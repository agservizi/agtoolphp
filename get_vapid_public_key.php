<?php
// get_vapid_public_key.php
// Restituisce la chiave pubblica VAPID per la sottoscrizione push
header('Content-Type: application/json');
require_once __DIR__ . '/inc/config.php';

echo json_encode([
    'publicKey' => VAPID_PUBLIC_KEY
]);
