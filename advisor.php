<?php
session_start();
require_once 'inc/config.php';
if (!isset($_SESSION['user_phone'])) {
    header('Location: login');
    exit;
}
$phone = $_SESSION['user_phone'];
$stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
$stmt->bind_param('s', $phone);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    $stmt->close();
    session_unset();
    session_destroy();
    header('Location: login');
    exit;
}
$stmt->close();

// Funzione per calcolare il risparmio proiettato
function calculate_projected_savings($amount, $frequency, $period) {
    switch ($frequency) {
        case 'daily':
            return $amount * 30 * $period;
        case 'weekly':
            return $amount * 4 * $period;
        case 'monthly':
            return $amount * $period;
        default:
            return $amount * $period;
    }
}

// Funzione per generare il consiglio di risparmio personalizzato
function generate_saving_advice($amount, $period, $frequency) {
    $total = calculate_projected_savings($amount, $frequency, $period);
    
    $freq_text = '';
    switch ($frequency) {
        case 'daily':
            $freq_text = 'al giorno';
            break;
        case 'weekly':
            $freq_text = 'a settimana';
            break;
        case 'monthly':
            $freq_text = 'al mese';
            break;
    }
    
    return [
        'amount' => $amount,
        'frequency' => $freq_text,
        'period' => $period,
        'total' => $total,
        'formatted_total' => format_currency($total),
        'message' => "Risparmiando " . format_currency($amount) . " " . $freq_text . " per " . $period . " mesi, accumulerai " . format_currency($total) . "."
    ];
}

