<?php
require_once __DIR__ . '/session.php';

$timeout = 30 * 60;

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    if (isset($_SESSION['last_activity']) && (time() - (int)$_SESSION['last_activity']) > $timeout) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => '/',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
        session_destroy();
        header('Location: login.php?expired=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
