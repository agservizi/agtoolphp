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

// Gestisci l'aggiunta di nuove categorie
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add') {
    $name = clean_input($_POST['name']);
    $type = clean_input($_POST['type']);
    $color = clean_input($_POST['color']);
    
    // Verifica se la categoria esiste già
    $sql_check = "SELECT * FROM categories WHERE name = '$name' AND type = '$type'";
    $result_check = $conn->query($sql_check);
    
    if ($result_check->num_rows > 0) {
        $error = "La categoria esiste già per questo tipo";
    } else {
        $sql = "INSERT INTO categories (name, type, color) VALUES ('$name', '$type', '$color')";
        
        if ($conn->query($sql) === TRUE) {
            header("Location: categories?status=success&message=" . urlencode("Categoria aggiunta con successo"));
            exit;
        } else {
            $error = "Errore nell'aggiunta della categoria: " . $conn->error;
        }
    }
}

// Gestisci l'eliminazione delle categorie
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Verifica se ci sono transazioni associate a questa categoria
    $sql_check = "SELECT COUNT(*) as count FROM transactions t
                 JOIN categories c ON t.category = c.name AND t.type = c.type
                 WHERE c.id = $id";
    $result_check = $conn->query($sql_check);
    $count = $result_check->fetch_assoc()['count'];
    
    if ($count > 0) {
        header("Location: categories?status=error&message=" . urlencode("Impossibile eliminare la categoria: ci sono transazioni associate"));
    } else {
        $sql = "DELETE FROM categories WHERE id = $id";
        
        if ($conn->query($sql) === TRUE) {
            header("Location: categories?status=success&message=" . urlencode("Categoria eliminata con successo"));
        } else {
            header("Location: categories?status=error&message=" . urlencode("Errore nell'eliminazione della categoria: " . $conn->error));
        }
    }
    exit;
}

// Modifica di una categoria
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $id = intval($_POST['id']);
    $name = clean_input($_POST['name']);
    $color = clean_input($_POST['color']);
    
    // Ottieni il tipo e il nome originale per poter aggiornare anche le transazioni
    $sql_original = "SELECT name, type FROM categories WHERE id = $id";
    $result_original = $conn->query($sql_original);
    $original = $result_original->fetch_assoc();
    
    // Aggiorna la categoria
    $sql = "UPDATE categories SET name = '$name', color = '$color' WHERE id = $id";
    
    if ($conn->query($sql) === TRUE) {
        // Aggiorna anche le transazioni che usano questa categoria
        $sql_update_transactions = "UPDATE transactions 
                                  SET category = '$name' 
                                  WHERE category = '{$original['name']}' AND type = '{$original['type']}'";
        $conn->query($sql_update_transactions);
        
        header("Location: categories?status=success&message=" . urlencode("Categoria aggiornata con successo"));
        exit;
    } else {
        $error = "Errore nell'aggiornamento della categoria: " . $conn->error;
    }
}

