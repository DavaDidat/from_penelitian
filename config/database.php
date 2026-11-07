<?php
$config = [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'db' => getenv('DB_NAME') ?: 'skripsi_sentimen_ai',
    'user' => getenv('DB_USER') ?: 'root',
    'pass' => getenv('DB_PASS') !== false ? getenv('DB_PASS') : '',
    'charset' => 'utf8mb4',
];

$local_config_file = __DIR__ . '/database.local.php';
if (is_file($local_config_file)) {
    $local_config = require $local_config_file;
    if (is_array($local_config)) {
        $config = array_merge($config, $local_config);
    }
}

$dsn = "mysql:host={$config['host']};dbname={$config['db']};charset={$config['charset']}";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $config['user'], $config['pass'], $options);
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Koneksi database gagal. Silakan hubungi peneliti.');
}
