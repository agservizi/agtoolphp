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

// Recupera le notifiche dell'utente
$sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY scheduled_at DESC, created_at DESC";
$notif_stmt = $conn->prepare($sql);
$notif_stmt->bind_param('i', $user_id);
$notif_stmt->execute();
$result = $notif_stmt->get_result();

include 'header.php';
?>
<div class="content-header">
    <div class="container-fluid">
        <h1 class="m-0">Notifiche e Promemoria</h1>
    </div>
</div>
<div class="container-fluid">
    <div class="card mt-3">
        <div class="card-body">
            <?php if ($result->num_rows > 0): ?>
                <ul class="list-group">
                <?php while($row = $result->fetch_assoc()): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?php echo htmlspecialchars($row['title']); ?></strong><br>
                            <span class="text-muted" style="font-size:0.95em;"><?php echo htmlspecialchars($row['message']); ?></span>
                        </div>
                        <span class="badge badge-<?php echo $row['status'] == 'sent' ? 'success' : ($row['status'] == 'failed' ? 'danger' : 'warning'); ?>">
                            <?php echo ucfirst($row['status']); ?>
                        </span>
                    </li>
                <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <div class="alert alert-info text-center">Nessuna notifica o promemoria.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php if (isset($_GET['success'])): ?>
<script>document.addEventListener('DOMContentLoaded',function(){showToast('success','Operazione sulle notifiche completata!');});</script>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
<script>document.addEventListener('DOMContentLoaded',function(){showToast('error','Errore nell\'operazione sulle notifiche!');});</script>
<?php endif; ?>
<?php
// Endpoint AJAX per badge notifiche
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $unread_count = 0;
    $notifiche = [];
    $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->bind_param('s', $_SESSION['user_phone']);
    $stmt->execute();
    $stmt->bind_result($user_id);
    $stmt->fetch();
    $stmt->close();
    $notif_stmt = $conn->prepare("SELECT status FROM notifications WHERE user_id = ? ORDER BY scheduled_at DESC, created_at DESC LIMIT 10");
    $notif_stmt->bind_param('i', $user_id);
    $notif_stmt->execute();
    $notif_stmt->bind_result($status);
    while($notif_stmt->fetch()) {
        if ($status === 'pending') $unread_count++;
    }
    $notif_stmt->close();
    echo json_encode(['unread_count'=>$unread_count]);
    exit;
}
// Marca tutte le notifiche come lette (status='sent') al click sulla campanella
if (isset($_GET['ajax']) && $_GET['ajax'] == 'read') {
    $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->bind_param('s', $_SESSION['user_phone']);
    $stmt->execute();
    $stmt->bind_result($user_id);
    $stmt->fetch();
    $stmt->close();
    $conn->query("UPDATE notifications SET status='sent', sent_at=NOW() WHERE user_id = $user_id AND status='pending'");
    echo json_encode(['status'=>'ok']);
    exit;
}
include 'footer.php';
