<?php
require_once 'inc/config.php';

// Verifica che sia stato fornito un tipo di grafico
if (!isset($_GET['type']) || empty($_GET['type'])) {
    echo json_encode(['error' => 'Tipo di grafico non specificato']);
    exit;
}

$chart_type = clean_input($_GET['type']);

// Ottieni i dati per il grafico entrate vs uscite
if ($chart_type === 'income_expense') {
    // Ottieni i dati degli ultimi 6 mesi
    $labels = [];
    $income_data = [];
    $expense_data = [];
    $savings_data = [];
    
    for ($i = 5; $i >= 0; $i--) {
        $month = date('m', strtotime("-$i months"));
        $year = date('Y', strtotime("-$i months"));
        
        // Formato del mese per il label del grafico
        $month_label = date('M Y', strtotime("$year-$month-01"));
        
        // Ottieni le entrate del mese
        $sql_income = "SELECT COALESCE(SUM(amount), 0) as total FROM transactions 
                     WHERE type = 'entrata' 
                     AND MONTH(date) = $month 
                     AND YEAR(date) = $year";
        
        $result_income = $conn->query($sql_income);
        $income = $result_income->fetch_assoc()['total'];
        
        // Ottieni le uscite del mese
        $sql_expense = "SELECT COALESCE(SUM(amount), 0) as total FROM transactions 
                      WHERE type = 'uscita' 
                      AND MONTH(date) = $month 
                      AND YEAR(date) = $year";
        
        $result_expense = $conn->query($sql_expense);
        $expense = $result_expense->fetch_assoc()['total'];
        
        // Calcola il risparmio del mese
        $savings = $income - $expense;
        
        // Aggiungi i dati agli array
        $labels[] = $month_label;
        $income_data[] = $income;
        $expense_data[] = $expense;
        $savings_data[] = $savings;
    }
    
    // Restituisci i dati in formato JSON
    echo json_encode([
        'labels' => $labels,
        'income' => $income_data,
        'expense' => $expense_data,
        'savings' => $savings_data
    ]);
    exit;
}

// Ottieni i dati per il grafico delle categorie di spesa
if ($chart_type === 'expense_categories') {
    $month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
    $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
    $sql = "SELECT category, SUM(amount) as total FROM transactions WHERE type = 'uscita' AND MONTH(date) = $month AND YEAR(date) = $year GROUP BY category ORDER BY total DESC";
    $result = $conn->query($sql);
    $categories = [];
    $amounts = [];
    $colors = ['#007bff','#28a745','#dc3545','#ffc107','#17a2b8','#6f42c1','#fd7e14','#20c997','#6610f2','#e83e8c'];
    $i = 0;
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $categories[] = $row['category'];
            $amounts[] = floatval($row['total']);
            $i++;
        }
    }
    echo json_encode([
        'categories' => $categories,
        'amounts' => $amounts,
        'colors' => array_slice($colors,0,count($categories))
    ]);
    exit;
}

// Ottieni i dati per il grafico dell'andamento giornaliero
if ($chart_type === 'daily_trend') {
    $month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
    $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
    $days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $labels = [];
    $income = array_fill(1, $days, 0);
    $expense = array_fill(1, $days, 0);
    $sql = "SELECT DAY(date) as day, type, SUM(amount) as total FROM transactions WHERE MONTH(date) = $month AND YEAR(date) = $year GROUP BY day, type";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            if ($row['type'] == 'entrata') $income[intval($row['day'])] = floatval($row['total']);
            else $expense[intval($row['day'])] = floatval($row['total']);
        }
    }
    for ($d=1; $d<=$days; $d++) $labels[] = $d;
    echo json_encode([
        'labels' => $labels,
        'income' => array_values($income),
        'expense' => array_values($expense)
    ]);
    exit;
}

// Se il tipo di grafico non è riconosciuto
echo json_encode(['error' => 'Tipo di grafico non valido']);
?>
