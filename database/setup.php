<?php
require_once '../inc/config.php';

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
    die("Errore nella creazione della tabella transactions: " . $conn->error);
}

// Crea tabella categorie
$sql = "CREATE TABLE IF NOT EXISTS categories (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    type ENUM('entrata', 'uscita') NOT NULL,
    color VARCHAR(20) DEFAULT '#3498db'
)";

if ($conn->query($sql) === FALSE) {
    die("Errore nella creazione della tabella categories: " . $conn->error);
}

// Inserisci alcune categorie predefinite
$default_categories = [
    ['Stipendio', 'entrata', '#27ae60'],
    ['Bonus', 'entrata', '#2ecc71'],
    ['Regalo', 'entrata', '#9b59b6'],
    ['Investimento', 'entrata', '#f1c40f'],
    ['Altro', 'entrata', '#3498db'],
    ['Affitto/Mutuo', 'uscita', '#e74c3c'],
    ['Cibo', 'uscita', '#e67e22'],
    ['Trasporti', 'uscita', '#d35400'],
    ['Bollette', 'uscita', '#c0392b'],
    ['Svago', 'uscita', '#16a085'],
    ['Salute', 'uscita', '#8e44ad'],
    ['Abbigliamento', 'uscita', '#2c3e50'],
    ['Altro', 'uscita', '#7f8c8d']
];

foreach ($default_categories as $category) {
    $name = $category[0];
    $type = $category[1];
    $color = $category[2];
    
    // Verifica se la categoria esiste già
    $check = "SELECT id FROM categories WHERE name = '$name' AND type = '$type'";
    $result = $conn->query($check);
    
    if ($result->num_rows == 0) {
        $sql = "INSERT INTO categories (name, type, color) VALUES ('$name', '$type', '$color')";
        $conn->query($sql);
    }
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
    die("Errore nella creazione della tabella savings_goals: " . $conn->error);
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
    die("Errore nella creazione della tabella financial_tips: " . $conn->error);
}

// Inserisci alcuni consigli predefiniti
$default_tips = [
    ['Regola 50/30/20', 'Destina il 50% del reddito alle necessità, il 30% ai desideri e il 20% al risparmio', 'risparmio'],
    ['Fondo di emergenza', 'Crea un fondo di emergenza che copra 3-6 mesi di spese', 'risparmio'],
    ['Risparmio automatico', 'Imposta trasferimenti automatici verso il tuo conto di risparmio', 'risparmio'],
    ['Traccia le spese', 'Tieni traccia di tutte le spese per identificare aree di miglioramento', 'budget'],
    ['Evita acquisti impulsivi', 'Attendi 24-48 ore prima di effettuare acquisti non pianificati', 'spesa']
];

foreach ($default_tips as $tip) {
    $title = $tip[0];
    $description = $tip[1];
    $type = $tip[2];
    
    // Verifica se il consiglio esiste già
    $check = "SELECT id FROM financial_tips WHERE title = '$title'";
    $result = $conn->query($check);
    
    if ($result->num_rows == 0) {
        $sql = "INSERT INTO financial_tips (title, description, type) VALUES ('$title', '$description', '$type')";
        $conn->query($sql);
    }
}

echo "Installazione del database completata con successo!";
?>
