<?php
session_start();
require_once 'inc/config.php';
if (!isset($_SESSION['user_phone'])) {
    header('Location: login.php');
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
    header('Location: login.php');
    exit;
}
$stmt->close();

// Impostazione dei filtri
$view = isset($_GET['view']) ? clean_input($_GET['view']) : 'monthly';
$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$category = isset($_GET['category']) ? clean_input($_GET['category']) : '';

// Funzione per ottenere il nome del mese
function get_month_name($month_number) {
    $months = [
        1 => 'Gennaio',
        2 => 'Febbraio',
        3 => 'Marzo',
        4 => 'Aprile',
        5 => 'Maggio',
        6 => 'Giugno',
        7 => 'Luglio',
        8 => 'Agosto',
        9 => 'Settembre',
        10 => 'Ottobre',
        11 => 'Novembre',
        12 => 'Dicembre'
    ];
    return $months[$month_number] ?? '';
}

// Includi l'header
include 'header.php';
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark">Reportistica</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index">Home</a></li>
                    <li class="breadcrumb-item active">Reportistica</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <!-- Pulsanti esportazione -->
    <div class="mb-3">
        <form method="get" action="reports.php" class="d-inline">
            <input type="hidden" name="export" value="csv">
            <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
            <input type="hidden" name="month" value="<?php echo htmlspecialchars($month); ?>">
            <input type="hidden" name="year" value="<?php echo htmlspecialchars($year); ?>">
            <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
            <button type="submit" class="btn btn-outline-primary"><i class="fas fa-file-csv"></i> Esporta CSV</button>
        </form>
        <form method="get" action="reports.php" class="d-inline">
            <input type="hidden" name="export" value="excel">
            <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
            <input type="hidden" name="month" value="<?php echo htmlspecialchars($month); ?>">
            <input type="hidden" name="year" value="<?php echo htmlspecialchars($year); ?>">
            <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
            <button type="submit" class="btn btn-outline-success"><i class="fas fa-file-excel"></i> Esporta Excel</button>
        </form>
        <form method="get" action="reports.php" class="d-inline">
            <input type="hidden" name="export" value="pdf">
            <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
            <input type="hidden" name="month" value="<?php echo htmlspecialchars($month); ?>">
            <input type="hidden" name="year" value="<?php echo htmlspecialchars($year); ?>">
            <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
            <button type="submit" class="btn btn-outline-danger"><i class="fas fa-file-pdf"></i> Esporta PDF</button>
        </form>
    </div>
    <!-- Fine pulsanti esportazione -->
    
    <!-- Filtri -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Filtri</h3>
        </div>
        <div class="card-body">
            <form method="get" class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Vista</label>
                        <select name="view" class="form-control" onchange="this.form.submit()">
                            <option value="monthly" <?php echo $view == 'monthly' ? 'selected' : ''; ?>>Mensile</option>
                            <option value="yearly" <?php echo $view == 'yearly' ? 'selected' : ''; ?>>Annuale</option>
                            <option value="category" <?php echo $view == 'category' ? 'selected' : ''; ?>>Per Categoria</option>
                            <option value="trend" <?php echo $view == 'trend' ? 'selected' : ''; ?>>Trend</option>
                        </select>
                    </div>
                </div>
                
                <?php if ($view != 'trend') { ?>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Anno</label>
                        <select name="year" class="form-control" onchange="this.form.submit()">
                            <?php
                            // Ottieni gli anni disponibili nel database
                            $sql_years = "SELECT DISTINCT YEAR(date) as year FROM transactions ORDER BY year DESC";
                            $result_years = $conn->query($sql_years);
                            
                            if ($result_years->num_rows > 0) {
                                while($row = $result_years->fetch_assoc()) {
                                    $selected = ($year == $row['year']) ? 'selected' : '';
                                    echo "<option value='{$row['year']}' $selected>{$row['year']}</option>";
                                }
                            } else {
                                echo "<option value='" . date('Y') . "' selected>" . date('Y') . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <?php } ?>
                
                <?php if ($view == 'monthly') { ?>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Mese</label>
                        <select name="month" class="form-control" onchange="this.form.submit()">
                            <?php
                            for ($i = 1; $i <= 12; $i++) {
                                $selected = ($month == $i) ? 'selected' : '';
                                echo "<option value='$i' $selected>" . get_month_name($i) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <?php } ?>
                
                <?php if ($view == 'category') { ?>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Categoria</label>
                        <select name="category" class="form-control" onchange="this.form.submit()">
                            <option value="">Tutte</option>
                            <?php
                            // Ottieni tutte le categorie distinte presenti nelle transazioni
                            $sql_categories = "SELECT DISTINCT category FROM transactions ORDER BY category";
                            $result_categories = $conn->query($sql_categories);
                            
                            if ($result_categories->num_rows > 0) {
                                while($row = $result_categories->fetch_assoc()) {
                                    $selected = ($category == $row['category']) ? 'selected' : '';
                                    echo "<option value='{$row['category']}' $selected>{$row['category']}</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <?php } ?>
            </form>
        </div>
    </div>
    
    <?php
    // Report Mensile
    if ($view == 'monthly') {
        $month_name = get_month_name($month);
        
        // Calcola entrate e uscite per il mese selezionato
        $sql_income = "SELECT COALESCE(SUM(amount), 0) as total FROM transactions 
                      WHERE type = 'entrata' AND MONTH(date) = $month AND YEAR(date) = $year";
        $sql_expense = "SELECT COALESCE(SUM(amount), 0) as total FROM transactions 
                       WHERE type = 'uscita' AND MONTH(date) = $month AND YEAR(date) = $year";
        
        $result_income = $conn->query($sql_income);
        $result_expense = $conn->query($sql_expense);
        
        $income = $result_income->fetch_assoc()['total'];
        $expense = $result_expense->fetch_assoc()['total'];
        $savings = $income - $expense;
    ?>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Report Mensile: <?php echo $month_name . ' ' . $year; ?></h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="info-box bg-success">
                                <span class="info-box-icon"><i class="fas fa-arrow-up"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Entrate Totali</span>
                                    <span class="info-box-number"><?php echo format_currency($income); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box bg-danger">
                                <span class="info-box-icon"><i class="fas fa-arrow-down"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Uscite Totali</span>
                                    <span class="info-box-number"><?php echo format_currency($expense); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box <?php echo $savings >= 0 ? 'bg-info' : 'bg-warning'; ?>">
                                <span class="info-box-icon"><i class="fas fa-wallet"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Risparmio Mensile</span>
                                    <span class="info-box-number"><?php echo format_currency($savings); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Grafico a torta delle spese per categoria -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Distribuzione Spese per Categoria</h3>
                                </div>
                                <div class="card-body">
                                    <canvas id="expense-category-chart" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Grafico a barre delle entrate e uscite giornaliere -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Andamento Giornaliero</h3>
                                </div>
                                <div class="card-body">
                                    <canvas id="daily-chart" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Tabella Top 5 entrate -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Top 5 Entrate</h3>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Data</th>
                                                <th>Descrizione</th>
                                                <th>Categoria</th>
                                                <th class="text-right">Importo</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $sql_top_income = "SELECT * FROM transactions
                                                               WHERE type = 'entrata' AND MONTH(date) = $month AND YEAR(date) = $year
                                                               ORDER BY amount DESC LIMIT 5";
                                            $result_top_income = $conn->query($sql_top_income);
                                            
                                            if ($result_top_income->num_rows > 0) {
                                                while($row = $result_top_income->fetch_assoc()) {
                                                    echo "<tr>";
                                                    echo "<td>" . format_date($row['date']) . "</td>";
                                                    echo "<td>{$row['description']}</td>";
                                                    echo "<td>{$row['category']}</td>";
                                                    echo "<td class='text-right text-success'>" . format_currency($row['amount']) . "</td>";
                                                    echo "</tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='4' class='text-center'>Nessuna entrata trovata</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tabella Top 5 uscite -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Top 5 Uscite</h3>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Data</th>
                                                <th>Descrizione</th>
                                                <th>Categoria</th>
                                                <th class="text-right">Importo</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $sql_top_expense = "SELECT * FROM transactions
                                                                WHERE type = 'uscita' AND MONTH(date) = $month AND YEAR(date) = $year
                                                                ORDER BY amount DESC LIMIT 5";
                                            $result_top_expense = $conn->query($sql_top_expense);
                                            
                                            if ($result_top_expense->num_rows > 0) {
                                                while($row = $result_top_expense->fetch_assoc()) {
                                                    echo "<tr>";
                                                    echo "<td>" . format_date($row['date']) . "</td>";
                                                    echo "<td>{$row['description']}</td>";
                                                    echo "<td>{$row['category']}</td>";
                                                    echo "<td class='text-right text-danger'>" . format_currency($row['amount']) . "</td>";
                                                    echo "</tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='4' class='text-center'>Nessuna uscita trovata</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php
    } // Fine report mensile
    
    // Report Annuale
    else if ($view == 'yearly') {
    ?>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Report Annuale: <?php echo $year; ?></h3>
                </div>
                <div class="card-body">
                    <?php
                    // Calcola entrate e uscite per l'anno selezionato
                    $sql_income = "SELECT COALESCE(SUM(amount), 0) as total FROM transactions 
                                  WHERE type = 'entrata' AND YEAR(date) = $year";
                    $sql_expense = "SELECT COALESCE(SUM(amount), 0) as total FROM transactions 
                                   WHERE type = 'uscita' AND YEAR(date) = $year";
                    
                    $result_income = $conn->query($sql_income);
                    $result_expense = $conn->query($sql_expense);
                    
                    $income = $result_income->fetch_assoc()['total'];
                    $expense = $result_expense->fetch_assoc()['total'];
                    $savings = $income - $expense;
                    
                    // Calcola il tasso di risparmio
                    $savings_rate = 0;
                    if ($income > 0) {
                        $savings_rate = ($savings / $income) * 100;
                    }
                    ?>
                    
                    <div class="row">
                        <div class="col-md-3">
                            <div class="info-box bg-success">
                                <span class="info-box-icon"><i class="fas fa-arrow-up"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Entrate Annuali</span>
                                    <span class="info-box-number"><?php echo format_currency($income); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-danger">
                                <span class="info-box-icon"><i class="fas fa-arrow-down"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Uscite Annuali</span>
                                    <span class="info-box-number"><?php echo format_currency($expense); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box <?php echo $savings >= 0 ? 'bg-info' : 'bg-warning'; ?>">
                                <span class="info-box-icon"><i class="fas fa-wallet"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Risparmio Annuale</span>
                                    <span class="info-box-number"><?php echo format_currency($savings); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box <?php echo $savings_rate >= 20 ? 'bg-success' : ($savings_rate >= 10 ? 'bg-info' : 'bg-warning'); ?>">
                                <span class="info-box-icon"><i class="fas fa-percentage"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Tasso di Risparmio</span>
                                    <span class="info-box-number"><?php echo number_format($savings_rate, 1); ?>%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Grafico andamento mensile -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Andamento Mensile</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="monthly-trend-chart" height="300"></canvas>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Grafico distribuzione entrate per categoria -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Distribuzione Entrate</h3>
                                </div>
                                <div class="card-body">
                                    <canvas id="income-category-chart" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Grafico distribuzione uscite per categoria -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Distribuzione Uscite</h3>
                                </div>
                                <div class="card-body">
                                    <canvas id="yearly-expense-category-chart" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php
    } // Fine report annuale
    
    // Report per categoria
    else if ($view == 'category') {
        $category_title = !empty($category) ? ": " . $category : "";
    ?>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Report per Categoria<?php echo $category_title; ?> (<?php echo $year; ?>)</h3>
                </div>
                <div class="card-body">
                    <?php
                    // Costruisci la clausola WHERE
                    $where_clause = "WHERE YEAR(date) = $year";
                    if (!empty($category)) {
                        $where_clause .= " AND category = '$category'";
                    }
                    
                    // Ottieni le transazioni
                    $sql_transactions = "SELECT * FROM transactions $where_clause ORDER BY date DESC";
                    $result_transactions = $conn->query($sql_transactions);
                    
                    // Calcola i totali
                    $sql_income = "SELECT COALESCE(SUM(amount), 0) as total FROM transactions 
                                  $where_clause AND type = 'entrata'";
                    $sql_expense = "SELECT COALESCE(SUM(amount), 0) as total FROM transactions 
                                   $where_clause AND type = 'uscita'";
                    
                    $result_income = $conn->query($sql_income);
                    $result_expense = $conn->query($sql_expense);
                    
                    $income = $result_income->fetch_assoc()['total'];
                    $expense = $result_expense->fetch_assoc()['total'];
                    $net = $income - $expense;
                    
                    // Se è stata selezionata una categoria specifica, mostra un grafico dell'andamento mensile
                    if (!empty($category)) {
                    ?>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Andamento Mensile - <?php echo $category; ?></h3>
                                </div>
                                <div class="card-body">
                                    <canvas id="category-monthly-chart" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="info-box bg-success">
                                <span class="info-box-icon"><i class="fas fa-arrow-up"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Entrate Totali</span>
                                    <span class="info-box-number"><?php echo format_currency($income); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box bg-danger">
                                <span class="info-box-icon"><i class="fas fa-arrow-down"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Uscite Totali</span>
                                    <span class="info-box-number"><?php echo format_currency($expense); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box <?php echo $net >= 0 ? 'bg-info' : 'bg-warning'; ?>">
                                <span class="info-box-icon"><i class="fas fa-calculator"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Saldo Netto</span>
                                    <span class="info-box-number"><?php echo format_currency($net); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tabella transazioni -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Elenco Transazioni</h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Tipo</th>
                                        <th>Descrizione</th>
                                        <?php if (empty($category)) { echo "<th>Categoria</th>"; } ?>
                                        <th class="text-right">Importo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($result_transactions->num_rows > 0) {
                                        while($row = $result_transactions->fetch_assoc()) {
                                            $color_class = ($row['type'] == 'entrata') ? 'text-success' : 'text-danger';
                                            $type_icon = ($row['type'] == 'entrata') ? 'fa-arrow-up' : 'fa-arrow-down';
                                            $type_text = ($row['type'] == 'entrata') ? 'Entrata' : 'Uscita';
                                            
                                            echo "<tr>";
                                            echo "<td>" . format_date($row['date']) . "</td>";
                                            echo "<td><i class='fas {$type_icon}'></i> {$type_text}</td>";
                                            echo "<td>{$row['description']}</td>";
                                            if (empty($category)) {
                                                echo "<td>{$row['category']}</td>";
                                            }
                                            echo "<td class='{$color_class} text-right'>" . format_currency($row['amount']) . "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        $colspan = empty($category) ? 5 : 4;
                                        echo "<tr><td colspan='{$colspan}' class='text-center'>Nessuna transazione trovata</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php
    } // Fine report per categoria
    
    // Report trend
    else if ($view == 'trend') {
    ?>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Analisi Trend</h3>
                </div>
                <div class="card-body">
                    <!-- Grafico trend entrate/uscite ultimi 12 mesi -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Trend Ultimi 12 Mesi</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="yearly-trend-chart" height="300"></canvas>
                        </div>
                    </div>
                    
                    <!-- Confronto mese corrente vs mese precedente -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Confronto con il Mese Precedente</h3>
                                </div>
                                <div class="card-body">
                                    <?php
                                    // Ottieni il mese corrente e quello precedente
                                    $current_month = date('m');
                                    $current_year = date('Y');
                                    
                                    // Calcola il mese precedente, considerando anche il cambio di anno
                                    $prev_month = $current_month - 1;
                                    $prev_year = $current_year;
                                    
                                    if ($prev_month == 0) {
                                        $prev_month = 12;
                                        $prev_year--;
                                    }
                                    
                                    // Ottieni i dati per il mese corrente
                                    $sql_current_income = "SELECT COALESCE(SUM(amount), 0) as total FROM transactions 
                                                         WHERE type = 'entrata' AND MONTH(date) = $current_month AND YEAR(date) = $current_year";
                                    $sql_current_expense = "SELECT COALESCE(SUM(amount), 0) as total FROM transactions 
                                                          WHERE type = 'uscita' AND MONTH(date) = $current_month AND YEAR(date) = $current_year";
                                    
                                    $result_current_income = $conn->query($sql_current_income);
                                    $result_current_expense = $conn->query($sql_current_expense);
                                    
                                    $current_income = $result_current_income->fetch_assoc()['total'];
                                    $current_expense = $result_current_expense->fetch_assoc()['total'];
                                    $current_savings = $current_income - $current_expense;
                                    
                                    // Ottieni i dati per il mese precedente
                                    $sql_prev_income = "SELECT COALESCE(SUM(amount), 0) as total FROM transactions 
                                                      WHERE type = 'entrata' AND MONTH(date) = $prev_month AND YEAR(date) = $prev_year";
                                    $sql_prev_expense = "SELECT COALESCE(SUM(amount), 0) as total FROM transactions 
                                                       WHERE type = 'uscita' AND MONTH(date) = $prev_month AND YEAR(date) = $prev_year";
                                    
                                    $result_prev_income = $conn->query($sql_prev_income);
                                    $result_prev_expense = $conn->query($sql_prev_expense);
                                    
                                    $prev_income = $result_prev_income->fetch_assoc()['total'];
                                    $prev_expense = $result_prev_expense->fetch_assoc()['total'];
                                    $prev_savings = $prev_income - $prev_expense;
                                    
                                    // Calcola variazioni percentuali
                                    $income_change_pct = ($prev_income > 0) ? (($current_income - $prev_income) / $prev_income) * 100 : 0;
                                    $expense_change_pct = ($prev_expense > 0) ? (($current_expense - $prev_expense) / $prev_expense) * 100 : 0;
                                    $savings_change_pct = ($prev_savings > 0) ? (($current_savings - $prev_savings) / $prev_savings) * 100 : 0;
                                    
                                    // Formatta le variazioni percentuali
                                    $income_change_class = $income_change_pct >= 0 ? 'text-success' : 'text-danger';
                                    $income_change_icon = $income_change_pct >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
                                    $income_change_formatted = number_format(abs($income_change_pct), 1) . '%';
                                    
                                    $expense_change_class = $expense_change_pct <= 0 ? 'text-success' : 'text-danger';
                                    $expense_change_icon = $expense_change_pct <= 0 ? 'fa-arrow-down' : 'fa-arrow-up';
                                    $expense_change_formatted = number_format(abs($expense_change_pct), 1) . '%';
                                    
                                    $savings_change_class = $savings_change_pct >= 0 ? 'text-success' : 'text-danger';
                                    $savings_change_icon = $savings_change_pct >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
                                    $savings_change_formatted = number_format(abs($savings_change_pct), 1) . '%';
                                    
                                    // Nomi dei mesi
                                    $current_month_name = get_month_name($current_month);
                                    $prev_month_name = get_month_name($prev_month);
                                    ?>
                                    
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="card">
                                                <div class="card-header bg-light">
                                                    <h3 class="card-title">Entrate</h3>
                                                </div>
                                                <div class="card-body">
                                                    <table class="table">
                                                        <tr>
                                                            <td><?php echo $current_month_name . ' ' . $current_year; ?></td>
                                                            <td class="text-right"><?php echo format_currency($current_income); ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td><?php echo $prev_month_name . ' ' . $prev_year; ?></td>
                                                            <td class="text-right"><?php echo format_currency($prev_income); ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td>Variazione</td>
                                                            <td class="text-right <?php echo $income_change_class; ?>">
                                                                <i class="fas <?php echo $income_change_icon; ?>"></i>
                                                                <?php echo $income_change_formatted; ?>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card">
                                                <div class="card-header bg-light">
                                                    <h3 class="card-title">Uscite</h3>
                                                </div>
                                                <div class="card-body">
                                                    <table class="table">
                                                        <tr>
                                                            <td><?php echo $current_month_name . ' ' . $current_year; ?></td>
                                                            <td class="text-right"><?php echo format_currency($current_expense); ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td><?php echo $prev_month_name . ' ' . $prev_year; ?></td>
                                                            <td class="text-right"><?php echo format_currency($prev_expense); ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td>Variazione</td>
                                                            <td class="text-right <?php echo $expense_change_class; ?>">
                                                                <i class="fas <?php echo $expense_change_icon; ?>"></i>
                                                                <?php echo $expense_change_formatted; ?>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card">
                                                <div class="card-header bg-light">
                                                    <h3 class="card-title">Risparmio</h3>
                                                </div>
                                                <div class="card-body">
                                                    <table class="table">
                                                        <tr>
                                                            <td><?php echo $current_month_name . ' ' . $current_year; ?></td>
                                                            <td class="text-right"><?php echo format_currency($current_savings); ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td><?php echo $prev_month_name . ' ' . $prev_year; ?></td>
                                                            <td class="text-right"><?php echo format_currency($prev_savings); ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td>Variazione</td>
                                                            <td class="text-right <?php echo $savings_change_class; ?>">
                                                                <i class="fas <?php echo $savings_change_icon; ?>"></i>
                                                                <?php echo $savings_change_formatted; ?>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php
    } // Fine report trend
    ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inizializza i grafici in base al tipo di report
    <?php if ($view == 'monthly') { ?>
    // Grafico spese per categoria
    initExpenseCategoryChart();
    
    // Grafico andamento giornaliero
    initDailyChart();
    <?php } else if ($view == 'yearly') { ?>
    // Grafico andamento mensile
    initMonthlyTrendChart();
    
    // Grafico entrate per categoria
    initIncomeCategoryChart();
    
    // Grafico uscite per categoria
    initYearlyExpenseCategoryChart();
    <?php } else if ($view == 'category' && !empty($category)) { ?>
    // Grafico andamento mensile per categoria
    initCategoryMonthlyChart();
    <?php } else if ($view == 'trend') { ?>
    // Grafico trend annuale
    initYearlyTrendChart();
    <?php } ?>
});

// Grafico spese per categoria (report mensile)
function initExpenseCategoryChart() {
    const ctx = document.getElementById('expense-category-chart');
    
    if (!ctx) return;
    
    fetch('get_chart_data.php?type=expense_categories&month=<?php echo $month; ?>&year=<?php echo $year; ?>')
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            console.error(data.error);
            return;
        }
        
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: data.categories,
                datasets: [{
                    data: data.amounts,
                    backgroundColor: data.colors
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                let value = context.raw || 0;
                                return label + ': ' + new Intl.NumberFormat('it-IT', {
                                    style: 'currency',
                                    currency: 'EUR'
                                }).format(value);
                            }
                        }
                    }
                }
            }
        });
    })
    .catch(error => {
        console.error('Error loading chart data:', error);
    });
}

// Grafico andamento giornaliero (report mensile)
function initDailyChart() {
    // Implementazione del grafico andamento giornaliero
    // (simile alle altre funzioni di grafico, ma con dati giornalieri)
}

// Grafico andamento mensile (report annuale)
function initMonthlyTrendChart() {
    // Implementazione del grafico andamento mensile
    // (simile alle altre funzioni di grafico, ma con dati mensili)
}

// Grafico entrate per categoria (report annuale)
function initIncomeCategoryChart() {
    // Implementazione del grafico entrate per categoria
    // (simile al grafico spese per categoria, ma per entrate)
}

// Grafico uscite per categoria (report annuale)
function initYearlyExpenseCategoryChart() {
    // Implementazione del grafico uscite per categoria
    // (simile al grafico spese per categoria, ma per l'anno intero)
}

// Grafico andamento mensile per categoria (report per categoria)
function initCategoryMonthlyChart() {
    // Implementazione del grafico andamento mensile per categoria
    // (simile alle altre funzioni di grafico, ma con dati di una categoria specifica)
}

// Grafico trend annuale (report trend)
function initYearlyTrendChart() {
    // Implementazione del grafico trend annuale
    // (simile alle altre funzioni di grafico, ma con dati degli ultimi 12 mesi)
}
</script>

<?php
// --- ESPORTAZIONE DATI REPORT ---
if (isset($_GET['export'])) {
    // Disabilita output buffer e header HTML
    if (ob_get_level()) ob_end_clean();
    header_remove('Content-Encoding');
    header_remove('Content-Length');
    
    $export_type = $_GET['export'];
    $export_view = $_GET['view'] ?? 'monthly';
    $export_month = intval($_GET['month'] ?? date('m'));
    $export_year = intval($_GET['year'] ?? date('Y'));
    $export_category = $_GET['category'] ?? '';

    // Costruisci la query in base al filtro
    $where = "WHERE 1=1";
    if ($export_view == 'monthly') {
        $where .= " AND MONTH(date) = $export_month AND YEAR(date) = $export_year";
    } elseif ($export_view == 'yearly') {
        $where .= " AND YEAR(date) = $export_year";
    } elseif ($export_view == 'category') {
        $where .= " AND YEAR(date) = $export_year";
        if (!empty($export_category)) {
            $where .= " AND category = '" . $conn->real_escape_string($export_category) . "'";
        }
    }
    $sql = "SELECT date, description, category, type, amount FROM transactions $where ORDER BY date DESC";
    $result = $conn->query($sql);
    $rows = [];
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    // Esporta CSV
    if ($export_type == 'csv') {
        if (!$exports_writable) {
            echo '<div class="alert alert-danger">Errore: la cartella <code>exports</code> non è scrivibile. Impossibile esportare il file.</div>';
            exit;
        }
        $out = fopen('php://output', 'w');
        if (!$out) {
            echo '<div class="alert alert-danger">Errore: impossibile aprire lo stream di output per il CSV.</div>';
            exit;
        }
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="report.csv"');
        fputcsv($out, ['Data','Descrizione','Categoria','Tipo','Importo']);
        foreach ($rows as $r) {
            fputcsv($out, [$r['date'],$r['description'],$r['category'],$r['type'],$r['amount']]);
        }
        fclose($out);
        exit;
    }
    // Esporta Excel (compatibile con Excel, ma formato CSV con estensione xls)
    if ($export_type == 'excel') {
        if (!$exports_writable) {
            echo '<div class="alert alert-danger">Errore: la cartella <code>exports</code> non è scrivibile. Impossibile esportare il file.</div>';
            exit;
        }
        $out = fopen('php://output', 'w');
        if (!$out) {
            echo '<div class="alert alert-danger">Errore: impossibile aprire lo stream di output per Excel.</div>';
            exit;
        }
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="report.xls"');
        fputcsv($out, ['Data','Descrizione','Categoria','Tipo','Importo'], "\t");
        foreach ($rows as $r) {
            fputcsv($out, [$r['date'],$r['description'],$r['category'],$r['type'],$r['amount']], "\t");
        }
        fclose($out);
        exit;
    }
    // Esporta PDF (richiede FPDF)
    if ($export_type == 'pdf') {
        if (!$exports_writable) {
            echo '<div class="alert alert-danger">Errore: la cartella <code>exports</code> non è scrivibile. Impossibile esportare il file PDF.</div>';
            exit;
        }
        require_once __DIR__ . '/vendor/autoload.php';
        if (!class_exists('FPDF')) {
            echo '<div class="alert alert-danger">Libreria FPDF non installata. Installa con composer require setasign/fpdf</div>';
            exit;
        }
        class PDFReport extends FPDF {
            function Header() {
                // Logo e intestazione
                $this->SetFillColor(0,123,255);
                $this->Rect(0,0,210,30,'F');
                $this->Image(__DIR__.'/assets/img/logo-192.png',10,7,16);
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
                // Tipo con colore
                if ($row['type'] == 'entrata') {
                    $this->SetTextColor(40,167,69); // verde
                } else {
                    $this->SetTextColor(220,53,69); // rosso
                }
                $this->Cell(20,9,ucfirst($row['type']),1,0,'C');
                $this->SetTextColor(0,0,0);
                $this->Cell(30,9,number_format($row['amount'],2,',','.'),1,1,'R');
            }
        }
        $pdf = new PDFReport('P','mm','A4');
        $pdf->AliasNbPages();
        $pdf->AddPage();
        $pdf->Ln(10);
        // Info riepilogo
        $pdf->SetFont('Arial','B',12);
        $pdf->SetTextColor(0,123,255);
        $pdf->Cell(0,8,'Periodo: '.($export_view=='monthly' ? get_month_name($export_month).' '.$export_year : ($export_view=='yearly' ? $export_year : '')),0,1,'L');
        if ($export_view=='category' && !empty($export_category)) {
            $pdf->Cell(0,8,'Categoria: '.utf8_decode($export_category),0,1,'L');
        }
        $pdf->Ln(2);
        // Tabella
        $pdf->TableHeader();
        if (count($rows) > 0) {
            foreach ($rows as $r) {
                $pdf->TableRow($r);
            }
        } else {
            $pdf->SetFont('Arial','I',11);
            $pdf->Cell(180,10,'Nessun dato disponibile per il periodo selezionato.',1,1,'C');
        }
        // Totali
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
        $pdf->Output('D','report.pdf');
        exit;
    }
}
?>

<?php
// --- CRONOLOGIA ESPORTAZIONI ---
$phone = $_SESSION['user_phone'];
$stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
$stmt->bind_param('s', $phone);
$stmt->execute();
$stmt->bind_result($user_id);
$stmt->fetch();
$stmt->close();

$sql_exports = "SELECT * FROM exported_reports WHERE user_id = ? ORDER BY created_at DESC LIMIT 30";
$stmt = $conn->prepare($sql_exports);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

?>
<div class="card mt-4">
    <div class="card-header bg-info text-white">
        <h3 class="card-title"><i class="fas fa-history"></i> Cronologia Esportazioni</h3>
    </div>
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Periodo</th>
                    <th>Categoria</th>
                    <th>Data esportazione</th>
                    <th>File</th>
                </tr>
            </thead>
            <tbody>
            <?php
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $tipo = ucfirst($row['export_type']);
                    $periodo = '';
                    if ($row['export_type'] == 'daily') {
                        $periodo = date('d/m/Y', strtotime($row['export_date']));
                    } elseif ($row['export_type'] == 'monthly') {
                        $periodo = get_month_name($row['export_month']) . ' ' . $row['export_year'];
                    } elseif ($row['export_type'] == 'yearly') {
                        $periodo = $row['export_year'];
                    }
                    $categoria = $row['export_category'] ?? '';
                    $data_export = date('d/m/Y H:i', strtotime($row['created_at']));
                    $file = $row['download_url'];
                    echo "<tr>";
                    echo "<td>$tipo</td>";
                    echo "<td>$periodo</td>";
                    echo "<td>".htmlspecialchars($categoria)."</td>";
                    echo "<td>$data_export</td>";
                    echo "<td><a href='$file' class='btn btn-sm btn-outline-primary' target='_blank'><i class='fas fa-download'></i> Scarica</a></td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='5' class='text-center'>Nessuna esportazione trovata</td></tr>";
            }
            ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>

<?php
// --- DIAGNOSTICA CARTELLA EXPORTS ---
$exports_dir = __DIR__ . '/exports';
$exports_writable = is_dir($exports_dir) && is_writable($exports_dir);
if (!$exports_writable) {
    echo '<div class="alert alert-danger"><b>Attenzione:</b> La cartella <code>exports</code> non esiste o non è scrivibile. I file di esportazione non potranno essere creati.<br>Controlla i permessi della cartella o ricreala manualmente.</div>';
}
?>
