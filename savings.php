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

// Gestione eliminazione obiettivi
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    $sql = "DELETE FROM savings_goals WHERE id = $id";
    
    if ($conn->query($sql) === TRUE) {
        header("Location: savings?status=success&message=" . urlencode("Obiettivo eliminato con successo"));
    } else {
        header("Location: savings?status=error&message=" . urlencode("Errore nell'eliminazione dell'obiettivo: " . $conn->error));
    }
    exit;
}

// Gestione aggiornamento importo attuale
if (isset($_POST['update_amount'])) {
    $id = intval($_POST['goal_id']);
    $amount = floatval($_POST['current_amount']);
    
    $sql = "UPDATE savings_goals SET current_amount = $amount WHERE id = $id";
    
    if ($conn->query($sql) === TRUE) {
        header("Location: savings?status=success&message=" . urlencode("Importo aggiornato con successo"));
    } else {
        header("Location: savings?status=error&message=" . urlencode("Errore nell'aggiornamento dell'importo: " . $conn->error));
    }
    exit;
}

// Gestione contributo all'obiettivo
if (isset($_POST['contribute'])) {
    $goal_id = intval($_POST['goal_id']);
    $amount = floatval($_POST['amount']);
    $description = clean_input($_POST['description']);
    $date = clean_input($_POST['date']);
    
    // Recupera i dati dell'obiettivo
    $sql_goal = "SELECT * FROM savings_goals WHERE id = $goal_id";
    $result_goal = $conn->query($sql_goal);
    
    if ($result_goal->num_rows > 0) {
        $goal = $result_goal->fetch_assoc();
        
        // Aggiorna l'importo attuale dell'obiettivo
        $new_amount = $goal['current_amount'] + $amount;
        $sql_update = "UPDATE savings_goals SET current_amount = $new_amount WHERE id = $goal_id";
        
        if ($conn->query($sql_update) === TRUE) {
            // Registra la transazione
            $sql_transaction = "INSERT INTO transactions (description, amount, type, category, date) 
                               VALUES ('$description', $amount, 'entrata', 'Risparmio: {$goal['name']}', '$date')";
            
            if ($conn->query($sql_transaction) === TRUE) {
                header("Location: savings?status=success&message=" . urlencode("Contributo aggiunto con successo"));
            } else {
                header("Location: savings?status=error&message=" . urlencode("Errore nella registrazione della transazione: " . $conn->error));
            }
        } else {
            header("Location: savings?status=error&message=" . urlencode("Errore nell'aggiornamento dell'importo: " . $conn->error));
        }
    } else {
        header("Location: savings?status=error&message=" . urlencode("Obiettivo non trovato"));
    }
    exit;
}

