<?php
// save_push_subscription.php
// Salva la subscription push per l'utente loggato
session_start();
require_once __DIR__ . '/inc/config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Non autenticato']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['endpoint'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Dati non validi']);
    exit;
}

$user_id = $_SESSION['user_id'];
$endpoint = $data['endpoint'];
$keys = $data['keys'] ?? [];
$p256dh = $keys['p256dh'] ?? '';
$auth = $keys['auth'] ?? '';

// Salva o aggiorna la subscription nel DB
$db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
$stmt = $db->prepare('REPLACE INTO push_subscriptions (user_id, endpoint, p256dh, auth) VALUES (?, ?, ?, ?)');
$stmt->execute([$user_id, $endpoint, $p256dh, $auth]);

echo json_encode(['status' => 'ok']);
