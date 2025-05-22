<?php
// Funzione per inviare una notifica email usando PHPMailer
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/email_template.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Invia una notifica email usando PHPMailer
 * @param string $to_email
 * @param string $title
 * @param string $message
 * @return bool true se inviata, false se errore
 */
function send_notification_email($to_email, $title, $message) {
    // Validazione indirizzo email
    if (!filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
        error_log("Email sending failed: indirizzo email non valido");
        return false;
    }
    
    $mail = new PHPMailer(true);
    try {
        // Configurazione SMTP: usa variabili d'ambiente o config.php per sicurezza!
        $mail->isSMTP();
        $mail->Host = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = getenv('SMTP_USER') ?: 'inserisci_email';
        $mail->Password = getenv('SMTP_PASS') ?: 'inserisci_password';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom('no-reply@agtool.local', 'AGTool Finance');
        $mail->addAddress($to_email);
        $mail->isHTML(true);
        $mail->Subject = $title;
        $mail->Body = get_notification_email_html($title, $message);
        $mail->AltBody = strip_tags($message);
        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log errore
        error_log("Email sending failed: " . $e->getMessage());
        return false;
    }
}
// Nota: imposta le variabili d'ambiente SMTP_HOST, SMTP_USER, SMTP_PASS per la produzione.
