<?php
require_once 'inc/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Raccogli i dati dal form
    $type = clean_input($_POST['type']);
    $amount = floatval($_POST['amount']);
    $description = clean_input($_POST['description']);
    $category = clean_input($_POST['category']);
    $date = clean_input($_POST['date']);
    
    // Verifica che i dati siano validi
    if (empty($description) || $amount <= 0 || empty($date)) {
        echo json_encode(['status' => 'error', 'message' => 'Tutti i campi sono obbligatori e l\'importo deve essere maggiore di zero']);
        exit;
    }
    
    if ($type != 'entrata' && $type != 'uscita') {
        echo json_encode(['status' => 'error', 'message' => 'Tipo di transazione non valido']);
        exit;
    }
    
    // Inserisci la transazione nel database
    $sql = "INSERT INTO transactions (description, amount, type, category, date) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdsss", $description, $amount, $type, $category, $date);
    
    if ($stmt->execute()) {
        // Se è un obiettivo di risparmio, aggiorna l'importo attuale
        if ($type == 'entrata' && isset($_POST['goal_id']) && !empty($_POST['goal_id'])) {
            $goal_id = intval($_POST['goal_id']);
            
            // Aggiorna l'importo attuale dell'obiettivo
            $update_goal = "UPDATE savings_goals 
                           SET current_amount = current_amount + ? 
                           WHERE id = ?";
            
            $update_stmt = $conn->prepare($update_goal);
            $update_stmt->bind_param("di", $amount, $goal_id);
            $update_stmt->execute();
        }
        // Notifica se la spesa supera un limite
        if ($type == 'uscita' && $amount >= 500) {
            session_start();
            $phone = $_SESSION['user_phone'] ?? null;
            if ($phone) {
                $user_stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
                $user_stmt->bind_param('s', $phone);
                $user_stmt->execute();
                $user_stmt->bind_result($user_id);
                if ($user_stmt->fetch()) {
                    $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, scheduled_at) VALUES (?, 'email', ?, ?, NOW())");
                    $title = 'Attenzione: spesa elevata';
                    $msg = "Hai registrato una spesa di " . format_currency($amount) . ": '" . $description . "'.";
                    $notif_stmt->bind_param('isss', $user_id, $title, $msg, $date);
                    $notif_stmt->execute();
                    $notification_id = $notif_stmt->insert_id;
                    $notif_stmt->close();
                    // Invio email se abilitato
                    $settings_stmt = $conn->prepare("SELECT email_notifications FROM user_settings WHERE user_id = ?");
                    $settings_stmt->bind_param('i', $user_id);
                    $settings_stmt->execute();
                    $settings_stmt->bind_result($email_notifications);
                    $settings_stmt->fetch();
                    $settings_stmt->close();
                    $email_stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
                    $email_stmt->bind_param('i', $user_id);
                    $email_stmt->execute();
                    $email_stmt->bind_result($user_email);
                    $email_stmt->fetch();
                    $email_stmt->close();
                    if ($email_notifications && filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
                        require_once 'inc/send_notification_email.php';
                        $sent = send_notification_email($user_email, $title, $msg);
                        $status = $sent ? 'sent' : 'failed';
                        $update_stmt = $conn->prepare("UPDATE notifications SET status = ?, sent_at = NOW() WHERE id = ?");
                        $update_stmt->bind_param('si', $status, $notification_id);
                        $update_stmt->execute();
                        $update_stmt->close();
                    }
                }
                $user_stmt->close();
            }
        }
        
        // Transazione salvata con successo
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            echo json_encode(['status' => 'success', 'message' => 'Transazione salvata con successo']);
        } else {
            header('Location: transactions.php?success=1');
        }
        exit;
    } else {
        // Errore nel salvataggio della transazione
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            echo json_encode(['status' => 'error', 'message' => 'Errore nel salvataggio della transazione']);
        } else {
            header('Location: transactions.php?error=1');
        }
        exit;
    }
} else {
    // Richiesta non valida
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        echo json_encode(['status' => 'error', 'message' => 'Metodo di richiesta non valido']);
    } else {
        header("Location: index.php?status=error&message=" . urlencode('Metodo di richiesta non valido'));
    }
}

$conn->close();
?>
