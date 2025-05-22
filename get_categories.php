<?php
require_once 'inc/config.php';

// Verifica che sia stato fornito un tipo
if (!isset($_GET['type']) || empty($_GET['type'])) {
    echo json_encode([]);
    exit;
}

$type = clean_input($_GET['type']);

// Verifica che il tipo sia valido
if ($type != 'entrata' && $type != 'uscita') {
    echo json_encode([]);
    exit;
}

// Ottieni le categorie dal database
$sql = "SELECT * FROM categories WHERE type = '$type' ORDER BY name";
$result = $conn->query($sql);

$categories = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $categories[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'color' => $row['color']
        ];
    }
}

// Restituisci le categorie in formato JSON
header('Content-Type: application/json');
echo json_encode($categories);
?>
