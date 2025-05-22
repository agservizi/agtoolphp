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

// Funzione per ottenere un consiglio AI da OpenRouter DeepSeek
function get_ai_advice_from_openrouter($question, $user_context = '') {
    global $OPENROUTER_API_KEY;
    $api_key = $OPENROUTER_API_KEY;
    $url = 'https://openrouter.ai/api/v1/chat/completions';
    
    $messages = [
        [
            'role' => 'system',
            'content' => "Sei un consulente finanziario esperto. Fornisci risposte pratiche, chiare e personalizzate su risparmio, investimenti, budget e gestione del denaro. Usa un tono amichevole e professionale. $user_context"
        ],
        [
            'role' => 'user',
            'content' => $question
        ]
    ];
    
    $data = [
        'model' => 'deepseek/deepseek-r1:free',
        'messages' => $messages,
        'max_tokens' => 512,
        'temperature' => 0.7
    ];
    
    $headers = [
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json',
        'HTTP-Referer: https://agtool.local/' // opzionale, per policy OpenRouter
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($err || !$response || $http_code !== 200) {
        return 'Mi dispiace, si è verificato un errore durante l\'elaborazione della richiesta. Codice HTTP: ' . $http_code . ' - Errore: ' . $err . ' - Risposta: ' . htmlspecialchars($response);
    }
    $json = json_decode($response, true);
    if (isset($json['choices'][0]['message']['content'])) {
        return trim($json['choices'][0]['message']['content']);
    } else {
        return 'Non sono riuscito a generare un consiglio in questo momento.';
    }
}

// Funzione per generare il contesto finanziario dell'utente per l'AI
function get_user_financial_context($conn, $user_phone) {
    $current_month = date('m');
    $current_year = date('Y');
    // Spese totali mese
    $sql_exp = "SELECT SUM(amount) as total FROM transactions WHERE type = 'uscita' AND user_phone = '$user_phone' AND MONTH(date) = $current_month AND YEAR(date) = $current_year";
    $result_exp = $conn->query($sql_exp);
    $total_expenses = $result_exp && $result_exp->num_rows > 0 ? floatval($result_exp->fetch_assoc()['total']) : 0;
    // Entrate totali mese
    $sql_inc = "SELECT SUM(amount) as total FROM transactions WHERE type = 'entrata' AND user_phone = '$user_phone' AND MONTH(date) = $current_month AND YEAR(date) = $current_year";
    $result_inc = $conn->query($sql_inc);
    $total_income = $result_inc && $result_inc->num_rows > 0 ? floatval($result_inc->fetch_assoc()['total']) : 0;
    // Categorie principali di spesa
    $sql_cat = "SELECT category, SUM(amount) as total FROM transactions WHERE type = 'uscita' AND user_phone = '$user_phone' AND MONTH(date) = $current_month AND YEAR(date) = $current_year GROUP BY category ORDER BY total DESC LIMIT 3";
    $result_cat = $conn->query($sql_cat);
    $top_categories = [];
    if ($result_cat && $result_cat->num_rows > 0) {
        while($row = $result_cat->fetch_assoc()) {
            $top_categories[] = $row['category'] . ' (' . format_currency($row['total']) . ')';
        }
    }
    // Rapporto spese/entrate
    $expense_ratio = ($total_income > 0) ? ($total_expenses / $total_income) * 100 : 0;
    // Saldo totale
    $sql_balance = "SELECT SUM(CASE WHEN type = 'entrata' THEN amount ELSE -amount END) as saldo FROM transactions WHERE user_phone = '$user_phone'";
    $result_balance = $conn->query($sql_balance);
    $saldo = $result_balance && $result_balance->num_rows > 0 ? floatval($result_balance->fetch_assoc()['saldo']) : 0;
    // Obiettivi di risparmio (se presenti)
    $sql_goal = "SELECT name, target_amount, current_amount FROM savings WHERE user_phone = '$user_phone' AND status = 'attivo' ORDER BY id DESC LIMIT 1";
    $result_goal = $conn->query($sql_goal);
    $goal_str = '';
    if ($result_goal && $result_goal->num_rows > 0) {
        $goal = $result_goal->fetch_assoc();
        $goal_str = "Obiettivo attivo: " . $goal['name'] . ", risparmiati " . format_currency($goal['current_amount']) . " su " . format_currency($goal['target_amount']) . ". ";
    }
    // Costruisci il contesto
    $context = "Dati utente: Spese mese: " . format_currency($total_expenses) . ", Entrate mese: " . format_currency($total_income) . ", Rapporto spese/entrate: " . round($expense_ratio,1) . "%. Categorie principali: " . implode(', ', $top_categories) . ". Saldo totale: " . format_currency($saldo) . ". $goal_str";
    return $context;
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
            $db_response = [
                'title' => $tip['title'],
                'message' => $tip['description']
            ];
        } else {
            $db_response = [
                'title' => 'Consiglio Finanziario',
                'message' => 'Per migliorare la tua situazione finanziaria, inizia a tenere traccia di tutte le tue entrate e uscite. Questo ti aiuterà a identificare le aree in cui puoi risparmiare.'
            ];
        }
        // Chiamata all'AI per un consiglio personalizzato con contesto
        $user_context = get_user_financial_context($conn, $phone);
        $ai_message = get_ai_advice_from_openrouter($_GET['question'], $user_context);
        $response = [
            'title' => $db_response['title'],
            'message' => $db_response['message'],
            'ai_message' => $ai_message
        ];
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    if ($action === 'get_ai_advice' && isset($_GET['question'])) {
        $question = trim($_GET['question']);
        $user_context = get_user_financial_context($conn, $phone);
        $ai_advice = get_ai_advice_from_openrouter($question, $user_context);
        header('Content-Type: application/json');
        echo json_encode(['title' => 'Consiglio AI', 'message' => $ai_advice]);
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
                <h1 class="m-0 text-dark">Consulente</h1>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Simulatore di risparmio: calcolo risparmio proiettato
    const simCalculateBtn = document.getElementById('sim-calculate');
    if (simCalculateBtn) {
        simCalculateBtn.addEventListener('click', function() {
            const amount = document.getElementById('sim-amount').value;
            const frequency = document.getElementById('sim-frequency').value;
            const period = document.getElementById('sim-period').value;
            
            // Chiamata AJAX per calcolare il risparmio
            fetch('advisor.php?action=calculate_savings&amount=' + amount + '&frequency=' + frequency + '&period=' + period)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('sim-results-message').innerHTML = data.message;
                    document.getElementById('sim-results-total').innerHTML = data.formatted_total;
                    document.getElementById('sim-results').style.display = 'block';
                })
                .catch(error => {
                    console.error('Errore nel calcolo del risparmio:', error);
                });
        });
    }
    
    // Aggiorna l'etichetta del periodo selezionato
    const simPeriodInput = document.getElementById('sim-period');
    const simPeriodDisplay = document.getElementById('sim-period-display');
    if (simPeriodInput && simPeriodDisplay) {
        simPeriodInput.addEventListener('input', function() {
            simPeriodDisplay.innerHTML = this.value + ' mesi';
        });
    }
    
    // Chat AI: invio domanda e ricezione risposta
    const chatForm = document.getElementById('chat-form');
    const chatInput = document.getElementById('chat-input');
    const chatMessages = document.getElementById('chat-messages');
    if (chatForm && chatInput && chatMessages) {
        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const question = chatInput.value.trim();
            if (!question) return;
            // Mostra domanda utente
            chatMessages.innerHTML += `<div class='direct-chat-msg right'><div class='direct-chat-infos clearfix'><span class='direct-chat-name float-right'>Tu</span><span class='direct-chat-timestamp float-left'>${new Date().toLocaleTimeString()}</span></div><div class='direct-chat-text bg-primary text-white'>${question}</div></div>`;
            chatInput.value = '';
            // Chiamata AJAX a get_ai_advice
            fetch('advisor.php?action=get_ai_advice&question=' + encodeURIComponent(question))
                .then(r => r.json())
                .then(data => {
                    chatMessages.innerHTML += `<div class='direct-chat-msg'><div class='direct-chat-infos clearfix'><span class='direct-chat-name float-left'>Consulente AI</span><span class='direct-chat-timestamp float-right'>${new Date().toLocaleTimeString()}</span></div><div class='direct-chat-text'>${data.message}</div></div>`;
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                })
                .catch(() => {
                    chatMessages.innerHTML += `<div class='direct-chat-msg'><div class='direct-chat-text text-danger'>Errore nel recupero del consiglio AI.</div></div>`;
                });
        });
    }
});
</script>