// Includi l'header
include 'header.php';
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark">Obiettivi di Risparmio</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index">Home</a></li>
                    <li class="breadcrumb-item active">Obiettivi di Risparmio</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="row">
        <div class="col-12 mb-4">
            <button type="button" class="btn btn-success" data-toggle="modal" data-target="#addGoalModal">
                <i class="fas fa-plus"></i> Nuovo Obiettivo di Risparmio
            </button>
        </div>
    </div>
    
    <div class="row">
        <?php
        // Ottieni tutti gli obiettivi di risparmio
        $sql = "SELECT * FROM savings_goals ORDER BY target_date ASC";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $percentage = ($row['target_amount'] > 0) ? ($row['current_amount'] / $row['target_amount']) * 100 : 0;
                $percentage = min(100, $percentage);
                $date_text = $row['target_date'] ? 'entro il ' . format_date($row['target_date']) : 'Nessuna data limite';
                $remaining = $row['target_amount'] - $row['current_amount'];
                $remaining = max(0, $remaining);
                
                // Calcola i giorni rimanenti
                $days_remaining = '';
                if ($row['target_date']) {
                    $target_date = new DateTime($row['target_date']);
                    $current_date = new DateTime();
                    $interval = $current_date->diff($target_date);
                    
                    if ($interval->invert == 0) { // Data futura
                        $days_remaining = $interval->days . ' giorni rimanenti';
                    } else {
                        $days_remaining = 'Data obiettivo superata';
                    }
                }
                
                // Determina il colore della card in base alla percentuale
                $card_class = 'card-success';
                if ($percentage < 25) {
                    $card_class = 'card-danger';
                } elseif ($percentage < 75) {
                    $card_class = 'card-warning';
                }
        ?>
        <div class="col-md-6 col-lg-4">
            <div class="card <?php echo $card_class; ?>">
                <div class="card-header">
                    <h3 class="card-title"><?php echo $row['name']; ?></h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-toggle="dropdown">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a href="#" class="dropdown-item edit-goal" data-id="<?php echo $row['id']; ?>" 
                               data-name="<?php echo $row['name']; ?>"
                               data-target="<?php echo $row['target_amount']; ?>"
                               data-current="<?php echo $row['current_amount']; ?>"
                               data-date="<?php echo $row['target_date']; ?>">
                               <i class="fas fa-edit mr-2"></i> Modifica
                            </a>
                            <a href="#" class="dropdown-item contribute-goal" data-id="<?php echo $row['id']; ?>" data-name="<?php echo $row['name']; ?>">
                               <i class="fas fa-plus-circle mr-2"></i> Aggiungi Contributo
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="savings?delete=<?php echo $row['id']; ?>" class="dropdown-item text-danger" 
                               onclick="return confirm('Sei sicuro di voler eliminare questo obiettivo?')">
                               <i class="fas fa-trash mr-2"></i> Elimina
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <h2 class="mb-0"><?php echo format_currency($row['current_amount']); ?></h2>
                        <small class="text-muted">di <?php echo format_currency($row['target_amount']); ?></small>
                    </div>
                    
                    <div class="progress mb-3" style="height: 25px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $percentage; ?>%;" 
                             aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                            <?php echo round($percentage, 1); ?>%
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-3">
                        <span>Mancano: <?php echo format_currency($remaining); ?></span>
                        <span><?php echo $date_text; ?></span>
                    </div>
                    
                    <?php if ($days_remaining) { ?>
                    <div class="text-center">
                        <span class="badge badge-info"><?php echo $days_remaining; ?></span>
                    </div>
                    <?php } ?>
                    
                    <?php
                    // Calcola quanto risparmiare al mese/settimana/giorno per raggiungere l'obiettivo in tempo
                    if ($row['target_date'] && $remaining > 0) {
                        $target_date = new DateTime($row['target_date']);
                        $current_date = new DateTime();
                        
                        if ($target_date > $current_date) {
                            $days = $current_date->diff($target_date)->days;
                            
                            if ($days > 0) {
                                $per_day = $remaining / $days;
                                $per_week = $per_day * 7;
                                $per_month = $per_day * 30;
                                
                                echo '<div class="alert alert-info mt-3">';
                                echo '<h5><i class="fas fa-info-circle mr-2"></i> Consigli</h5>';
                                echo '<p>Per raggiungere il tuo obiettivo in tempo, dovresti risparmiare:</p>';
                                echo '<ul class="mb-0">';
                                echo '<li>' . format_currency($per_day) . ' al giorno, oppure</li>';
                                echo '<li>' . format_currency($per_week) . ' a settimana, oppure</li>';
                                echo '<li>' . format_currency($per_month) . ' al mese</li>';
                                echo '</ul>';
                                echo '</div>';
                            }
                        }
                    }
                    ?>
                </div>
                <div class="card-footer">
                    <button type="button" class="btn btn-sm btn-success contribute-goal" data-id="<?php echo $row['id']; ?>" data-name="<?php echo $row['name']; ?>">
                        <i class="fas fa-plus-circle"></i> Contribuisci
                    </button>
                    <button type="button" class="btn btn-sm btn-info float-right edit-goal" data-id="<?php echo $row['id']; ?>" 
                            data-name="<?php echo $row['name']; ?>"
                            data-target="<?php echo $row['target_amount']; ?>"
                            data-current="<?php echo $row['current_amount']; ?>"
                            data-date="<?php echo $row['target_date']; ?>">
                        <i class="fas fa-edit"></i> Modifica
                    </button>
                </div>
            </div>
        </div>
        <?php
            }
        } else {
            echo '<div class="col-12">';
            echo '<div class="alert alert-info">';
            echo '<h5><i class="icon fas fa-info"></i> Nessun obiettivo di risparmio!</h5>';
            echo 'Non hai ancora creato obiettivi di risparmio. Clicca sul pulsante "Nuovo Obiettivo di Risparmio" per iniziare.';
            echo '</div>';
            echo '</div>';
        }
        ?>
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
            <form action="savings" method="post" id="goal-form">
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

