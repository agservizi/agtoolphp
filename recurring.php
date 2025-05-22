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
$stmt->bind_result($user_id);
if (!$stmt->fetch()) {
    $stmt->close();
    session_unset();
    session_destroy();
    header('Location: login');
    exit;
}
$stmt->close();

// Gestione inserimento nuova ricorrenza
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = clean_input($_POST['description']);
    $amount = floatval($_POST['amount']);
    $type = clean_input($_POST['type']);
    $category = clean_input($_POST['category']);
    $start_date = clean_input($_POST['start_date']);
    $end_date = !empty($_POST['end_date']) ? clean_input($_POST['end_date']) : null;
    $frequency = clean_input($_POST['frequency']);
    $next_occurrence = $start_date;
    $sql = "INSERT INTO recurring_transactions (user_id, description, amount, type, category, start_date, end_date, frequency, next_occurrence) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('isdssssss', $user_id, $description, $amount, $type, $category, $start_date, $end_date, $frequency, $next_occurrence);
    $stmt->execute();
    $stmt->close();
    header('Location: recurring?success=1');
    exit;
}

// Recupera ricorrenze esistenti
$sql = "SELECT * FROM recurring_transactions WHERE user_id = ? ORDER BY next_occurrence ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Endpoint AJAX per generare ricorrenze
if (isset($_GET['action']) && $_GET['action'] === 'run_recurring') {
    $oggi = date('Y-m-d');
    $sql = "SELECT * FROM recurring_transactions WHERE next_occurrence <= ? AND (end_date IS NULL OR next_occurrence <= end_date) AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $oggi, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = 0;
    while ($row = $result->fetch_assoc()) {
        $sql_insert = "INSERT INTO transactions (description, amount, type, category, date) VALUES (?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param('sdsss', $row['description'], $row['amount'], $row['type'], $row['category'], $row['next_occurrence']);
        $stmt_insert->execute();
        $stmt_insert->close();
        $next = $row['next_occurrence'];
        switch ($row['frequency']) {
            case 'daily': $next = date('Y-m-d', strtotime($next . ' +1 day')); break;
            case 'weekly': $next = date('Y-m-d', strtotime($next . ' +1 week')); break;
            case 'monthly': $next = date('Y-m-d', strtotime($next . ' +1 month')); break;
            case 'yearly': $next = date('Y-m-d', strtotime($next . ' +1 year')); break;
        }
        if ($row['end_date'] && $next > $row['end_date']) continue;
        $sql_update = "UPDATE recurring_transactions SET next_occurrence = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param('si', $next, $row['id']);
        $stmt_update->execute();
        $stmt_update->close();
        $count++;
    }
    $stmt->close();
    header('Content-Type: application/json');
    echo json_encode(['status'=>'ok','generated'=>$count]);
    exit;
}

include 'header.php';
?>
<div class="content-header">
    <div class="container-fluid">
        <h1 class="m-0">Transazioni Ricorrenti</h1>
    </div>
</div>
<div class="container-fluid">
    <div class="card mt-3">
        <div class="card-body">
            <button class="btn btn-primary mb-3" onclick="runRecurringAjax()"><i class="fas fa-sync-alt"></i> Genera ricorrenze ora</button>
            <form method="post" class="mb-4">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <input type="text" name="description" class="form-control" placeholder="Descrizione" required>
                    </div>
                    <div class="col-md-2 mb-2">
                        <input type="number" step="0.01" name="amount" class="form-control" placeholder="Importo" required>
                    </div>
                    <div class="col-md-2 mb-2">
                        <select name="type" class="form-control" id="recurring-type" required>
                            <option value="entrata">Entrata</option>
                            <option value="uscita">Uscita</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <select name="category" class="form-control" id="recurring-category" required>
                            <option value="">Seleziona categoria</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <input type="date" name="start_date" class="form-control" required>
                    </div>
                    <div class="col-md-3 mb-2">
                        <input type="date" name="end_date" class="form-control" placeholder="Fine (opzionale)">
                    </div>
                    <div class="col-md-2 mb-2">
                        <select name="frequency" class="form-control" required>
                            <option value="monthly">Mensile</option>
                            <option value="weekly">Settimanale</option>
                            <option value="daily">Giornaliera</option>
                            <option value="yearly">Annuale</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <button type="submit" class="btn btn-success btn-block">Aggiungi</button>
                    </div>
                </div>
            </form>
            <script>
document.addEventListener('DOMContentLoaded', function() {
    function loadRecurringCategories(type) {
        const categorySelect = document.getElementById('recurring-category');
        if (!categorySelect) return;
        categorySelect.innerHTML = '<option value="">Caricamento...</option>';
        fetch('get_categories.php?type=' + type)
            .then(response => response.json())
            .then(data => {
                categorySelect.innerHTML = '';
                if (data.length === 0) {
                    const option = document.createElement('option');
                    option.value = 'Altro';
                    option.textContent = 'Altro';
                    categorySelect.appendChild(option);
                } else {
                    data.forEach(category => {
                        const option = document.createElement('option');
                        option.value = category.name;
                        option.textContent = category.name;
                        categorySelect.appendChild(option);
                    });
                }
            })
            .catch(() => {
                categorySelect.innerHTML = '<option value="Altro">Altro</option>';
            });
    }
    const typeSelect = document.getElementById('recurring-type');
    if (typeSelect) {
        loadRecurringCategories(typeSelect.value);
        typeSelect.addEventListener('change', function() {
            loadRecurringCategories(this.value);
        });
    }
});
</script>
            <h5>Elenco Ricorrenze</h5>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Descrizione</th>
                        <th>Importo</th>
                        <th>Tipo</th>
                        <th>Categoria</th>
                        <th>Frequenza</th>
                        <th>Prossima Occorrenza</th>
                        <th>Fine</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                        <td><?php echo format_currency($row['amount']); ?></td>
                        <td><?php echo ucfirst($row['type']); ?></td>
                        <td><?php echo htmlspecialchars($row['category']); ?></td>
                        <td><?php echo ucfirst($row['frequency']); ?></td>
                        <td><?php echo format_date($row['next_occurrence']); ?></td>
                        <td><?php echo $row['end_date'] ? format_date($row['end_date']) : '-'; ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php if (isset($_GET['success'])): ?>
<script>document.addEventListener('DOMContentLoaded',function(){showToast('success','Ricorrenza aggiunta con successo!');});</script>
<?php endif; ?>
<?php include 'footer.php'; ?>
