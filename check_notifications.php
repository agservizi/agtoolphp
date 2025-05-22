<?php
// check_notifications.php - Controlla e invia notifiche automatiche (email/SMS) via AJAX
session_start();
require_once 'inc/config.php';
require_once 'inc/send_notification_email.php';

header('Content-Type: application/json');
if (!isset($_SESSION['user_phone'])) {
    echo json_encode(['status'=>'error','message'=>'Non autenticato']);
    exit;
}
$phone = $_SESSION['user_phone'];
$stmt = $conn->prepare("SELECT id, email, monthly_limit FROM users WHERE phone = ?");
$stmt->bind_param('s', $phone);
$stmt->execute();
$stmt->bind_result($user_id, $user_email, $monthly_limit);
if (!$stmt->fetch()) {
    echo json_encode(['status'=>'error','message'=>'Utente non trovato']);
    exit;
}
$stmt->close();

// Recupera preferenze notifiche
$settings_stmt = $conn->prepare("SELECT email_notifications, sms_notifications FROM user_settings WHERE user_id = ?");
$settings_stmt->bind_param('i', $user_id);
$settings_stmt->execute();
$settings_stmt->bind_result($email_notifications, $sms_notifications);
$settings_stmt->fetch();
$settings_stmt->close();

$notifiche = [];

// 1. Limite di spesa superato
if ($monthly_limit > 0) {
    $mese = date('m');
    $anno = date('Y');
    $sql = "SELECT COALESCE(SUM(amount),0) as total FROM transactions WHERE user_id = ? AND type = 'uscita' AND MONTH(date) = ? AND YEAR(date) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iss', $user_id, $mese, $anno);
    $stmt->execute();
    $stmt->bind_result($spese_mese);
    $stmt->fetch();
    $stmt->close();
    if ($spese_mese >= $monthly_limit) {
        $msg = "Hai superato il limite di spesa mensile di ".format_currency($monthly_limit)."!";
        $notifiche[] = $msg;
        if ($email_notifications) send_notification_email($user_email, "Limite di spesa superato", $msg);
        // SMS: qui puoi integrare API SMS
    }
}

// 2. Obiettivi di risparmio raggiunti
$sql = "SELECT name, target_amount FROM savings_goals WHERE user_id = ? AND current_amount >= target_amount AND target_amount > 0";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($goal_name, $goal_amount);
while ($stmt->fetch()) {
    $msg = "Obiettivo di risparmio raggiunto: $goal_name (".format_currency($goal_amount).")";
    $notifiche[] = $msg;
    if ($email_notifications) send_notification_email($user_email, "Obiettivo raggiunto", $msg);
    // SMS: qui puoi integrare API SMS
}
$stmt->close();

// 3. Obiettivi/ricorrenze in scadenza entro 3 giorni
$oggi = date('Y-m-d');
$tra3 = date('Y-m-d', strtotime('+3 days'));
$sql = "SELECT name, target_date FROM savings_goals WHERE user_id = ? AND target_date IS NOT NULL AND target_date BETWEEN ? AND ? AND current_amount < target_amount";
$stmt = $conn->prepare($sql);
$stmt->bind_param('iss', $user_id, $oggi, $tra3);
$stmt->execute();
$stmt->bind_result($goal_name, $target_date);
while ($stmt->fetch()) {
    $msg = "Obiettivo '$goal_name' in scadenza il $target_date!";
    $notifiche[] = $msg;
    if ($email_notifications) send_notification_email($user_email, "Obiettivo in scadenza", $msg);
    // SMS: qui puoi integrare API SMS
}
$stmt->close();

// Ricorrenze
$sql = "SELECT description, next_occurrence FROM recurring_transactions WHERE user_id = ? AND next_occurrence BETWEEN ? AND ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('iss', $user_id, $oggi, $tra3);
$stmt->execute();
$stmt->bind_result($desc, $next_occurrence);
while ($stmt->fetch()) {
    $msg = "Ricorrenza '$desc' prevista per $next_occurrence";
    $notifiche[] = $msg;
    if ($email_notifications) send_notification_email($user_email, "Ricorrenza in arrivo", $msg);
    // SMS: qui puoi integrare API SMS
}
$stmt->close();

// Salva le notifiche in tabella (solo se non già presenti per oggi con stesso titolo e messaggio)
foreach ($notifiche as $msg) {
    // Determina titolo e tipo
    if (strpos($msg, 'limite di spesa') !== false) {
        $title = 'Limite di spesa superato';
        $type = 'email';
    } elseif (strpos($msg, 'Obiettivo di risparmio raggiunto') !== false) {
        $title = 'Obiettivo raggiunto';
        $type = 'email';
    } elseif (strpos($msg, 'in scadenza') !== false) {
        $title = 'Obiettivo in scadenza';
        $type = 'email';
    } elseif (strpos($msg, 'Ricorrenza') !== false) {
        $title = 'Ricorrenza in arrivo';
        $type = 'email';
    } else {
        $title = 'Notifica';
        $type = 'email';
    }
    // Verifica se già presente oggi
    $sql = "SELECT id FROM notifications WHERE user_id = ? AND title = ? AND message = ? AND DATE(created_at) = CURDATE()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iss', $user_id, $title, $msg);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows == 0) {
        $stmt->close();
        $sql = "INSERT INTO notifications (user_id, type, title, message, status, scheduled_at) VALUES (?, ?, ?, ?, 'pending', NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('isss', $user_id, $type, $title, $msg);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt->close();
    }
}

if (count($notifiche) > 0) {
    echo json_encode(['status'=>'ok','notifiche'=>$notifiche]);
} else {
    echo json_encode(['status'=>'ok','notifiche'=>[]]);
}
?>