<!-- Modal Modifica Obiettivo -->
<div class="modal fade" id="editGoalModal" tabindex="-1" role="dialog" aria-labelledby="editGoalModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editGoalModalLabel">Modifica Obiettivo</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="process_goal" method="post">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit-goal-id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit-goal-name">Nome dell'obiettivo</label>
                        <input type="text" id="edit-goal-name" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-goal-amount">Importo obiettivo</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">€</span>
                            </div>
                            <input type="number" step="0.01" min="0.01" id="edit-goal-amount" name="target_amount" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="edit-goal-current">Importo attuale</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">€</span>
                            </div>
                            <input type="number" step="0.01" min="0" id="edit-goal-current" name="current_amount" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="edit-goal-date">Data obiettivo (opzionale)</label>
                        <input type="date" id="edit-goal-date" name="target_date" class="form-control">
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

<!-- Modal Aggiungi Contributo -->
<div class="modal fade" id="contributeGoalModal" tabindex="-1" role="dialog" aria-labelledby="contributeGoalModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="contributeGoalModalLabel">Aggiungi Contributo</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="savings" method="post">
                <input type="hidden" name="contribute" value="1">
                <input type="hidden" name="goal_id" id="contribute-goal-id">
                <div class="modal-body">
                    <div class="alert alert-info">
                        Stai aggiungendo un contributo a: <strong id="contribute-goal-name"></strong>
                    </div>
                    <div class="form-group">
                        <label for="contribute-amount">Importo</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">€</span>
                            </div>
                            <input type="number" step="0.01" min="0.01" id="contribute-amount" name="amount" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="contribute-description">Descrizione</label>
                        <input type="text" id="contribute-description" name="description" class="form-control" 
                               placeholder="Es. Deposito per risparmio" required>
                    </div>
                    <div class="form-group">
                        <label for="contribute-date">Data</label>
                        <input type="date" id="contribute-date" name="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-success">Salva</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
<script>document.addEventListener('DOMContentLoaded',function(){showToast('success','Obiettivo salvato con successo!');});</script>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
<script>document.addEventListener('DOMContentLoaded',function(){showToast('error','Errore nel salvataggio dell\'obiettivo!');});</script>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Popola i campi per la modifica dell'obiettivo
    $('.edit-goal').click(function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        var target = $(this).data('target');
        var current = $(this).data('current');
        var date = $(this).data('date');
        
        $('#edit-goal-id').val(id);
        $('#edit-goal-name').val(name);
        $('#edit-goal-amount').val(target);
        $('#edit-goal-current').val(current);
        $('#edit-goal-date').val(date);
        
        $('#editGoalModal').modal('show');
    });
    
    // Popola i campi per il contributo all'obiettivo
    $('.contribute-goal').click(function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        
        $('#contribute-goal-id').val(id);
        $('#contribute-goal-name').text(name);
        
        $('#contributeGoalModal').modal('show');
    });
});
</script>

<?php include 'footer.php'; ?>
