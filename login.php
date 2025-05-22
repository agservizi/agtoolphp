<?php
require_once 'inc/config.php';

// Gestione login
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone'] ?? '');
    if ($phone === '') {
        $login_error = 'Inserisci il numero di cellulare.';
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
        $stmt->bind_param('s', $phone);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            session_start();
            $_SESSION['user_phone'] = $phone;
            header('Location: index');
            exit;
        } else {
            $login_error = 'Numero non riconosciuto.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Login - AGTool Finance</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            background: #f4f6f9;
            font-family: 'Source Sans Pro', Arial, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            display: flex;
            max-width: 820px;
            width: 100%;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 8px 32px 0 rgba(44, 62, 80, 0.13);
            overflow: hidden;
            min-height: 420px;
        }
        .login-left {
            background: #232526;
            color: #fff;
            flex: 1.1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 2rem;
        }
        .login-left .login-logo {
            width: 80px;
            height: 80px;
            background: #28a745;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.2rem;
            box-shadow: 0 2px 12px #28a74533;
        }
        .login-left .login-logo i {
            color: #fff;
            font-size: 2.5rem;
        }
        .login-left .brand-title {
            font-size: 2.1rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            letter-spacing: 1px;
        }
        .login-left .brand-desc {
            font-size: 1.1rem;
            color: #e0e0e0;
            margin-bottom: 1.5rem;
            text-align: center;
            line-height: 1.5;
        }
        .login-left .brand-footer {
            font-size: 0.95rem;
            color: #b0b0b0;
            margin-top: 2.5rem;
        }
        .login-right {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 2rem;
            background: #fff;
        }
        .login-form {
            width: 100%;
            max-width: 320px;
            box-sizing: border-box;
        }
        .login-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #232526;
            margin-bottom: 0.7rem;
            letter-spacing: 1px;
        }
        .login-subtitle {
            color: #888;
            font-size: 1.05rem;
            margin-bottom: 1.5rem;
        }
        .form-group {
            margin-bottom: 1.3rem;
            text-align: left;
        }
        .form-group label {
            font-weight: 600;
            color: #232526;
            margin-bottom: 0.3rem;
            display: block;
            font-size: 1.08rem;
        }
        .form-control {
            width: 100%;
            padding: 0.7rem 1rem;
            border-radius: 8px;
            border: 1.5px solid #e0e0e0;
            font-size: 1.1rem;
            background: #f8f8f8;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }
        .form-control:focus {
            border-color: #28a745;
            outline: none;
        }
        .btn-login {
            width: 100%;
            padding: 0.7rem;
            background: #28a745;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1.15rem;
            font-weight: 700;
            letter-spacing: 1px;
            box-shadow: 0 2px 8px #28a74522;
            transition: background 0.2s, box-shadow 0.2s;
            margin-top: 0.5rem;
        }
        .btn-login:hover {
            background: #1abc9c;
            box-shadow: 0 4px 16px #28a74533;
        }
        .login-error {
            color: #e74c3c;
            background: #fdecea;
            border-radius: 6px;
            padding: 0.7rem 1rem;
            margin-bottom: 1rem;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid #e74c3c22;
        }
        .login-error i {
            font-size: 1.2rem;
        }
        @media (max-width: 900px) {
            .login-container { flex-direction: column; min-height: 0; }
            .login-left, .login-right { padding: 2rem 1.2rem; }
        }
        @media (max-width: 600px) {
            .login-container { box-shadow: none; border-radius: 0; }
            .login-left, .login-right { padding: 1.2rem 0.5rem; }
            .login-form { max-width: 100%; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <div class="login-logo">
                <i class="fas fa-wallet"></i>
            </div>
            <div class="brand-title">AGTool Finance</div>
            <div class="brand-desc">Gestisci le tue finanze personali in modo semplice, sicuro e intelligente.<br>Accedi per iniziare a monitorare le tue entrate, uscite e risparmi.</div>
            <div class="brand-footer">&copy; 2025 AGTool Finance</div>
        </div>
        <div class="login-right">
            <form method="post" class="login-form">
                <div class="login-title">Accedi</div>
                <div class="login-subtitle">Inserisci il tuo numero di cellulare</div>
                <?php if ($login_error): ?>
                    <div class="login-error"><i class="fas fa-exclamation-triangle"></i> <?php echo $login_error; ?> </div>
                <?php endif; ?>
                <div class="form-group">
                    <label for="phone"><i class="fas fa-mobile-alt"></i> Numero di cellulare</label>
                    <input type="text" name="phone" id="phone" class="form-control" required autofocus autocomplete="tel">
                </div>
                <button type="submit" class="btn-login">Accedi</button>
            </form>
        </div>
    </div>
</body>
</html>
