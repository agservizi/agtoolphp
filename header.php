<?php
require_once 'inc/config.php';
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

            <!-- Right navbar links -->
            <ul class="navbar-nav ml-auto">
                <?php
                // Mostra la campanella solo se l'utente è loggato
                if (isset($_SESSION['user_phone'])) {
                    $phone = $_SESSION['user_phone'];
                    $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
                    $stmt->bind_param('s', $phone);
                    $stmt->execute();
                    $stmt->bind_result($user_id);
                    $stmt->fetch();
                    $stmt->close();
                    // Prendi le ultime 5 notifiche
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
                            <a href="index" class="nav-link">
                                <i class="nav-icon fas fa-home"></i>
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
            <!-- Content Header (Page header) viene aggiunto in ogni pagina -->
            
            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <!-- Contenuto specifico della pagina viene aggiunto qui -->
