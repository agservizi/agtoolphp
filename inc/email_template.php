<?php
function get_notification_email_html($title, $message) {
    return '<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Notifica AGTool Finance</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f7f7f7; margin: 0; padding: 0; }
        .container { background: #fff; max-width: 500px; margin: 40px auto; border-radius: 8px; box-shadow: 0 2px 8px #ddd; padding: 32px; }
        .header { background: #007bff; color: #fff; padding: 16px 0; border-radius: 8px 8px 0 0; text-align: center; font-size: 1.3em; }
        .content { padding: 24px 0; color: #222; }
        .footer { color: #888; font-size: 0.95em; text-align: center; margin-top: 24px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">AGTool Finance - Notifica</div>
        <div class="content">
            <h2 style="color:#007bff;">' . htmlspecialchars($title) . '</h2>
            <p>' . nl2br(htmlspecialchars($message)) . '</p>
        </div>
        <div class="footer">
            Ricevi questa email perché hai attivato le notifiche email su AGTool Finance.<br>
            <small>Non rispondere a questa email.</small>
        </div>
    </div>
</body>
</html>';
}
