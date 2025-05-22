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

// Gestisci l'eliminazione delle transazioni
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    $sql = "DELETE FROM transactions WHERE id = $id";
    
    if ($conn->query($sql) === TRUE) {
        header("Location: transactions.php?status=success&message=" . urlencode("Transazione eliminata con successo"));
    } else {
        header("Location: transactions.php?status=error&message=" . urlencode("Errore nell'eliminazione della transazione: " . $conn->error));
    }
    exit;
}

// Filtri per tipo di transazione e intervallo di date
$filter_type = isset($_GET['type']) ? clean_input($_GET['type']) : '';
$filter_start_date = isset($_GET['start_date']) ? clean_input($_GET['start_date']) : date('Y-m-01'); // Inizio del mese corrente
$filter_end_date = isset($_GET['end_date']) ? clean_input($_GET['end_date']) : date('Y-m-t'); // Fine del mese corrente
$filter_category = isset($_GET['category']) ? clean_input($_GET['category']) : '';

// Includi l'header
include 'header.php';
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark">Transazioni</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index">Home</a></li>
                    <li class="breadcrumb-item active">Transazioni</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <!-- Filtri -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Filtri</h3>
        </div>
        <div class="card-body">
            <form method="get" class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Tipo</label>
                        <select name="type" class="form-control">
                            <option value="" <?php echo $filter_type == '' ? 'selected' : ''; ?>>Tutti</option>
                            <option value="entrata" <?php echo $filter_type == 'entrata' ? 'selected' : ''; ?>>Entrate</option>
                            <option value="uscita" <?php echo $filter_type == 'uscita' ? 'selected' : ''; ?>>Uscite</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Categoria</label>
                        <select name="category" class="form-control">
                            <option value="">Tutte</option>
                            <?php
                            // Ottieni tutte le categorie distinte presenti nelle transazioni
                            $sql_categories = "SELECT DISTINCT category FROM transactions";
                            $result_categories = $conn->query($sql_categories);
                            
                            if ($result_categories->num_rows > 0) {
                                while($row = $result_categories->fetch_assoc()) {
                                    $selected = ($filter_category == $row['category']) ? 'selected' : '';
                                    echo "<option value='{$row['category']}' $selected>{$row['category']}</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Data Inizio</label>
                        <input type="date" name="start_date" class="form-control" value="<?php echo $filter_start_date; ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Data Fine</label>
                        <input type="date" name="end_date" class="form-control" value="<?php echo $filter_end_date; ?>">
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Filtra</button>
                    <a href="transactions.php" class="btn btn-default">Reimposta</a>
                    <button type="button" class="btn btn-success float-right" data-toggle="modal" data-target="#addTransactionModal">
                        <i class="fas fa-plus"></i> Nuova Transazione
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Riepilogo -->
    <div class="row">
        <div class="col-md-4">
            <div class="info-box">
                <span class="info-box-icon bg-success"><i class="fas fa-arrow-up"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Entrate Totali</span>
                    <?php
                    // Query per calcolare le entrate totali nel periodo filtrato
                    $where_clause = "WHERE type = 'entrata'";
                    
                    if (!empty($filter_start_date)) {
                        $where_clause .= " AND date >= '$filter_start_date'";
                    }
                    
                    if (!empty($filter_end_date)) {
                        $where_clause .= " AND date <= '$filter_end_date'";
                    }
                    
                    if (!empty($filter_category)) {
                        $where_clause .= " AND category = '$filter_category'";
                    }
                    
                    $sql_income = "SELECT COALESCE(SUM(amount), 0) as total FROM transactions $where_clause";
                    $result_income = $conn->query($sql_income);
                    $income = $result_income->fetch_assoc()['total'];
                    ?>
                    <span class="info-box-number"><?php echo format_currency($income); ?></span>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="info-box">
                <span class="info-box-icon bg-danger"><i class="fas fa-arrow-down"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Uscite Totali</span>
                    <?php
                    // Query per calcolare le uscite totali nel periodo filtrato
                    $where_clause = "WHERE type = 'uscita'";
                    
                    if (!empty($filter_start_date)) {
                        $where_clause .= " AND date >= '$filter_start_date'";
                    }
                    
                    if (!empty($filter_end_date)) {
                        $where_clause .= " AND date <= '$filter_end_date'";
                    }
                    
                    if (!empty($filter_category)) {
                        $where_clause .= " AND category = '$filter_category'";
                    }
                    
                    $sql_expense = "SELECT COALESCE(SUM(amount), 0) as total FROM transactions $where_clause";
                    $result_expense = $conn->query($sql_expense);
                    $expense = $result_expense->fetch_assoc()['total'];
                    ?>
                    <span class="info-box-number"><?php echo format_currency($expense); ?></span>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="info-box">
                <span class="info-box-icon bg-info"><i class="fas fa-wallet"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Bilancio Periodo</span>
                    <?php $balance = $income - $expense; ?>
                    <span class="info-box-number"><?php echo format_currency($balance); ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tabella Transazioni -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Elenco Transazioni</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Tipo</th>
                            <th>Descrizione</th>
                            <th>Categoria</th>
                            <th class="text-right">Importo</th>
                            <th class="text-right">Azioni</th>
                        </tr>
                    </thead>
                    <tbody id="transactions-tbody">
                        <?php
                        // Costruisci la query per le transazioni con i filtri applicati
                        $where = [];
                        
                        if (!empty($filter_type)) {
                            $where[] = "type = '$filter_type'";
                        }
                        
                        if (!empty($filter_category)) {
                            $where[] = "category = '$filter_category'";
                        }
                        
                        if (!empty($filter_start_date)) {
                            $where[] = "date >= '$filter_start_date'";
                        }
                        
                        if (!empty($filter_end_date)) {
                            $where[] = "date <= '$filter_end_date'";
                        }
                        
                        $where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
                        
                        $sql = "SELECT t.*, c.color 
                                FROM transactions t
                                LEFT JOIN categories c ON t.category = c.name AND t.type = c.type
                                $where_clause
                                ORDER BY t.date DESC";
                        
                        $result = $conn->query($sql);
                        
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                $color_class = ($row['type'] == 'entrata') ? 'text-success' : 'text-danger';
                                $amount_sign = ($row['type'] == 'entrata') ? '+' : '-';
                                $amount = $amount_sign . ' ' . format_currency($row['amount']);
                                $date = format_date($row['date']);
                                $category_color = $row['color'] ?? '#3498db';
                                $type_icon = ($row['type'] == 'entrata') ? 'fa-arrow-up' : 'fa-arrow-down';
                                $type_label = ($row['type'] == 'entrata') ? 'Entrata' : 'Uscita';
                                
                                echo "<tr>";
                                echo "<td>{$date}</td>";
                                echo "<td><i class='fas {$type_icon}'></i> {$type_label}</td>";
                                echo "<td>{$row['description']}</td>";
                                echo "<td><span class='badge' style='background-color: {$category_color}'>{$row['category']}</span></td>";
                                echo "<td class='{$color_class} text-right'>{$amount}</td>";
                                echo "<td class='text-right'>";
                                echo "<a href='edit_transaction?id={$row['id']}' class='btn btn-sm btn-info'>Modifica</a> ";
                                echo "<a href='transactions?delete={$row['id']}' class='btn btn-sm btn-danger' onclick='return confirm(\\'Sei sicuro di voler eliminare questa transazione?\\')'>Elimina</a>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' class='text-center'>Nessuna transazione trovata</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
<script>document.addEventListener('DOMContentLoaded',function(){showToast('success','Transazione salvata con successo!');});</script>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
<script>document.addEventListener('DOMContentLoaded',function(){showToast('error','Errore nel salvataggio della transazione!');});</script>
<?php endif; ?>

<?php
// Gestisci richiesta AJAX per aggiornamento tabella transazioni
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    ob_start();
    // Costruisci la query per le transazioni con i filtri applicati
    $where = [];
    if (!empty($filter_type)) {
        $where[] = "type = '$filter_type'";
    }
    if (!empty($filter_category)) {
        $where[] = "category = '$filter_category'";
    }
    if (!empty($filter_start_date)) {
        $where[] = "date >= '$filter_start_date'";
    }
    if (!empty($filter_end_date)) {
        $where[] = "date <= '$filter_end_date'";
    }
    $where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    $sql = "SELECT t.*, c.color 
            FROM transactions t
            LEFT JOIN categories c ON t.category = c.name AND t.type = c.type
            $where_clause
            ORDER BY t.date DESC";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $color_class = ($row['type'] == 'entrata') ? 'text-success' : 'text-danger';
            $amount_sign = ($row['type'] == 'entrata') ? '+' : '-';
            $amount = $amount_sign . ' ' . format_currency($row['amount']);
            $date = format_date($row['date']);
            $category_color = $row['color'] ?? '#3498db';
            $type_icon = ($row['type'] == 'entrata') ? 'fa-arrow-up' : 'fa-arrow-down';
            $type_label = ($row['type'] == 'entrata') ? 'Entrata' : 'Uscita';
            echo "<tr>";
            echo "<td>{$date}</td>";
            echo "<td><i class='fas {$type_icon}'></i> {$type_label}</td>";
            echo "<td>{$row['description']}</td>";
            echo "<td><span class='badge' style='background-color: {$category_color}'>{$row['category']}</span></td>";
            echo "<td class='{$color_class} text-right'>{$amount}</td>";
            echo "<td class='text-right'>";
            echo "<a href='edit_transaction?id={$row['id']}' class='btn btn-sm btn-info'>Modifica</a> ";
            echo "<a href='transactions?delete={$row['id']}' class='btn btn-sm btn-danger' onclick='return confirm(\\'Sei sicuro di voler eliminare questa transazione?\\')'>Elimina</a>";
            echo "</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='6' class='text-center'>Nessuna transazione trovata</td></tr>";
    }
    $html = ob_get_clean();
    echo $html;
    exit;
}
?>

<?php include 'footer.php'; ?>
