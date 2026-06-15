<?php
require __DIR__ . '/private/config.php';

session_start();

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

if (empty($_SESSION['admin_authed']) || (time() - ($_SESSION['admin_last'] ?? 0) >= SESSION_TIMEOUT)) {
    if (!empty($_SESSION['admin_authed'])) session_destroy();
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Session expired. Please log in again.']);
    exit;
}
$_SESSION['admin_last'] = time();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $url = APPS_SCRIPT_URL . '?action=list&password=' . urlencode(APPS_PASSWORD);
    $ctx = stream_context_create(['http' => ['follow_location' => 1, 'timeout' => 30]]);
    $response = @file_get_contents($url, false, $ctx);
    echo $response !== false ? $response : json_encode(['status' => 'error', 'message' => 'Could not reach sheet.']);

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = file_get_contents('php://input');
    $data = json_decode($body, true) ?? [];
    $data['password'] = APPS_PASSWORD;

    $ch = curl_init(APPS_SCRIPT_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_HTTPHEADER     => ['Content-Type: text/plain;charset=utf-8'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    echo $response !== false ? $response : json_encode(['status' => 'error', 'message' => 'Could not reach sheet.']);

} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
}
