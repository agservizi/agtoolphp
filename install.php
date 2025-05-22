<?php
// install.php - Installazione database AGTool Finance
require_once 'inc/config.php';

$errors = [];
$success = [];

// Crea tabella transazioni
$sql = "CREATE TABLE IF NOT EXISTS transactions (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    type ENUM('entrata', 'uscita') NOT NULL,
    category VARCHAR(100),
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql) === FALSE) {
    $errors[] = "Errore nella creazione della tabella transactions: " . $conn->error;
} else {
    $success[] = "Tabella transactions creata o già esistente.";
}

// Crea tabella categorie
$sql = "CREATE TABLE IF NOT EXISTS categories (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('entrata', 'uscita') NOT NULL,
    color VARCHAR(20) DEFAULT '#3498db',
    UNIQUE KEY unique_cat (name, type)
)";
if ($conn->query($sql) === FALSE) {
    $errors[] = "Errore nella creazione della tabella categories: " . $conn->error;
} else {
    $success[] = "Tabella categories creata o già esistente.";
}

// Crea tabella obiettivi di risparmio
$sql = "CREATE TABLE IF NOT EXISTS savings_goals (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    target_amount DECIMAL(10,2) NOT NULL,
    current_amount DECIMAL(10,2) DEFAULT 0,
    target_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql) === FALSE) {
    $errors[] = "Errore nella creazione della tabella savings_goals: " . $conn->error;
} else {
    $success[] = "Tabella savings_goals creata o già esistente.";
}

// Crea tabella consigli finanziari
$sql = "CREATE TABLE IF NOT EXISTS financial_tips (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    type VARCHAR(50) NOT NULL,
    is_active TINYINT(1) DEFAULT 1
)";
if ($conn->query($sql) === FALSE) {
    $errors[] = "Errore nella creazione della tabella financial_tips: " . $conn->error;
} else {
    $success[] = "Tabella financial_tips creata o già esistente.";
}

// Crea tabella utenti
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(100) DEFAULT NULL,
    monthly_limit DECIMAL(10,2) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql) === FALSE) {
    $errors[] = "Errore nella creazione della tabella users: " . $conn->error;
} else {
    $success[] = "Tabella users creata o già esistente.";
}

// Crea tabella notifiche
$sql = "CREATE TABLE IF NOT EXISTS notifications (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    type ENUM('email','sms','push') NOT NULL DEFAULT 'email',
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
    scheduled_at DATETIME,
    sent_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
if ($conn->query($sql) === FALSE) {
    $errors[] = "Errore nella creazione della tabella notifications: " . $conn->error;
} else {
    $success[] = "Tabella notifications creata o già esistente.";
}

// Crea tabella transazioni ricorrenti
$sql = "CREATE TABLE IF NOT EXISTS recurring_transactions (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    type ENUM('entrata', 'uscita') NOT NULL,
    category VARCHAR(100),
    start_date DATE NOT NULL,
    end_date DATE DEFAULT NULL,
    frequency ENUM('daily','weekly','monthly','yearly') NOT NULL,
    next_occurrence DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
if ($conn->query($sql) === FALSE) {
    $errors[] = "Errore nella creazione della tabella recurring_transactions: " . $conn->error;
} else {
    $success[] = "Tabella recurring_transactions creata o già esistente.";
}

// Crea tabella user_settings per preferenze e notifiche
$sql = "CREATE TABLE IF NOT EXISTS user_settings (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    theme VARCHAR(20) DEFAULT 'light',
    language VARCHAR(10) DEFAULT 'it',
    email_notifications TINYINT(1) DEFAULT 1,
    sms_notifications TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
if ($conn->query($sql) === FALSE) {
    $errors[] = "Errore nella creazione della tabella user_settings: " . $conn->error;
} else {
    $success[] = "Tabella user_settings creata o già esistente.";
}

// Crea tabella sottoscrizioni push
$sql = "CREATE TABLE IF NOT EXISTS push_subscriptions (
    user_id INT(11) NOT NULL,
    endpoint VARCHAR(512) NOT NULL,
    p256dh VARCHAR(255) NOT NULL,
    auth VARCHAR(255) NOT NULL,
    PRIMARY KEY (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
if ($conn->query($sql) === FALSE) {
    $errors[] = "Errore nella creazione della tabella push_subscriptions: " . $conn->error;
} else {
    $success[] = "Tabella push_subscriptions creata o già esistente.";
}

// Inserisci utente di default solo se la tabella è vuota
$check = $conn->query("SELECT COUNT(*) as total FROM users");
if ($check && ($row = $check->fetch_assoc()) && $row['total'] == 0) {
    $sql = "INSERT INTO users (phone) VALUES ('3773798570')";
    if ($conn->query($sql) === FALSE) {
        $errors[] = "Errore nell'inserimento dell'utente di default.";
    } else {
        $success[] = "Utente di default inserito.";
    }
}

?><!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Installazione AGTool Finance</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body style="font-family: sans-serif; max-width: 600px; margin: 40px auto;">
    <h1>Installazione Database AGTool Finance</h1>
    <?php if ($errors): ?>
        <div style="color: red;">
            <h3>Si sono verificati errori:</h3>
            <ul>
                <?php foreach ($errors as $err) echo "<li>$err</li>"; ?>
            </ul>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div style="color: green;">
            <h3>Operazione completata:</h3>
            <ul>
                <?php foreach ($success as $msg) echo "<li>$msg</li>"; ?>
            </ul>
        </div>
    <?php endif; ?>
    <p><a href="index.php">Vai alla Dashboard</a></p>
</body>
</html>
