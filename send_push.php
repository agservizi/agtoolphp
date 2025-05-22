<?php
// send_push.php
// Invia una notifica push a un utente specifico
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/vendor/autoload.php';

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

if (!isset($_GET['user_id']) || !isset($_GET['title']) || !isset($_GET['body'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Parametri mancanti']);
    exit;
}

$user_id = (int)$_GET['user_id'];
$title = $_GET['title'];
$body = $_GET['body'];
$url = isset($_GET['url']) ? $_GET['url'] : '/notifications';

// Recupera la subscription dal DB
$db = new PDO(DB_DSN, DB_USER, DB_PASSWORD);
$stmt = $db->prepare('SELECT * FROM push_subscriptions WHERE user_id = ?');
$stmt->execute([$user_id]);
$sub = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$sub) {
    echo json_encode(['status' => 'error', 'message' => 'Nessuna subscription trovata']);
    exit;
}

$subscription = Subscription::create([
    'endpoint' => $sub['endpoint'],
    'publicKey' => $sub['p256dh'],
    'authToken' => $sub['auth'],
    'contentEncoding' => 'aesgcm',
]);

$webPush = new WebPush([
    'VAPID' => [
        'subject' => 'mailto:info@agtool.local',
        'publicKey' => VAPID_PUBLIC_KEY,
        'privateKey' => VAPID_PRIVATE_KEY,
    ],
]);

$payload = json_encode([
    'title' => $title,
    'body' => $body,
    'url' => $url
]);

$report = $webPush->sendOneNotification($subscription, $payload);

if ($report->isSuccess()) {
    echo json_encode(['status' => 'ok']);
} else {
    echo json_encode(['status' => 'error', 'message' => $report->getReason()]);
}
