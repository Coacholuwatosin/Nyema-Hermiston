<?php
require __DIR__ . '/private/config.php';

$token  = $_GET['token']  ?? '';
$action = $_GET['action'] ?? '';

if (!$token || !in_array($action, ['once', 'forever', 'deny'])) {
    http_response_code(400);
    die(page('Invalid Request', 'This link is not valid.', '#888'));
}

$tokensFile = __DIR__ . '/private/pending_tokens.json';
$tokens     = file_exists($tokensFile) ? json_decode(file_get_contents($tokensFile), true) : [];

if (!isset($tokens[$token])) {
    die(page('Link Expired', 'This link has already been used or has expired.', '#888'));
}

$data = $tokens[$token];

if ($data['expires'] < time()) {
    unset($tokens[$token]);
    file_put_contents($tokensFile, json_encode($tokens), LOCK_EX);
    die(page('Link Expired', 'This approval link has expired. The user will need to request access again.', '#888'));
}

if ($data['status'] !== 'pending') {
    die(page('Already Handled', 'This request has already been actioned.', '#888'));
}

$ip          = $data['ip'];
$deviceToken = $data['device_token'] ?? '';
$dtFile      = __DIR__ . '/private/device_tokens.json';

if ($action === 'forever') {
    $wlFile    = __DIR__ . '/private/whitelist.json';
    $whitelist = file_exists($wlFile) ? json_decode(file_get_contents($wlFile), true) : [];
    if (!in_array($ip, $whitelist)) {
        $whitelist[] = $ip;
        file_put_contents($wlFile, json_encode($whitelist), LOCK_EX);
    }
    if ($deviceToken) {
        $deviceTokens = file_exists($dtFile) ? json_decode(file_get_contents($dtFile), true) : [];
        $deviceTokens[$deviceToken] = ['expires' => 0];
        file_put_contents($dtFile, json_encode($deviceTokens), LOCK_EX);
    }
    $tokens[$token]['status'] = 'approved_forever';
    $title = 'Permanently Approved';
    $body  = "IP <strong>{$ip}</strong> has been added to the permanent whitelist. Their browser is also recognised — they will not need approval again even if their IP address changes.";
    $color = '#5862a3';

} elseif ($action === 'once') {
    $tmpFile   = __DIR__ . '/private/temp_access.json';
    $tmpAccess = file_exists($tmpFile) ? json_decode(file_get_contents($tmpFile), true) : [];
    $tmpAccess[$ip] = time() + 86400;
    file_put_contents($tmpFile, json_encode($tmpAccess), LOCK_EX);
    if ($deviceToken) {
        $deviceTokens = file_exists($dtFile) ? json_decode(file_get_contents($dtFile), true) : [];
        $deviceTokens[$deviceToken] = ['expires' => time() + 86400];
        file_put_contents($dtFile, json_encode($deviceTokens), LOCK_EX);
    }
    $tokens[$token]['status'] = 'approved_once';
    $title = 'Session Access Approved';
    $body  = "IP <strong>{$ip}</strong> has been granted access for the next 24 hours.";
    $color = '#2d9c6e';

} else {
    $tokens[$token]['status'] = 'denied';
    $title = 'Access Denied';
    $body  = "IP <strong>{$ip}</strong> has been denied. They will see a denial message on the admin page.";
    $color = '#c0392b';
}

file_put_contents($tokensFile, json_encode($tokens), LOCK_EX);
echo page($title, $body, $color);

function page($title, $body, $color) {
    $check = $color !== '#c0392b'
        ? '<path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>'
        : '<path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>';
    return "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>{$title}</title></head>
    <body style='font-family:sans-serif;max-width:480px;margin:4rem auto;padding:1rem;text-align:center'>
    <div style='width:64px;height:64px;border-radius:50%;background:{$color};margin:0 auto 1.25rem;display:flex;align-items:center;justify-content:center'>
    <svg width='30' height='30' fill='white' viewBox='0 0 24 24'>{$check}</svg></div>
    <h2 style='color:{$color};margin-bottom:0.5rem'>{$title}</h2>
    <p style='color:#555'>{$body}</p>
    <p style='color:#aaa;font-size:0.82rem;margin-top:2rem'>You can close this tab.</p>
    </body></html>";
}
