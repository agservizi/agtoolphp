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
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AGTool Finance - Gestione Finanze Personali</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- AdminLTE Style -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    
    <!-- Google Font: Source Sans Pro -->
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
    
    <!-- Custom Style -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="index" class="nav-link">Home</a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="#" class="nav-link" data-toggle="modal" data-target="#addTransactionModal">Nuova Transazione</a>
                </li>
            </ul>
            <!-- Pulsante Logout a destra -->
            <ul class="navbar-nav ml-auto">
                <?php
                if (isset($_SESSION['user_phone'])) {
                    $phone = $_SESSION['user_phone'];
                    $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
                    $stmt->bind_param('s', $phone);
                    $stmt->execute();
                    $stmt->bind_result($user_id);
                    $stmt->fetch();
                    $stmt->close();
                    $notif_stmt = $conn->prepare("SELECT title, message, status, scheduled_at FROM notifications WHERE user_id = ? ORDER BY scheduled_at DESC, created_at DESC LIMIT 5");
                    $notif_stmt->bind_param('i', $user_id);
                    $notif_stmt->execute();
                    $notif_result = $notif_stmt->get_result();
                    $unread_count = 0;
                    $notifiche = [];
                    while($row = $notif_result->fetch_assoc()) {
                        if ($row['status'] === 'pending') $unread_count++;
                        $notifiche[] = $row;
                    }
                    $notif_stmt->close();
                ?>
                <li class="nav-item dropdown">
                    <a class="nav-link" data-toggle="dropdown" href="#" aria-label="Notifiche">
                        <i class="fas fa-bell"></i>
                        <?php if ($unread_count > 0): ?>
                            <span class="badge badge-warning navbar-badge"><?php echo $unread_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right p-0" style="min-width:320px;max-width:350px;">
                        <span class="dropdown-header"><?php echo $unread_count > 0 ? $unread_count.' nuove notifiche' : 'Nessuna nuova notifica'; ?></span>
                        <div class="dropdown-divider"></div>
                        <?php if (count($notifiche) > 0): foreach($notifiche as $n): ?>
                            <a href="notifications" class="dropdown-item">
                                <i class="fas fa-info-circle mr-2"></i> <strong><?php echo htmlspecialchars($n['title']); ?></strong><br>
                                <span style="font-size:0.95em;color:#666;"><?php echo htmlspecialchars($n['message']); ?></span>
                                <span class="float-right text-muted text-sm"><?php echo $n['scheduled_at'] ? date('d/m/Y', strtotime($n['scheduled_at'])) : ''; ?></span>
                            </a>
                            <div class="dropdown-divider"></div>
                        <?php endforeach; else: ?>
                            <span class="dropdown-item text-center text-muted">Nessuna notifica recente</span>
                            <div class="dropdown-divider"></div>
                        <?php endif; ?>
                        <a href="notifications" class="dropdown-item dropdown-footer">Vedi tutte le notifiche</a>
                    </div>
                </li>
                <?php } ?>
                <li class="nav-item">
                    <a href="logout" class="nav-link text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </li>
            </ul>
        </nav>
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <!-- Brand Logo -->
            <a href="index" class="brand-link">
                <i class="fas fa-wallet ml-3 mr-2"></i>
                <span class="brand-text font-weight-light">AGTool Finance</span>
            </a>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Sidebar Menu -->
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                        <li class="nav-item">
                            <a href="index" class="nav-link active">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="transactions" class="nav-link">
                                <i class="nav-icon fas fa-exchange-alt"></i>
                                <p>Transazioni</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="recurring" class="nav-link">
                                <i class="nav-icon fas fa-redo"></i>
                                <p>Ricorrenti</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="categories" class="nav-link">
                                <i class="nav-icon fas fa-tags"></i>
                                <p>Categorie</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="savings" class="nav-link">
                                <i class="nav-icon fas fa-piggy-bank"></i>
                                <p>Obiettivi di Risparmio</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="reports" class="nav-link">
                                <i class="nav-icon fas fa-chart-pie"></i>
                                <p>Reportistica</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="advisor" class="nav-link">
                                <i class="nav-icon fas fa-lightbulb"></i>
                                <p>Consulente</p>
                            </a>
                        </li>
                        <!-- Nuove voci di menu per Notifiche e Impostazioni -->
                        <li class="nav-item">
                            <a href="notifications" class="nav-link">
                                <i class="nav-icon fas fa-bell"></i>
                                <p>Notifiche</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="settings" class="nav-link">
                                <i class="nav-icon fas fa-cog"></i>
                                <p>Impostazioni</p>
                            </a>
                        </li>
                    </ul>
                </nav>
                <!-- /.sidebar-menu -->
            </div>
            <!-- /.sidebar -->
        </aside>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0 text-dark">Dashboard</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="#">Home</a></li>
                                <li class="breadcrumb-item active">Dashboard</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.content-header -->

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <!-- Statistiche principali -->
                    <div class="row">
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <?php
                                    require_once 'inc/config.php';
                                    
                                    // Bilancio totale
                                    $income = $conn->query("SELECT COALESCE(SUM(amount),0) as total FROM transactions WHERE type='entrata'")->fetch_assoc()['total'];
                                    $expense = $conn->query("SELECT COALESCE(SUM(amount),0) as total FROM transactions WHERE type='uscita'")->fetch_assoc()['total'];
                                    $balance = $income - $expense;
                                    ?>
                                    <h3><?php echo format_currency($balance); ?></h3>
                                    <p>Bilancio Totale</p>
                                </div>
                                <div class="icon"><i class="fas fa-wallet"></i></div>
                                <a href="transactions" class="small-box-footer">Maggiori dettagli <i class="fas fa-arrow-circle-right"></i></a>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3><?php echo format_currency($income); ?></h3>
                                    <p>Entrate Totali</p>
                                </div>
                                <div class="icon"><i class="fas fa-arrow-up"></i></div>
                                <a href="transactions?type=entrata" class="small-box-footer">Maggiori dettagli <i class="fas fa-arrow-circle-right"></i></a>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3><?php echo format_currency($expense); ?></h3>
                                    <p>Uscite Totali</p>
                                </div>
                                <div class="icon"><i class="fas fa-arrow-down"></i></div>
                                <a href="transactions?type=uscita" class="small-box-footer">Maggiori dettagli <i class="fas fa-arrow-circle-right"></i></a>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <?php
                                    $m = date('m'); $y = date('Y');
                                    $monthly_income = $conn->query("SELECT COALESCE(SUM(amount),0) as total FROM transactions WHERE type='entrata' AND MONTH(date)=$m AND YEAR(date)=$y")->fetch_assoc()['total'];
                                    $monthly_expense = $conn->query("SELECT COALESCE(SUM(amount),0) as total FROM transactions WHERE type='uscita' AND MONTH(date)=$m AND YEAR(date)=$y")->fetch_assoc()['total'];
                                    $monthly_savings = $monthly_income - $monthly_expense;
                                    ?>
                                    <h3><?php echo format_currency($monthly_savings); ?></h3>
                                    <p>Risparmio del Mese</p>
                                </div>
                                <div class="icon"><i class="fas fa-piggy-bank"></i></div>
                                <a href="reports?view=monthly" class="small-box-footer">Maggiori dettagli <i class="fas fa-arrow-circle-right"></i></a>
                            </div>
                        </div>
                    </div>
                    <!-- /.row -->

                    <div class="row">
                        <!-- Colonna sinistra -->
                        <section class="col-lg-7 connectedSortable">
                            <!-- Transazioni recenti -->
                            <div class="card">
                                <div class="card-header border-0">
                                    <h3 class="card-title">Transazioni Recenti</h3>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-valign-middle">
                                            <thead>
                                                <tr>
                                                    <th>Data</th>
                                                    <th>Descrizione</th>
                                                    <th>Categoria</th>
                                                    <th>Importo</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $sql = "SELECT t.*, c.name as category_name, c.color FROM transactions t LEFT JOIN categories c ON t.category = c.name AND t.type = c.type ORDER BY t.date DESC LIMIT 10";
                                                $result = $conn->query($sql);
                                                if ($result && $result->num_rows > 0) {
                                                    while($row = $result->fetch_assoc()) {
                                                        $color_class = ($row['type'] == 'entrata') ? 'text-success' : 'text-danger';
                                                        $amount_sign = ($row['type'] == 'entrata') ? '+' : '-';
                                                        $amount = $amount_sign . ' ' . format_currency($row['amount']);
                                                        $date = format_date($row['date']);
                                                        $category_color = $row['color'] ?? '#3498db';
                                                        $category_name = $row['category_name'] ?? $row['category'];
                                                        echo "<tr>";
                                                        echo "<td>{$date}</td>";
                                                        echo "<td>{$row['description']}</td>";
                                                        echo "<td><span class='badge' style='background-color: {$category_color}'>{$category_name}</span></td>";
                                                        echo "<td class='{$color_class}'>{$amount}</td>";
                                                        echo "</tr>";
                                                    }
                                                } else {
                                                    echo "<tr><td colspan='4' class='text-center'>Nessuna transazione trovata</td></tr>";
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="card-footer text-center">
                                    <a href="transactions" class="uppercase">Vedi Tutte le Transazioni</a>
                                </div>
                            </div>
                            <!-- /.card -->

                            <!-- Consigliere Finanziario Card -->
                            <div class="card direct-chat direct-chat-primary">
                                <div class="card-header">
                                    <h3 class="card-title">Consulente</h3>
                                </div>
                                <div class="card-body" id="financial-advisor">
                                    <div class="direct-chat-messages" id="advisor-messages">
                                        <div class="direct-chat-msg">
                                            <div class="direct-chat-infos clearfix">
                                                <span class="direct-chat-name float-left">Consulente</span>
                                            </div>
                                            <div class="direct-chat-img bg-info rounded-circle d-flex justify-content-center align-items-center">
                                                <i class="fas fa-robot"></i>
                                            </div>
                                            <div class="direct-chat-text">
                                                <?php
                                                $sql = "SELECT * FROM financial_tips WHERE is_active = 1 ORDER BY RAND() LIMIT 1";
                                                $result = $conn->query($sql);
                                                if ($result && $result->num_rows > 0) {
                                                    $tip = $result->fetch_assoc();
                                                    echo "<strong>{$tip['title']}</strong><br>";
                                                    echo $tip['description'];
                                                } else {
                                                    echo "Benvenuto in AGTool Finance! Inizia a registrare le tue transazioni per ricevere consigli finanziari personalizzati.";
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <div class="input-group">
                                        <input type="text" id="advisor-question" name="message" placeholder="Fai una domanda al consulente..." class="form-control">
                                        <span class="input-group-append">
                                            <button type="button" id="ask-advisor" class="btn btn-primary">Invia</button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <!-- /.card -->
                        </section>
                        <!-- Colonna destra -->
                        <section class="col-lg-5 connectedSortable">
                            <!-- Grafico Entrate vs Uscite -->
                            <div class="card">
                                <div class="card-header border-0">
                                    <h3 class="card-title">Entrate vs Uscite</h3>
                                </div>
                                <div class="card-body">
                                    <div class="position-relative mb-4">
                                        <canvas id="income-expense-chart" height="250"></canvas>
                                    </div>
                                </div>
                            </div>
                            <!-- /.card -->

                            <!-- Obiettivi di risparmio -->
                            <div class="card">
                                <div class="card-header border-0">
                                    <h3 class="card-title">Obiettivi di risparmio</h3>
                                    <div class="card-tools">
                                        <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addGoalModal">
                                            <i class="fas fa-plus"></i> Nuovo obiettivo
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <?php
                                    $sql = "SELECT * FROM savings_goals ORDER BY target_date ASC";
                                    $result = $conn->query($sql);
                                    if ($result && $result->num_rows > 0) {
                                        while($row = $result->fetch_assoc()) {
                                            $percentage = ($row['target_amount'] > 0) ? ($row['current_amount'] / $row['target_amount']) * 100 : 0;
                                            $percentage = min(100, $percentage);
                                            $date_text = $row['target_date'] ? 'entro il ' . format_date($row['target_date']) : '';
                                    ?>
                                    <div class="p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h4 class="m-0"><?php echo $row['name']; ?></h4>
                                            <span><?php echo format_currency($row['current_amount']); ?> / <?php echo format_currency($row['target_amount']); ?></span>
                                        </div>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $percentage; ?>%" aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                                <?php echo round($percentage, 1); ?>%
                                            </div>
                                        </div>
                                        <small class="text-muted"><?php echo $date_text; ?></small>
                                    </div>
                                    <div class="dropdown-divider"></div>
                                    <?php
                                        }
                                    } else {
                                        echo '<div class="p-3 text-center">Nessun obiettivo di risparmio. Aggiungi il tuo primo obiettivo!</div>';
                                    }
                                    ?>
                                </div>
                            </div>
                            <!-- /.card -->

                            <!-- Simulatore di risparmio -->
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Simulatore di Risparmio</h3>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="saving-amount">Importo da risparmiare:</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">€</span>
                                            </div>
                                            <input type="number" id="saving-amount" class="form-control" value="100">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="saving-frequency">Frequenza:</label>
                                        <select id="saving-frequency" class="form-control">
                                            <option value="daily">Giornaliera</option>
                                            <option value="weekly">Settimanale</option>
                                            <option value="monthly" selected>Mensile</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="saving-period">Periodo (mesi):</label>
                                        <input type="range" id="saving-period" class="form-control" min="1" max="60" value="12" oninput="document.getElementById('period-value').innerHTML = this.value">
                                        <span id="period-value">12</span> mesi
                                    </div>
                                    <button type="button" id="calculate-savings" class="btn btn-primary btn-block">Calcola</button>
                                    
                                    <div id="savings-result" class="mt-3 text-center" style="display: none;">
                                        <h4>Risultato:</h4>
                                        <div class="alert alert-success">
                                            Risparmiando <span id="result-amount"></span>€ <span id="result-frequency"></span>,
                                            in <span id="result-period"></span> mesi avrai risparmiato:
                                            <h3 id="result-total"></h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- /.card -->
                        </section>
                    </div>
                </div><!-- /.container-fluid -->
            </section>
            <!-- /.content -->
        </div>
        <!-- /.content-wrapper -->

        <footer class="main-footer">
            <strong>AGTool Finance &copy; 2025</strong> - Gestione Finanze Personali
            <div class="float-right d-none d-sm-inline-block">
                <b>Versione</b> 1.0.0
            </div>
        </footer>
    </div>
    <!-- ./wrapper -->

    <!-- Modal Aggiungi Transazione -->
    <div class="modal fade" id="addTransactionModal" tabindex="-1" role="dialog" aria-labelledby="addTransactionModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTransactionModalLabel">Aggiungi Transazione</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="process_transaction.php" method="post" id="transaction-form">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="transaction-type">Tipo</label>
                            <select id="transaction-type" name="type" class="form-control" required>
                                <option value="entrata">Entrata</option>
                                <option value="uscita">Uscita</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="transaction-amount">Importo</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">€</span>
                                </div>
                                <input type="number" step="0.01" min="0.01" id="transaction-amount" name="amount" class="form-control" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="transaction-description">Descrizione</label>
                            <input type="text" id="transaction-description" name="description" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="transaction-category">Categoria</label>
                            <select id="transaction-category" name="category" class="form-control" required>
                                <!-- Le categorie saranno caricate dinamicamente in base al tipo selezionato -->
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="transaction-date">Data</label>
                            <input type="date" id="transaction-date" name="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
                        <button type="submit" class="btn btn-primary">Salva</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Aggiungi Obiettivo -->
    <div class="modal fade" id="addGoalModal" tabindex="-1" role="dialog" aria-labelledby="addGoalModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addGoalModalLabel">Nuovo Obiettivo di Risparmio</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="process_goal" method="post" id="goal-form">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="goal-name">Nome dell'obiettivo</label>
                            <input type="text" id="goal-name" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="goal-amount">Importo obiettivo</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">€</span>
                                </div>
                                <input type="number" step="0.01" min="0.01" id="goal-amount" name="target_amount" class="form-control" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="goal-current">Importo già risparmiato (opzionale)</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">€</span>
                                </div>
                                <input type="number" step="0.01" min="0" id="goal-current" name="current_amount" class="form-control" value="0">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="goal-date">Data obiettivo (opzionale)</label>
                            <input type="date" id="goal-date" name="target_date" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
                        <button type="submit" class="btn btn-primary">Salva</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap 4 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE App -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    <!-- ChartJS -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
</body>
</html>
