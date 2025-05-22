<?php
require_once 'inc/config.php';

// Verifica se è stato passato un ID valido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: transactions.php?status=error&message=" . urlencode("ID transazione non valido"));
    exit;
}

$id = intval($_GET['id']);

// Ottieni i dettagli della transazione
$sql = "SELECT * FROM transactions WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

// Verifica se la transazione esiste
if ($result->num_rows === 0) {
    header("Location: transactions.php?status=error&message=" . urlencode("Transazione non trovata"));
    exit;
}

$transaction = $result->fetch_assoc();

// Gestisci il form di modifica
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Raccogli i dati dal form
    $type = clean_input($_POST['type']);
    $amount = floatval($_POST['amount']);
    $description = clean_input($_POST['description']);
    $category = clean_input($_POST['category']);
    $date = clean_input($_POST['date']);
    
    // Verifica che i dati siano validi
    if (empty($description) || $amount <= 0 || empty($date)) {
        $error_message = "Tutti i campi sono obbligatori e l'importo deve essere maggiore di zero";
    } elseif ($type != 'entrata' && $type != 'uscita') {
        $error_message = "Tipo di transazione non valido";
    } else {
        // Aggiorna la transazione nel database
        $update_sql = "UPDATE transactions 
                      SET description = ?, amount = ?, type = ?, category = ?, date = ? 
                      WHERE id = ?";
        
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("sdsssi", $description, $amount, $type, $category, $date, $id);
        
        if ($update_stmt->execute()) {
            header("Location: transactions.php?status=success&message=" . urlencode("Transazione aggiornata con successo"));
            exit;
        } else {
            $error_message = "Errore nell'aggiornamento della transazione: " . $update_stmt->error;
        }
        
        $update_stmt->close();
    }
}

// Ottieni tutte le categorie per il tipo di transazione selezionato
function getCategoriesByType($conn, $type) {
    $sql = "SELECT name, color FROM categories WHERE type = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $type);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    
    $stmt->close();
    return $categories;
}

$categories = getCategoriesByType($conn, $transaction['type']);

// Includi l'header
include 'header.php';
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark">Modifica Transazione</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index">Home</a></li>
                    <li class="breadcrumb-item"><a href="transactions">Transazioni</a></li>
                    <li class="breadcrumb-item active">Modifica</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Modifica Transazione</h3>
                </div>
                
                <form method="post" action="">
                    <div class="card-body">
                        <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger">
                            <?php echo $error_message; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label>Tipo di Transazione</label>
                            <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                                <label class="btn btn-outline-success <?php echo ($transaction['type'] == 'entrata') ? 'active' : ''; ?>" id="btnEntrata">
                                    <input type="radio" name="type" value="entrata" <?php echo ($transaction['type'] == 'entrata') ? 'checked' : ''; ?>> Entrata
                                </label>
                                <label class="btn btn-outline-danger <?php echo ($transaction['type'] == 'uscita') ? 'active' : ''; ?>" id="btnUscita">
                                    <input type="radio" name="type" value="uscita" <?php echo ($transaction['type'] == 'uscita') ? 'checked' : ''; ?>> Uscita
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Descrizione</label>
                            <input type="text" class="form-control" id="description" name="description" value="<?php echo htmlspecialchars($transaction['description']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="amount">Importo (€)</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" id="amount" name="amount" value="<?php echo $transaction['amount']; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="category">Categoria</label>
                            <select class="form-control" id="category" name="category" required>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['name']); ?>" 
                                        data-color="<?php echo htmlspecialchars($cat['color']); ?>" 
                                        <?php echo ($transaction['category'] == $cat['name']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="date">Data</label>
                            <input type="date" class="form-control" id="date" name="date" value="<?php echo $transaction['date']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Aggiorna Transazione</button>
                        <a href="transactions" class="btn btn-secondary">Torna alle transazioni</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestione del cambio di tipo di transazione
    document.querySelectorAll('input[name="type"]').forEach(function(radio) {
        radio.addEventListener('change', function(e) {
            const transactionType = e.target.value;
            
            // Aggiorna lo stile dei pulsanti
            if (transactionType === 'entrata') {
                document.getElementById('btnEntrata').classList.add('active');
                document.getElementById('btnUscita').classList.remove('active');
            } else {
                document.getElementById('btnEntrata').classList.remove('active');
                document.getElementById('btnUscita').classList.add('active');
            }
            
            // Carica le categorie corrispondenti al tipo selezionato
            fetch('get_categories.php?type=' + transactionType)
                .then(response => response.json())
                .then(data => {
                    const categorySelect = document.getElementById('category');
                    categorySelect.innerHTML = '';
                    
                    data.forEach(function(cat) {
                        const option = document.createElement('option');
                        option.value = cat.name;
                        option.textContent = cat.name;
                        option.dataset.color = cat.color;
                        categorySelect.appendChild(option);
                    });
                })
                .catch(error => console.error('Errore nel caricamento delle categorie:', error));
        });
    });
});
</script>

<?php include 'footer.php'; ?>