// Includi l'header
include 'header.php';
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark">Gestione Categorie</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index">Home</a></li>
                    <li class="breadcrumb-item active">Categorie</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="row">
        <!-- Categorie di entrata -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success">
                    <h3 class="card-title">Categorie di Entrata</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-toggle="modal" data-target="#addCategoryModal" data-type="entrata">
                            <i class="fas fa-plus"></i> Aggiungi
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th style="width: 10px">#</th>
                                <th>Nome</th>
                                <th>Colore</th>
                                <th style="width: 100px">Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT * FROM categories WHERE type = 'entrata' ORDER BY name";
                            $result = $conn->query($sql);
                            
                            if ($result->num_rows > 0) {
                                $i = 1;
                                while($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>{$i}</td>";
                                    echo "<td>{$row['name']}</td>";
                                    echo "<td><span class='badge' style='background-color: {$row['color']}'>&nbsp;</span> {$row['color']}</td>";
                                    echo "<td>";
                                    echo "<button class='btn btn-sm btn-info edit-category' data-id='{$row['id']}' data-name='{$row['name']}' data-color='{$row['color']}'><i class='fas fa-edit'></i></button> ";
                                    echo "<a href='categories?delete={$row['id']}' class='btn btn-sm btn-danger' onclick='return confirm(\"Sei sicuro di voler eliminare questa categoria?\")'>
                                          <i class='fas fa-trash'></i></a>";
                                    echo "</td>";
                                    echo "</tr>";
                                    $i++;
                                }
                            } else {
                                echo "<tr><td colspan='4' class='text-center'>Nessuna categoria trovata</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Categorie di uscita -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-danger">
                    <h3 class="card-title">Categorie di Uscita</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-toggle="modal" data-target="#addCategoryModal" data-type="uscita">
                            <i class="fas fa-plus"></i> Aggiungi
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th style="width: 10px">#</th>
                                <th>Nome</th>
                                <th>Colore</th>
                                <th style="width: 100px">Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT * FROM categories WHERE type = 'uscita' ORDER BY name";
                            $result = $conn->query($sql);
                            
                            if ($result->num_rows > 0) {
                                $i = 1;
                                while($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>{$i}</td>";
                                    echo "<td>{$row['name']}</td>";
                                    echo "<td><span class='badge' style='background-color: {$row['color']}'>&nbsp;</span> {$row['color']}</td>";
                                    echo "<td>";
                                    echo "<button class='btn btn-sm btn-info edit-category' data-id='{$row['id']}' data-name='{$row['name']}' data-color='{$row['color']}'><i class='fas fa-edit'></i></button> ";
                                    echo "<a href='categories?delete={$row['id']}' class='btn btn-sm btn-danger' onclick='return confirm(\"Sei sicuro di voler eliminare questa categoria?\")'>
                                          <i class='fas fa-trash'></i></a>";
                                    echo "</td>";
                                    echo "</tr>";
                                    $i++;
                                }
                            } else {
                                echo "<tr><td colspan='4' class='text-center'>Nessuna categoria trovata</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Gestione consigli finanziari -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-info">
                    <h3 class="card-title">Consigli Finanziari</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-toggle="modal" data-target="#addTipModal">
                            <i class="fas fa-plus"></i> Aggiungi
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th style="width: 10px">#</th>
                                <th>Titolo</th>
                                <th>Tipo</th>
                                <th>Descrizione</th>
                                <th>Stato</th>
                                <th style="width: 100px">Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT * FROM financial_tips ORDER BY type, title";
                            $result = $conn->query($sql);
                            
                            if ($result->num_rows > 0) {
                                $i = 1;
                                while($row = $result->fetch_assoc()) {
                                    $statusBadge = $row['is_active'] ? '<span class="badge badge-success">Attivo</span>' : '<span class="badge badge-secondary">Inattivo</span>';
                                    $toggleStatusUrl = "toggle_tip.php?id={$row['id']}&status=" . ($row['is_active'] ? '0' : '1');
                                    
                                    echo "<tr>";
                                    echo "<td>{$i}</td>";
                                    echo "<td>{$row['title']}</td>";
                                    echo "<td>{$row['type']}</td>";
                                    echo "<td>" . (strlen($row['description']) > 100 ? substr($row['description'], 0, 100) . '...' : $row['description']) . "</td>";
                                    echo "<td>{$statusBadge}</td>";
                                    echo "<td>";
                                    echo "<button class='btn btn-sm btn-info edit-tip' data-id='{$row['id']}' data-title='{$row['title']}'
                                          data-description='" . htmlspecialchars($row['description']) . "' data-type='{$row['type']}' data-active='{$row['is_active']}'>
                                          <i class='fas fa-edit'></i></button> ";
                                    echo "<a href='{$toggleStatusUrl}' class='btn btn-sm btn-warning'>
                                          <i class='fas fa-power-off'></i></a> ";
                                    echo "<a href='delete_tip.php?id={$row['id']}' class='btn btn-sm btn-danger' onclick='return confirm(\"Sei sicuro di voler eliminare questo consiglio?\")'>
                                          <i class='fas fa-trash'></i></a>";
                                    echo "</td>";
                                    echo "</tr>";
                                    $i++;
                                }
                            } else {
                                echo "<tr><td colspan='6' class='text-center'>Nessun consiglio finanziario trovato</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Aggiungi Categoria -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" role="dialog" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCategoryModalLabel">Aggiungi Categoria</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="categories" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <label for="category-type">Tipo</label>
                        <select id="category-type" name="type" class="form-control" required>
                            <option value="entrata">Entrata</option>
                            <option value="uscita">Uscita</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="category-name">Nome</label>
                        <input type="text" id="category-name" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="category-color">Colore</label>
                        <input type="color" id="category-color" name="color" class="form-control" value="#3498db">
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

<!-- Modal Modifica Categoria -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" role="dialog" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCategoryModalLabel">Modifica Categoria</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="categories" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit-category-id">
                    <div class="form-group">
                        <label for="edit-category-name">Nome</label>
                        <input type="text" id="edit-category-name" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-category-color">Colore</label>
                        <input type="color" id="edit-category-color" name="color" class="form-control">
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

<!-- Modal Aggiungi Consiglio -->
<div class="modal fade" id="addTipModal" tabindex="-1" role="dialog" aria-labelledby="addTipModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTipModalLabel">Aggiungi Consiglio Finanziario</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="process_tip" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <label for="tip-title">Titolo</label>
                        <input type="text" id="tip-title" name="title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="tip-type">Tipo</label>
                        <select id="tip-type" name="type" class="form-control" required>
                            <option value="risparmio">Risparmio</option>
                            <option value="budget">Budget</option>
                            <option value="investimento">Investimento</option>
                            <option value="debito">Gestione Debiti</option>
                            <option value="spesa">Ottimizzazione Spese</option>
                            <option value="generale">Generale</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="tip-description">Descrizione</label>
                        <textarea id="tip-description" name="description" class="form-control" rows="5" required></textarea>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="tip-active" name="is_active" value="1" checked>
                            <label class="custom-control-label" for="tip-active">Attivo</label>
                        </div>
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

<!-- Modal Modifica Consiglio -->
<div class="modal fade" id="editTipModal" tabindex="-1" role="dialog" aria-labelledby="editTipModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editTipModalLabel">Modifica Consiglio Finanziario</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="process_tip" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit-tip-id">
                    <div class="form-group">
                        <label for="edit-tip-title">Titolo</label>
                        <input type="text" id="edit-tip-title" name="title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-tip-type">Tipo</label>
                        <select id="edit-tip-type" name="type" class="form-control" required>
                            <option value="risparmio">Risparmio</option>
                            <option value="budget">Budget</option>
                            <option value="investimento">Investimento</option>
                            <option value="debito">Gestione Debiti</option>
                            <option value="spesa">Ottimizzazione Spese</option>
                            <option value="generale">Generale</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit-tip-description">Descrizione</label>
                        <textarea id="edit-tip-description" name="description" class="form-control" rows="5" required></textarea>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="edit-tip-active" name="is_active" value="1">
                            <label class="custom-control-label" for="edit-tip-active">Attivo</label>
                        </div>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Popola automaticamente il tipo di categoria nel modal
    $('#addCategoryModal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget);
        var type = button.data('type');
        var modal = $(this);
        
        modal.find('#category-type').val(type);
    });
    
    // Popola i campi per la modifica della categoria
    $('.edit-category').click(function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        var color = $(this).data('color');
        
        $('#edit-category-id').val(id);
        $('#edit-category-name').val(name);
        $('#edit-category-color').val(color);
        
        $('#editCategoryModal').modal('show');
    });
    
    // Popola i campi per la modifica del consiglio
    $('.edit-tip').click(function() {
        var id = $(this).data('id');
        var title = $(this).data('title');
        var description = $(this).data('description');
        var type = $(this).data('type');
        var active = $(this).data('active');
        
        $('#edit-tip-id').val(id);
        $('#edit-tip-title').val(title);
        $('#edit-tip-description').val(description);
        $('#edit-tip-type').val(type);
        $('#edit-tip-active').prop('checked', active == 1);
        
        $('#editTipModal').modal('show');
    });
});
</script>

<?php if (isset($_GET['success'])): ?>
<script>document.addEventListener('DOMContentLoaded',function(){showToast('success','Operazione sulle categorie completata!');});</script>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
<script>document.addEventListener('DOMContentLoaded',function(){showToast('error','Errore nell\'operazione sulle categorie!');});</script>
<?php endif; ?>

<?php include 'footer.php'; ?>
