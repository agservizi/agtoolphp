<?php
// Script da eseguire periodicamente (es. via cron) per generare le transazioni ricorrenti
require_once 'inc/config.php';

$oggi = date('Y-m-d');
$sql = "SELECT * FROM recurring_transactions WHERE next_occurrence <= ? AND (end_date IS NULL OR next_occurrence <= end_date)";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $oggi);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    // Inserisci la transazione effettiva
    $sql_insert = "INSERT INTO transactions (description, amount, type, category, date) VALUES (?, ?, ?, ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param('sdsss', $row['description'], $row['amount'], $row['type'], $row['category'], $row['next_occurrence']);
    $stmt_insert->execute();
    $stmt_insert->close();

    // Calcola la prossima occorrenza
    $next = $row['next_occurrence'];
    switch ($row['frequency']) {
        case 'daily':
            $next = date('Y-m-d', strtotime($next . ' +1 day'));
            break;
        case 'weekly':
            $next = date('Y-m-d', strtotime($next . ' +1 week'));
            break;
        case 'monthly':
            $next = date('Y-m-d', strtotime($next . ' +1 month'));
            break;
        case 'yearly':
            $next = date('Y-m-d', strtotime($next . ' +1 year'));
            break;
    }
    // Aggiorna la prossima occorrenza solo se non si è superata la data di fine
    if ($row['end_date'] && $next > $row['end_date']) {
        // Non aggiornare più
        continue;
    }
    $sql_update = "UPDATE recurring_transactions SET next_occurrence = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param('si', $next, $row['id']);
    $stmt_update->execute();
    $stmt_update->close();
}
$stmt->close();
