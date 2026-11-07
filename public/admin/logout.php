<?php
require_once __DIR__ . '/session.php';

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
header('Location: login.php');
exit;
