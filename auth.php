<?php
require __DIR__ . '/private/config.php';

session_start();
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

define('OWNER_EMAIL', 'nyema@karunahealthcare.com.au');
define('SITE_URL',    'https://nyemahermiston.com.au');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

function ipStatus($ip, $deviceToken = '') {
    $wl  = __DIR__ . '/private/whitelist.json';
    $tmp = __DIR__ . '/private/temp_access.json';
    $tok = __DIR__ . '/private/pending_tokens.json';
    $dtf = __DIR__ . '/private/device_tokens.json';

    $whitelist = file_exists($wl) ? json_decode(file_get_contents($wl), true) : [];
    if (in_array($ip, $whitelist)) return 'allowed';

    $tmpAccess = file_exists($tmp) ? json_decode(file_get_contents($tmp), true) : [];
    if (isset($tmpAccess[$ip]) && $tmpAccess[$ip] > time()) return 'allowed';

    if ($deviceToken) {
        $deviceTokens = file_exists($dtf) ? json_decode(file_get_contents($dtf), true) : [];
        if (isset($deviceTokens[$deviceToken])) {
            $dt = $deviceTokens[$deviceToken];
            if ($dt['expires'] === 0 || $dt['expires'] > time()) return 'allowed';
        }
    }

    $tokens = file_exists($tok) ? json_decode(file_get_contents($tok), true) : [];
    foreach ($tokens as $data) {
        if ($data['ip'] === $ip && $data['expires'] > time()) {
            if ($data['status'] === 'pending')  return 'pending';
            if ($data['status'] === 'denied')   return 'denied';
            if (in_array($data['status'], ['approved_once', 'approved_forever'])) return 'allowed';
        }
    }
    return 'unknown';
}

function requireAdmin() {
    if (empty($_SESSION['admin_authed'])) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
        exit;
    }
}

// ── Login ─────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'login') {

    $ip          = $_SERVER['REMOTE_ADDR'];
    $deviceToken = trim($_POST['device_token'] ?? $_GET['device_token'] ?? '');
    if (!in_array(ipStatus($ip, $deviceToken), ['allowed'])) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Access not authorised.']);
        exit;
    }

    $attempts  = $_SESSION['login_attempts']  ?? 0;
    $lockUntil = $_SESSION['login_lock_until'] ?? 0;

    if ($lockUntil > time()) {
        $wait = ceil(($lockUntil - time()) / 60);
        http_response_code(429);
        echo json_encode(['status' => 'error', 'message' => "Too many failed attempts. Try again in {$wait} minute(s)."]);
        exit;
    }

    $pw = $_POST['password'] ?? '';
    if (password_verify($pw, ADMIN_HASH)) {
        unset($_SESSION['login_attempts'], $_SESSION['login_lock_until']);
        session_regenerate_id(true);
        $_SESSION['admin_authed'] = true;
        $_SESSION['admin_last']   = time();
        echo json_encode(['status' => 'success']);
    } else {
        $attempts++;
        $_SESSION['login_attempts'] = $attempts;
        if ($attempts >= MAX_ATTEMPTS) {
            $_SESSION['login_lock_until'] = time() + LOCKOUT_SECONDS;
            $_SESSION['login_attempts']   = 0;
            http_response_code(429);
            echo json_encode(['status' => 'error', 'message' => 'Too many failed attempts. Locked for 15 minutes.']);
        } else {
            $left = MAX_ATTEMPTS - $attempts;
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => "Incorrect password. {$left} attempt(s) remaining."]);
        }
    }

