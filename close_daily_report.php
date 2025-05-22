<?php
// close_daily_report.php - Chiude e salva il report giornaliero per l'utente loggato
session_start();
require_once __DIR__ . '/inc/config.php';

if (!isset($_SESSION['user_phone'])) {
    echo json_encode(['status' => 'error', 'message' => 'Non autenticato']);
    exit;
}
$phone = $_SESSION['user_phone'];
$stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
$stmt->bind_param('s', $phone);
$stmt->execute();
$stmt->bind_result($user_id);
if (!$stmt->fetch()) {
    echo json_encode(['status' => 'error', 'message' => 'Utente non trovato']);
    exit;
}
$stmt->close();

// Calcola la data di ieri (giorno da chiudere)
$yesterday = date('Y-m-d', strtotime('-1 day'));

// Controlla se il report è già stato generato
$sql = "SELECT id FROM exported_reports WHERE user_id = ? AND export_type = 'daily' AND export_view = 'daily' AND export_date = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('is', $user_id, $yesterday);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['status' => 'ok', 'message' => 'Report già generato']);
    exit;
}
$stmt->close();

// Recupera le transazioni di ieri
$sql = "SELECT date, description, category, type, amount FROM transactions WHERE user_id = ? AND date = ? ORDER BY date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('is', $user_id, $yesterday);
$stmt->execute();
$result = $stmt->get_result();
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}
$stmt->close();

if (count($rows) === 0) {
    echo json_encode(['status' => 'ok', 'message' => 'Nessuna transazione da esportare']);
    exit;
}

// Crea la cartella exports se non esiste
$exports_dir = __DIR__ . '/exports';
if (!is_dir($exports_dir)) {
    mkdir($exports_dir, 0775, true);
}

// Genera il file CSV
$file_name = "report-daily-{$user_id}-{$yesterday}.csv";
$file_path = $exports_dir . "/$file_name";
$download_url = 'exports/' . $file_name;
$out = fopen($file_path, 'w');
fputcsv($out, ['Data','Descrizione','Categoria','Tipo','Importo']);
foreach ($rows as $r) {
    fputcsv($out, [$r['date'], $r['description'], $r['category'], $r['type'], $r['amount']]);
}
fclose($out);

// Salva nella tabella exported_reports
$sql = "INSERT INTO exported_reports (user_id, export_type, export_view, export_date, file_name, file_path, download_url) VALUES (?, 'daily', 'daily', ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param('issss', $user_id, $yesterday, $file_name, $file_path, $download_url);
$stmt->execute();
$stmt->close();

echo json_encode(['status' => 'success', 'message' => 'Report giornaliero generato', 'url' => $download_url]);
