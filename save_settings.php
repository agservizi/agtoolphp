<?php
session_start();
require_once 'inc/config.php';
if (!isset($_SESSION['user_phone'])) {
    echo json_encode(['status'=>'error','message'=>'Non autenticato']);
    exit;
}
$phone = $_SESSION['user_phone'];
$stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
$stmt->bind_param('s', $phone);
$stmt->execute();
$stmt->bind_result($user_id);
if (!$stmt->fetch()) {
    echo json_encode(['status'=>'error','message'=>'Utente non trovato']);
    exit;
}
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'preferences') {
        $theme = clean_input($_POST['theme'] ?? 'light');
        $language = clean_input($_POST['language'] ?? 'it');
        $sql = "INSERT INTO user_settings (user_id, theme, language) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE theme=VALUES(theme), language=VALUES(language), updated_at=NOW()";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iss', $user_id, $theme, $language);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['status'=>'success','message'=>'Preferenze salvate']);
        exit;
    }
    if ($action === 'limit') {
        $limit = floatval($_POST['monthly_limit'] ?? 0);
        $sql = "UPDATE users SET monthly_limit = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('di', $limit, $user_id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['status'=>'success','message'=>'Limite salvato']);
        exit;
    }
    if ($action === 'notifications') {
        $email = isset($_POST['email_notifications']) ? 1 : 0;
        $sms = isset($_POST['sms_notifications']) ? 1 : 0;
        $sql = "INSERT INTO user_settings (user_id, email_notifications, sms_notifications) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE email_notifications=VALUES(email_notifications), sms_notifications=VALUES(sms_notifications), updated_at=NOW()";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iii', $user_id, $email, $sms);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['status'=>'success','message'=>'Notifiche salvate']);
        exit;
    }
    if ($action === 'delete_account') {
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->close();
        session_unset();
        session_destroy();
        echo json_encode(['status'=>'success','message'=>'Account eliminato']);
        exit;
    }
    if ($action === 'profile') {
        $email = clean_input($_POST['email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['status'=>'error','message'=>'Email non valida']);
            exit;
        }
        $sql = "UPDATE users SET email = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('si', $email, $user_id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['status'=>'success','message'=>'Email aggiornata']);
        exit;
    }
}
echo json_encode(['status'=>'error','message'=>'Richiesta non valida']);
?>
