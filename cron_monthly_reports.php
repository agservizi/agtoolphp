<?php
// cron_monthly_reports.php - Genera report mensili automatici per tutti gli utenti
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/vendor/autoload.php';
if (!class_exists('FPDF')) {
    echo "FPDF non installato. Installa con composer require setasign/fpdf\n";
    exit(1);
}

// Funzione per ottenere il nome del mese
function get_month_name($month_number) {
    $months = [1=>'Gennaio',2=>'Febbraio',3=>'Marzo',4=>'Aprile',5=>'Maggio',6=>'Giugno',7=>'Luglio',8=>'Agosto',9=>'Settembre',10=>'Ottobre',11=>'Novembre',12=>'Dicembre'];
    return $months[$month_number] ?? '';
}

// Funzione per salvare il report esportato
function save_exported_report($conn, $user_id, $export_type, $export_view, $export_month, $export_year, $export_category, $file_name, $file_path, $download_url) {
    $stmt = $conn->prepare("INSERT INTO exported_reports (user_id, export_type, export_view, export_month, export_year, export_category, file_name, file_path, download_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('issiiisss', $user_id, $export_type, $export_view, $export_month, $export_year, $export_category, $file_name, $file_path, $download_url);
    $stmt->execute();
    $stmt->close();
}

// Calcola mese e anno da esportare (mese appena concluso)
$now = new DateTime();
$now->modify('first day of this month');
$now->modify('-1 day');
$export_month = intval($now->format('m'));
$export_year = intval($now->format('Y'));

$exports_dir = __DIR__ . '/exports';
if (!is_dir($exports_dir)) {
    mkdir($exports_dir, 0775, true);
}

// Prendi tutti gli utenti
$res_users = $conn->query("SELECT id, phone FROM users");
while ($user = $res_users->fetch_assoc()) {
    $user_id = $user['id'];
    $phone = $user['phone'];
    $where = "WHERE MONTH(date) = $export_month AND YEAR(date) = $export_year";
    $sql = "SELECT date, description, category, type, amount FROM transactions WHERE user_id = $user_id AND MONTH(date) = $export_month AND YEAR(date) = $export_year ORDER BY date DESC";
    $result = $conn->query($sql);
    $rows = [];
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    // CSV
    $file_name = "report-{$user_id}-{$export_year}-{$export_month}.csv";
    $file_path = $exports_dir . "/$file_name";
    $download_url = 'exports/' . $file_name;
    $out = fopen($file_path, 'w');
    fputcsv($out, ['Data','Descrizione','Categoria','Tipo','Importo']);
    foreach ($rows as $r) {
        fputcsv($out, [$r['date'],$r['description'],$r['category'],$r['type'],$r['amount']]);
    }
    fclose($out);
    save_exported_report($conn, $user_id, 'csv', 'monthly', $export_month, $export_year, '', $file_name, $file_path, $download_url);
    // Excel
    $file_name = "report-{$user_id}-{$export_year}-{$export_month}.xls";
    $file_path = $exports_dir . "/$file_name";
    $download_url = 'exports/' . $file_name;
    $out = fopen($file_path, 'w');
    fputcsv($out, ['Data','Descrizione','Categoria','Tipo','Importo'], "\t");
    foreach ($rows as $r) {
        fputcsv($out, [$r['date'],$r['description'],$r['category'],$r['type'],$r['amount']], "\t");
    }
    fclose($out);
    save_exported_report($conn, $user_id, 'excel', 'monthly', $export_month, $export_year, '', $file_name, $file_path, $download_url);
    // PDF
    class PDFReport extends FPDF {
        function Header() {
            $this->SetFillColor(0,123,255);
            $this->Rect(0,0,210,30,'F');
            if (file_exists(__DIR__.'/assets/img/logo-192.png')) {
                $this->Image(__DIR__.'/assets/img/logo-192.png',10,7,16);
            }
            $this->SetFont('Arial','B',18);
            $this->SetTextColor(255,255,255);
            $this->Cell(0,15,'AGTool Finance - Report Proforma',0,1,'C');
            $this->Ln(2);
        }
        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial','I',8);
            $this->SetTextColor(150,150,150);
            $this->Cell(0,10,'Generato il '.date('d/m/Y H:i').' - Pagina '.$this->PageNo().'/{nb}',0,0,'C');
        }
        function TableHeader() {
            $this->SetFont('Arial','B',11);
            $this->SetFillColor(230,230,230);
            $this->SetTextColor(0,0,0);
            $this->Cell(30,10,'Data',1,0,'C',true);
            $this->Cell(60,10,'Descrizione',1,0,'C',true);
            $this->Cell(40,10,'Categoria',1,0,'C',true);
            $this->Cell(20,10,'Tipo',1,0,'C',true);
            $this->Cell(30,10,'Importo',1,1,'C',true);
        }
        function TableRow($row) {
            $this->SetFont('Arial','',10);
            $this->SetTextColor(0,0,0);
            $this->Cell(30,9,$row['date'],1,0,'C');
            $this->Cell(60,9,utf8_decode($row['description']),1,0,'L');
            $this->Cell(40,9,utf8_decode($row['category']),1,0,'L');
            if ($row['type'] == 'entrata') {
                $this->SetTextColor(40,167,69);
            } else {
                $this->SetTextColor(220,53,69);
            }
            $this->Cell(20,9,ucfirst($row['type']),1,0,'C');
            $this->SetTextColor(0,0,0);
            $this->Cell(30,9,number_format($row['amount'],2,',','.'),1,1,'R');
        }
    }
    $file_name = "report-{$user_id}-{$export_year}-{$export_month}.pdf";
    $file_path = $exports_dir . "/$file_name";
    $download_url = 'exports/' . $file_name;
    $pdf = new PDFReport('P','mm','A4');
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->Ln(10);
    $pdf->SetFont('Arial','B',12);
    $pdf->SetTextColor(0,123,255);
    $pdf->Cell(0,8,'Periodo: '.get_month_name($export_month).' '.$export_year,0,1,'L');
    $pdf->Ln(2);
    $pdf->TableHeader();
    if (count($rows) > 0) {
        foreach ($rows as $r) {
            $pdf->TableRow($r);
        }
    } else {
        $pdf->SetFont('Arial','I',11);
        $pdf->Cell(180,10,'Nessun dato disponibile per il periodo selezionato.',1,1,'C');
    }
    $pdf->Ln(5);
    $pdf->SetFont('Arial','B',12);
    $pdf->SetFillColor(240,240,240);
    $pdf->SetTextColor(0,0,0);
    $tot_entrate = 0; $tot_uscite = 0;
    foreach ($rows as $r) {
        if ($r['type'] == 'entrata') $tot_entrate += $r['amount'];
        else $tot_uscite += $r['amount'];
    }
    $pdf->Cell(60,10,'Totale Entrate:',1,0,'R',true);
    $pdf->SetTextColor(40,167,69);
    $pdf->Cell(40,10,number_format($tot_entrate,2,',','.'),1,0,'R',true);
    $pdf->SetTextColor(0,0,0);
    $pdf->Cell(40,10,'Totale Uscite:',1,0,'R',true);
    $pdf->SetTextColor(220,53,69);
    $pdf->Cell(40,10,number_format($tot_uscite,2,',','.'),1,1,'R',true);
    $pdf->SetTextColor(0,0,0);
    $pdf->Cell(60,10,'Saldo Netto:',1,0,'R',true);
    $saldo = $tot_entrate - $tot_uscite;
    $pdf->SetTextColor($saldo >= 0 ? 40 : 220, $saldo >= 0 ? 167 : 53, $saldo >= 0 ? 69 : 69);
    $pdf->Cell(40,10,number_format($saldo,2,',','.'),1,1,'R',true);
    $pdf->SetTextColor(0,0,0);
    $pdf->Output('F', $file_path);
    save_exported_report($conn, $user_id, 'pdf', 'monthly', $export_month, $export_year, '', $file_name, $file_path, $download_url);
}
echo "Report mensili generati per il mese $export_month/$export_year\n";