// ── Session check ─────────────────────────────────────────────────────────
} elseif ($action === 'check') {

    if (!empty($_SESSION['admin_authed'])) {
        if (time() - ($_SESSION['admin_last'] ?? 0) < SESSION_TIMEOUT) {
            $_SESSION['admin_last'] = time();
            echo json_encode(['status' => 'ok']);
        } else {
            session_destroy();
            echo json_encode(['status' => 'expired']);
        }
    } else {
        echo json_encode(['status' => 'unauthenticated']);
    }

// ── IP status check ───────────────────────────────────────────────────────
} elseif ($action === 'check_ip') {

    $deviceToken = trim($_GET['device_token'] ?? '');
    echo json_encode(['status' => ipStatus($_SERVER['REMOTE_ADDR'], $deviceToken)]);

// ── Request access ────────────────────────────────────────────────────────
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'request_access') {

    $ip          = $_SERVER['REMOTE_ADDR'];
    $name        = trim($_POST['name'] ?? '');
    $deviceToken = trim($_POST['device_token'] ?? '');
    $tokFile     = __DIR__ . '/private/pending_tokens.json';
    $tokens      = file_exists($tokFile) ? json_decode(file_get_contents($tokFile), true) : [];

    foreach ($tokens as $data) {
        if ($data['ip'] === $ip && $data['status'] === 'pending' && $data['expires'] > time()) {
            echo json_encode(['status' => 'already_pending']);
            exit;
        }
    }

    $token   = bin2hex(random_bytes(32));
    $expires = time() + 86400;
    $tokens[$token] = ['ip' => $ip, 'name' => $name, 'device_token' => $deviceToken, 'created' => time(), 'expires' => $expires, 'status' => 'pending'];
    file_put_contents($tokFile, json_encode($tokens), LOCK_EX);

    $time     = gmdate('d M Y, H:i') . ' UTC';
    $nameRow  = $name ? "<tr><td style='padding:4px 16px 4px 0;color:#888'>From</td><td><strong>" . htmlspecialchars($name) . "</strong></td></tr>" : '';
    $once     = SITE_URL . '/ip_access.php?token=' . $token . '&action=once';
    $forever  = SITE_URL . '/ip_access.php?token=' . $token . '&action=forever';
    $deny     = SITE_URL . '/ip_access.php?token=' . $token . '&action=deny';

    $subject  = 'Blog Admin Access Request' . ($name ? " from {$name}" : '') . ' – Nyema Hermiston';
    $body     = "<html><body style='font-family:sans-serif;max-width:520px;margin:0 auto;padding:2rem'>
<h2 style='color:#5862a3;margin-bottom:0.5rem'>Blog Admin Access Request</h2>
<p>Someone is requesting access to your blog admin panel.</p>
<table style='border-collapse:collapse;margin-bottom:1.5rem'>
  {$nameRow}
  <tr><td style='padding:4px 16px 4px 0;color:#888'>IP Address</td><td><strong>{$ip}</strong></td></tr>
  <tr><td style='padding:4px 16px 4px 0;color:#888'>Time</td><td>{$time}</td></tr>
</table>
<p style='margin-bottom:1rem'>Choose what to do:</p>
<a href='{$once}' style='display:inline-block;background:#2d9c6e;color:#fff;padding:0.65rem 1.25rem;border-radius:6px;text-decoration:none;font-weight:600;margin:0.25rem 0.4rem 0.25rem 0'>Approve for this session</a>
<a href='{$forever}' style='display:inline-block;background:#5862a3;color:#fff;padding:0.65rem 1.25rem;border-radius:6px;text-decoration:none;font-weight:600;margin:0.25rem 0.4rem 0.25rem 0'>Approve permanently</a>
<a href='{$deny}' style='display:inline-block;background:#c0392b;color:#fff;padding:0.65rem 1.25rem;border-radius:6px;text-decoration:none;font-weight:600;margin:0.25rem 0.4rem 0.25rem 0'>Deny</a>
<p style='color:#aaa;font-size:0.82rem;margin-top:2rem'>This link expires in 24 hours. If you did not expect this, click Deny.</p>
</body></html>";

    $headers = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nFrom: noreply@nyemahermiston.com.au\r\n";
    mail(OWNER_EMAIL, $subject, $body, $headers);
    echo json_encode(['status' => 'pending']);

// ── List approved IPs ─────────────────────────────────────────────────────
} elseif ($action === 'list_ips') {

    requireAdmin();
    $wlFile  = __DIR__ . '/private/whitelist.json';
    $tmpFile = __DIR__ . '/private/temp_access.json';

    $permanent = file_exists($wlFile) ? json_decode(file_get_contents($wlFile), true) : [];
    $tmpRaw    = file_exists($tmpFile) ? json_decode(file_get_contents($tmpFile), true) : [];

    $temporary = [];
    $clean     = [];
    foreach ($tmpRaw as $ip => $exp) {
        if ($exp > time()) {
            $temporary[] = ['ip' => $ip, 'expires' => $exp, 'expires_human' => gmdate('d M Y, H:i', $exp) . ' UTC'];
            $clean[$ip]  = $exp;
        }
    }
    if (count($clean) !== count($tmpRaw)) file_put_contents($tmpFile, json_encode($clean), LOCK_EX);

    echo json_encode(['permanent' => array_values($permanent), 'temporary' => $temporary]);

// ── Remove IP ─────────────────────────────────────────────────────────────
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'remove_ip') {

    requireAdmin();
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    $ip   = $data['ip']   ?? '';
    $type = $data['type'] ?? '';

    if (!$ip || !in_array($type, ['permanent', 'temporary'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
        exit;
    }

    if ($type === 'permanent') {
        $wlFile    = __DIR__ . '/private/whitelist.json';
        $whitelist = file_exists($wlFile) ? json_decode(file_get_contents($wlFile), true) : [];
        $whitelist = array_values(array_filter($whitelist, fn($i) => $i !== $ip));
        file_put_contents($wlFile, json_encode($whitelist), LOCK_EX);
    } else {
        $tmpFile   = __DIR__ . '/private/temp_access.json';
        $tmpAccess = file_exists($tmpFile) ? json_decode(file_get_contents($tmpFile), true) : [];
        unset($tmpAccess[$ip]);
        file_put_contents($tmpFile, json_encode($tmpAccess), LOCK_EX);
    }

    // Revoke any device tokens linked to this IP via pending_tokens records
    $tokFile      = __DIR__ . '/private/pending_tokens.json';
    $dtFile       = __DIR__ . '/private/device_tokens.json';
    $pendingToks  = file_exists($tokFile) ? json_decode(file_get_contents($tokFile), true) : [];
    $deviceTokens = file_exists($dtFile)  ? json_decode(file_get_contents($dtFile),  true) : [];
    foreach ($pendingToks as $rec) {
        if (($rec['ip'] ?? '') === $ip && !empty($rec['device_token'])) {
            unset($deviceTokens[$rec['device_token']]);
        }
    }
    file_put_contents($dtFile, json_encode($deviceTokens), LOCK_EX);

    echo json_encode(['status' => 'success']);

// ── Logout ────────────────────────────────────────────────────────────────
} elseif ($action === 'logout') {

    session_destroy();
    echo json_encode(['status' => 'ok']);

} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
}
