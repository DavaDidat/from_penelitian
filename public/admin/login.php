<?php
require_once __DIR__ . '/session.php';
require_once '../../config/database.php';

// Jika sudah login, lempar ke index
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';
$expired = isset($_GET['expired']);
$max_attempts = 5;
$lockout_seconds = 30;
$attempts = (int)($_SESSION['login_attempts'] ?? 0);
$last_attempt = (int)($_SESSION['login_last_attempt'] ?? 0);

if ($attempts >= $max_attempts && (time() - $last_attempt) >= $lockout_seconds) {
    $attempts = 0;
    $_SESSION['login_attempts'] = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if ($attempts >= $max_attempts && (time() - $last_attempt) < $lockout_seconds) {
        $remaining = $lockout_seconds - (time() - $last_attempt);
        $error = "Terlalu banyak percobaan. Coba lagi dalam {$remaining} detik.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Username dan Password wajib diisi!';
        } else {
            $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_user_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['last_activity'] = time();
                unset($_SESSION['login_attempts'], $_SESSION['login_last_attempt']);
                header('Location: index.php');
                exit;
            } else {
                $_SESSION['login_attempts'] = $attempts + 1;
                $_SESSION['login_last_attempt'] = time();
                $error = 'Username atau Password salah!';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Peneliti - Klasifikasi Sentimen AI</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-color: #f4f0ec;
            --card-bg: #ffffff;
            --border-color: #111111;
            --primary-color: #b388ff; 
            --success-color: #00e676; 
            --border-width: 3px;
            --shadow-offset: 8px;
        }
        body {
            background-color: var(--bg-color);
            font-family: 'Space Grotesk', sans-serif;
            color: var(--border-color);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background-color: var(--card-bg);
            border: var(--border-width) solid var(--border-color);
            border-radius: 16px;
            box-shadow: var(--shadow-offset) var(--shadow-offset) 0px var(--border-color);
            width: 100%;
            max-width: 400px;
            padding: 2rem;
        }
        h3 {
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: -1px;
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .form-control {
            border: var(--border-width) solid var(--border-color);
            border-radius: 8px;
            font-weight: 600;
            box-shadow: 4px 4px 0px var(--border-color);
            transition: all 0.2s ease;
            margin-bottom: 1.5rem;
        }
        .form-control:focus {
            border-color: var(--border-color);
            box-shadow: 6px 6px 0px var(--border-color);
            outline: none;
        }
        .btn-login {
            font-weight: 700;
            border: var(--border-width) solid var(--border-color);
            border-radius: 8px;
            box-shadow: 4px 4px 0px var(--border-color);
            transition: all 0.15s ease;
            text-transform: uppercase;
            padding: 10px 20px;
            width: 100%;
            background-color: var(--success-color);
            color: #000;
        }
        .btn-login:hover {
            transform: translate(2px, 2px);
            box-shadow: 2px 2px 0px var(--border-color);
            background-color: var(--success-color);
            color: #000;
        }
        .btn-login:active {
            transform: translate(4px, 4px);
            box-shadow: 0px 0px 0px var(--border-color);
        }
    </style>
</head>
<body>

<div class="login-card">
    <h3>Login Peneliti</h3>
    
    <?php if ($expired): ?>
        <div class="alert alert-warning" style="border: 2px solid #111; font-weight: bold; border-radius: 8px;">
            Sesi Anda telah berakhir. Silakan login kembali.
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger" style="border: 2px solid #111; font-weight: bold; border-radius: 8px;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <?= csrf_field() ?>
        <div>
            <label class="form-label fw-bold">Username</label>
            <input type="text" name="username" class="form-control" required autofocus autocomplete="username">
        </div>
        <div>
            <label class="form-label fw-bold">Password</label>
            <input type="password" name="password" class="form-control" required autocomplete="current-password">
        </div>
        <button type="submit" class="btn btn-login mt-2">Masuk</button>
    </form>
</div>

</body>
</html>