// Gestisce le richieste AJAX
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    // Calcola il risparmio proiettato
    if ($action === 'calculate_savings' && isset($_GET['amount']) && isset($_GET['frequency']) && isset($_GET['period'])) {
        $amount = floatval($_GET['amount']);
        $frequency = $_GET['frequency'];
        $period = intval($_GET['period']);
        
        $advice = generate_saving_advice($amount, $period, $frequency);
        
        header('Content-Type: application/json');
        echo json_encode($advice);
        exit;
    }
    
    // Ottiene consiglio finanziario basato sulla domanda
    if ($action === 'get_advice' && isset($_GET['question'])) {
        $question = strtolower($_GET['question']);
        
        // Keywords per identificare il tipo di consiglio
        $savings_keywords = ['risparmio', 'risparmiare', 'mettere da parte', 'accantonare'];
        $budget_keywords = ['budget', 'bilancio', 'gestire', 'spese'];
        $investment_keywords = ['investire', 'investimenti', 'azioni', 'obbligazioni'];
        $debt_keywords = ['debito', 'prestito', 'mutuo', 'finanziamento'];
        
        // Verifica quale tipo di consiglio fornire
        $advice_type = '';
        
        foreach ($savings_keywords as $keyword) {
            if (strpos($question, $keyword) !== false) {
                $advice_type = 'risparmio';
                break;
            }
        }
        
        if (empty($advice_type)) {
            foreach ($budget_keywords as $keyword) {
                if (strpos($question, $keyword) !== false) {
                    $advice_type = 'budget';
                    break;
                }
            }
        }
        
        if (empty($advice_type)) {
            foreach ($investment_keywords as $keyword) {
                if (strpos($question, $keyword) !== false) {
                    $advice_type = 'investimento';
                    break;
                }
            }
        }
        
        if (empty($advice_type)) {
            foreach ($debt_keywords as $keyword) {
                if (strpos($question, $keyword) !== false) {
                    $advice_type = 'debito';
                    break;
                }
            }
        }
        
        // Se non identifica un tipo specifico, usa consigli generali
        if (empty($advice_type)) {
            $advice_type = 'generale';
        }
        
        // Ottieni un consiglio dal database in base al tipo
        $sql = "SELECT * FROM financial_tips WHERE type LIKE '%$advice_type%' OR type = 'generale' ORDER BY RAND() LIMIT 1";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            $tip = $result->fetch_assoc();
            $response = [
                'title' => $tip['title'],
                'message' => $tip['description']
            ];
        } else {
            // Consiglio predefinito se non ne trova uno nel database
            $response = [
                'title' => 'Consiglio Finanziario',
                'message' => 'Per migliorare la tua situazione finanziaria, inizia a tenere traccia di tutte le tue entrate e uscite. Questo ti aiuterà a identificare le aree in cui puoi risparmiare.'
            ];
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// Carica la pagina standard
include 'header.php';
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark">Consigliere Finanziario</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index">Home</a></li>
                    <li class="breadcrumb-item active">Consigliere Finanziario</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <!-- Simulatore di risparmi -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary">
                        <h3 class="card-title">Simulatore di Risparmio</h3>
                    </div>
                    <div class="card-body">
                        <p>Scopri quanto puoi risparmiare nel tempo con un piano regolare di accantonamento.</p>
                        
                        <form id="savings-simulator-form">
                            <div class="form-group">
                                <label for="sim-amount">Quanto vuoi risparmiare:</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">€</span>
                                    </div>
                                    <input type="number" id="sim-amount" class="form-control" value="100" min="1">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="sim-frequency">Con quale frequenza:</label>
                                <select id="sim-frequency" class="form-control">
                                    <option value="daily">Ogni giorno</option>
                                    <option value="weekly">Ogni settimana</option>
                                    <option value="monthly" selected>Ogni mese</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="sim-period">Per quanto tempo:</label>
                                <div class="d-flex align-items-center">
                                    <input type="range" id="sim-period" class="form-control-range flex-grow-1 mr-2" min="1" max="60" value="12">
                                    <span id="sim-period-display" class="badge badge-primary">12 mesi</span>
                                </div>
                            </div>
                            
                            <button type="button" id="sim-calculate" class="btn btn-primary btn-lg btn-block">Calcola</button>
                        </form>
                        
                        <div id="sim-results" class="mt-4" style="display: none;">
                            <div class="alert alert-success">
                                <h4 class="alert-heading">Risultato della simulazione</h4>
                                <p id="sim-results-message"></p>
                                <hr>
                                <h2 class="text-center" id="sim-results-total"></h2>
                            </div>
                            
                            <div class="text-center">
                                <button type="button" id="sim-create-goal" class="btn btn-success">
                                    <i class="fas fa-plus-circle"></i> Crea obiettivo di risparmio
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Valutazione spese -->
                <div class="card">
                    <div class="card-header bg-warning">
                        <h3 class="card-title">Valutazione delle Spese</h3>
                    </div>
                    <div class="card-body">
                        <p>Analizza le tue spese mensili e ricevi consigli su come ottimizzarle.</p>
                        
                        <?php
                        // Calcola le spese mensili per categoria
                        $current_month = date('m');
                        $current_year = date('Y');
                        
                        $sql = "SELECT category, SUM(amount) as total 
                                FROM transactions 
                                WHERE type = 'uscita' 
                                AND MONTH(date) = $current_month 
                                AND YEAR(date) = $current_year
                                GROUP BY category 
                                ORDER BY total DESC";
                                
                        $result = $conn->query($sql);
                        
                        if ($result->num_rows > 0) {
                            echo '<h5 class="mb-3">Le tue spese principali questo mese:</h5>';
                            echo '<ul class="list-group">';
                            while($row = $result->fetch_assoc()) {
                                echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
                                echo $row['category'];
                                echo '<span class="badge badge-danger badge-pill">' . format_currency($row['total']) . '</span>';
                                echo '</li>';
                            }
                            echo '</ul>';
                            
                            // Calcola il totale delle uscite mensili
                            $sql_total = "SELECT SUM(amount) as total 
                                        FROM transactions 
                                        WHERE type = 'uscita' 
                                        AND MONTH(date) = $current_month 
                                        AND YEAR(date) = $current_year";
                                        
                            $result_total = $conn->query($sql_total);
                            $total_expenses = $result_total->fetch_assoc()['total'];
                            
                            // Calcola il totale delle entrate mensili
                            $sql_income = "SELECT SUM(amount) as total 
                                        FROM transactions 
                                        WHERE type = 'entrata' 
                                        AND MONTH(date) = $current_month 
                                        AND YEAR(date) = $current_year";
                                        
                            $result_income = $conn->query($sql_income);
                            $total_income = $result_income->fetch_assoc()['total'];
                            
                            // Calcola la percentuale di spesa rispetto alle entrate
                            $expense_ratio = ($total_income > 0) ? ($total_expenses / $total_income) * 100 : 0;
                            
                            echo '<div class="mt-4">';
                            echo '<h5>Analisi rapporto spese/entrate:</h5>';
                            echo '<div class="progress" style="height: 25px;">';
                            
                            $bar_class = 'bg-success';
                            if ($expense_ratio > 50 && $expense_ratio <= 80) {
                                $bar_class = 'bg-warning';
                            } elseif ($expense_ratio > 80) {
                                $bar_class = 'bg-danger';
                            }
                            
                            echo '<div class="progress-bar ' . $bar_class . '" role="progressbar" style="width: ' . min(100, $expense_ratio) . '%;" ';
                            echo 'aria-valuenow="' . $expense_ratio . '" aria-valuemin="0" aria-valuemax="100">';
                            echo round($expense_ratio, 1) . '%';
                            echo '</div></div>';
                            echo '<small class="text-muted">Percentuale di spesa rispetto alle entrate</small>';
                            
                            // Fornisci un consiglio in base al rapporto spese/entrate
                            echo '<div class="alert ' . ($bar_class == 'bg-success' ? 'alert-success' : ($bar_class == 'bg-warning' ? 'alert-warning' : 'alert-danger')) . ' mt-3">';
                            if ($expense_ratio <= 50) {
                                echo '<i class="fas fa-thumbs-up mr-2"></i> Ottimo! Stai spendendo meno del 50% delle tue entrate. Considera di investire parte del tuo risparmio.';
                            } elseif ($expense_ratio <= 80) {
                                echo '<i class="fas fa-exclamation-triangle mr-2"></i> Attenzione. Le tue spese rappresentano il ' . round($expense_ratio, 1) . '% delle tue entrate. Cerca di ridurle per aumentare il tuo risparmio.';
                            } else {
                                echo '<i class="fas fa-exclamation-circle mr-2"></i> Allarme! Le tue spese rappresentano il ' . round($expense_ratio, 1) . '% delle tue entrate. Devi ridurle immediatamente per evitare problemi finanziari.';
                            }
                            echo '</div>';
                            
                            echo '</div>';
                        } else {
                            echo '<div class="alert alert-info">';
                            echo '<i class="fas fa-info-circle mr-2"></i> Non ci sono dati sulle spese per questo mese. Registra le tue transazioni per ricevere consigli personalizzati.';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
            
            <!-- Chat consigliere finanziario -->
            <div class="col-md-6">
                <div class="card direct-chat direct-chat-primary" style="height: 700px;">
                    <div class="card-header bg-success">
                        <h3 class="card-title">Consulente Virtuale</h3>
                    </div>
                    <div class="card-body">
                        <div class="direct-chat-messages" id="chat-messages" style="height: 550px;">
                            <!-- Messaggio di benvenuto -->
                            <div class="direct-chat-msg">
                                <div class="direct-chat-infos clearfix">
                                    <span class="direct-chat-name float-left">Consulente</span>
                                    <span class="direct-chat-timestamp float-right"><?php echo date('H:i'); ?></span>
                                </div>
                                <div class="direct-chat-img" style="background-color: #28a745; color: white; text-align: center; line-height: 40px;">
                                    <i class="fas fa-robot"></i>
                                </div>
                                <div class="direct-chat-text">
                                    Ciao! Sono il tuo consulente finanziario virtuale. Puoi chiedermi consigli su risparmio, budget, investimenti o gestione del debito. Come posso aiutarti oggi?
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <form id="chat-form">
                            <div class="input-group">
                                <input type="text" id="chat-input" placeholder="Scrivi una domanda..." class="form-control">
                                <span class="input-group-append">
                                    <button type="submit" class="btn btn-success">Invia</button>
                                </span>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Consigli del giorno -->
                <div class="card">
                    <div class="card-header bg-info">
                        <h3 class="card-title">Consigli Finanziari</h3>
                    </div>
                    <div class="card-body">
                        <h5 class="mb-3">Suggerimenti per migliorare le tue finanze:</h5>
                        
                        <?php
                        // Recupera i consigli dal database
                        $sql = "SELECT * FROM financial_tips WHERE is_active = 1 ORDER BY RAND() LIMIT 3";
                        $result = $conn->query($sql);
                        
                        if ($result->num_rows > 0) {
                            $counter = 1;
                            while($row = $result->fetch_assoc()) {
                                echo '<div class="callout callout-info">';
                                echo '<h5>' . $counter . '. ' . $row['title'] . '</h5>';
                                echo '<p>' . $row['description'] . '</p>';
                                echo '</div>';
                                $counter++;
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>
