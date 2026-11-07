<?php
require_once __DIR__ . '/../config/database.php';

function column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.COLUMNS "
        . "WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
    );
    $stmt->execute([$table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

function index_exists(PDO $pdo, string $table, string $index): bool
{
    $stmt = $pdo->query("SHOW INDEX FROM `$table` WHERE Key_name = " . $pdo->quote($index));
    return (bool)$stmt->fetch();
}

function constraint_exists(PDO $pdo, string $table, string $constraint): bool
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS "
        . "WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?"
    );
    $stmt->execute([$table, $constraint]);
    return (int)$stmt->fetchColumn() > 0;
}

$changes = [];

if (!column_exists($pdo, 'respondents', 'nama')) {
    $pdo->exec("ALTER TABLE respondents ADD COLUMN nama VARCHAR(100) NOT NULL DEFAULT '' AFTER id");
    $changes[] = 'respondents.nama ditambahkan';
}

$version_type = $pdo->query(
    "SELECT COLUMN_TYPE, IS_NULLABLE FROM information_schema.COLUMNS "
    . "WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'model_versions' AND COLUMN_NAME = 'version_number'"
)->fetch();

if ($version_type && (stripos($version_type['COLUMN_TYPE'], 'varchar(30)') === false || $version_type['IS_NULLABLE'] !== 'YES')) {
    $pdo->exec("ALTER TABLE model_versions MODIFY COLUMN version_number VARCHAR(30) NULL");
    $changes[] = 'model_versions.version_number disamakan ke VARCHAR(30) NULL';
}

if (!column_exists($pdo, 'model_versions', 'trained_by')) {
    $pdo->exec("ALTER TABLE model_versions ADD COLUMN trained_by INT NULL AFTER is_active");
    $changes[] = 'model_versions.trained_by ditambahkan';
}

if (!constraint_exists($pdo, 'model_versions', 'model_versions_trained_by_fk')) {
    $pdo->exec(
        "ALTER TABLE model_versions ADD CONSTRAINT model_versions_trained_by_fk "
        . "FOREIGN KEY (trained_by) REFERENCES users(id) ON DELETE SET NULL"
    );
    $changes[] = 'FK model_versions.trained_by ditambahkan';
}

if (!column_exists($pdo, 'responses', 'updated_at')) {
    $pdo->exec(
        "ALTER TABLE responses ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP "
        . "ON UPDATE CURRENT_TIMESTAMP AFTER created_at"
    );
    $changes[] = 'responses.updated_at ditambahkan';
}

$indexes = [
    'idx_responses_relevance' => 'relevance_status',
    'idx_responses_validation' => 'validation_status',
    'idx_responses_created' => 'created_at',
    'idx_responses_relevance_validation' => 'relevance_status, validation_status',
];

foreach ($indexes as $index => $columns) {
    if (!index_exists($pdo, 'responses', $index)) {
        $pdo->exec("ALTER TABLE responses ADD INDEX `$index` ($columns)");
        $changes[] = "index $index ditambahkan";
    }
}

if (count($changes) === 0) {
    echo "Tidak ada perubahan. Skema sudah sesuai.\n";
} else {
    echo "Migrasi selesai:\n- " . implode("\n- ", $changes) . "\n";
}
