<?php
require __DIR__ . '/private/config.php';

session_start();
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

if (empty($_SESSION['admin_authed']) || (time() - ($_SESSION['admin_last'] ?? 0) >= SESSION_TIMEOUT)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}
$_SESSION['admin_last'] = time();

$HISTORY_FILE = __DIR__ . '/private/post_history.json';
$action       = $_POST['action'] ?? $_GET['action'] ?? '';
$postId       = trim($_POST['id'] ?? $_GET['id'] ?? '');

function loadHistory($file) {
    if (!file_exists($file)) return [];
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function saveHistory($file, $data) {
    file_put_contents($file, json_encode($data), LOCK_EX);
}

// ── Get history for a post ────────────────────────────────────────────────
if ($action === 'get_history') {
    if (!$postId) { echo json_encode(['status' => 'error', 'message' => 'No id']); exit; }
    $history = loadHistory($HISTORY_FILE);
    echo json_encode(['status' => 'success', 'history' => $history[$postId] ?? []]);

// ── Save a snapshot (called before each edit is committed) ───────────────
} elseif ($action === 'save_snapshot' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $id   = trim($body['id'] ?? '');
    if (!$id) { echo json_encode(['status' => 'error', 'message' => 'No id']); exit; }

    $snapshot = [
        'savedAt'   => time(),
        'title'     => $body['title']     ?? '',
        'date'      => $body['date']      ?? '',
        'category'  => $body['category']  ?? '',
        'excerpt'   => $body['excerpt']   ?? '',
        'content'   => $body['content']   ?? '',
        'imageUrl'  => $body['imageUrl']  ?? '',
        'published' => $body['published'] ?? '',
        'note'      => $body['note']      ?? '',
    ];

    $history = loadHistory($HISTORY_FILE);
    if (!isset($history[$id])) $history[$id] = [];
    array_unshift($history[$id], $snapshot);
    if (count($history[$id]) > 20) $history[$id] = array_slice($history[$id], 0, 20);
    saveHistory($HISTORY_FILE, $history);
    echo json_encode(['status' => 'success']);

} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
