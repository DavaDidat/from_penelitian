<?php
require_once 'auth.php';
require_once '../../config/database.php';

$train_result = null;

function resolve_model_path(string $path): string
{
    if ($path === '') {
        return '';
    }
    if (preg_match('/^(?:[A-Za-z]:[\\\\\\/]|\\/)/', $path)) {
        return is_file($path) ? $path : '';
    }
    $resolved = realpath(__DIR__ . '/../..' . '/' . ltrim($path, '/\\'));
    return ($resolved && is_file($resolved)) ? $resolved : '';
}

// Handle eksekusi training ML
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'train') {
    verify_csrf();

    $python_cmd = getenv('PYTHON_BIN') ?: 'python';
    $script_path = realpath(__DIR__ . '/../../python/train.py');
    $project_root = realpath(__DIR__ . '/../..');

    if (!$script_path || !$project_root) {
        $train_result = ['status' => 'error', 'message' => 'File script train.py tidak ditemukan.'];
    } elseif (!function_exists('proc_open')) {
        $train_result = ['status' => 'error', 'message' => 'Fungsi proc_open dinonaktifkan di server. Training tidak dapat dijalankan.'];
    } else {
        $cmd = escapeshellarg($python_cmd) . ' ' . escapeshellarg($script_path) . ' ' . (int)$_SESSION['admin_user_id'];
        $descriptors = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open($cmd, $descriptors, $pipes, $project_root);

        if (!is_resource($process)) {
            $train_result = ['status' => 'error', 'message' => 'Script Python gagal dieksekusi.'];
        } else {
            stream_set_blocking($pipes[1], false);
            stream_set_blocking($pipes[2], false);
            $stdout = '';
            $stderr = '';
            $started = time();

            while (true) {
                $stdout .= stream_get_contents($pipes[1]);
                $stderr .= stream_get_contents($pipes[2]);
                $status = proc_get_status($process);

                if (!$status['running']) {
                    break;
                }
                if ((time() - $started) > 120) {
                    proc_terminate($process);
                    $stderr .= "\nProses training melebihi batas waktu 120 detik.";
                    break;
                }
                usleep(100000);
            }

            $stdout .= stream_get_contents($pipes[1]);
            $stderr .= stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);

            $lines = preg_split('/\r?\n/', trim($stdout));
            $json_str = end($lines);
            $train_result = json_decode($json_str, true);

            if (!is_array($train_result)) {
                $detail = trim($stdout . "\n" . $stderr);
                error_log('Training output invalid: ' . $detail);
                $train_result = ['status' => 'error', 'message' => 'Gagal membaca output dari Python.'];
            }
        }
    }
}

// Ambil model aktif saat ini
$stmt = $pdo->query("SELECT * FROM model_versions WHERE is_active = 1 LIMIT 1");
$active_model = $stmt->fetch();
$model_ok = $active_model
    && resolve_model_path((string)$active_model['model_file_path']) !== ''
    && resolve_model_path((string)$active_model['vectorizer_file_path']) !== '';

// Ambil jumlah data siap train (hanya data relevan dan sudah divalidasi)
$stmt_data = $pdo->query("SELECT COUNT(*) FROM responses WHERE validation_status IN ('valid', 'corrected') AND relevance_status = 'relevant'");
$ready_data_count = $stmt_data->fetchColumn();

// Ambil riwayat model
$stmt_history = $pdo->query("SELECT * FROM model_versions ORDER BY id DESC LIMIT 10");
$model_history = $stmt_history->fetchAll();

include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Training Model AI (SVM)</h2>
</div>

<?php if ($train_result): ?>
    <?php if ($train_result['status'] === 'success'): ?>
        <div class="alert alert-success fw-bold" style="border: 3px solid #111; border-radius: 8px; box-shadow: 4px 4px 0px #111;">
            ✅ <?= htmlspecialchars($train_result['message']) ?><br>
            Akurasi Model Baru: <?= number_format($train_result['metrics']['accuracy'] * 100, 2) ?>%
        </div>
    <?php else: ?>
        <div class="alert alert-danger fw-bold" style="border: 3px solid #111; border-radius: 8px; box-shadow: 4px 4px 0px #111;">
            ❌ Gagal: <?= htmlspecialchars($train_result['message']) ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="row">
    <div class="col-md-6">
        <div class="n-card">
            <h4>Pusat Komando ML</h4>
            <p>Di sini Anda bisa memicu (trigger) sistem AI Python untuk belajar dari data yang sudah Anda validasi.</p>
            
            <div class="mb-4">
                <strong>Data siap latih (Tervalidasi):</strong> <span class="badge badge-neo badge-positif"><?= $ready_data_count ?> Data</span>
            </div>
            
            <form method="POST" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="train">
                <button type="submit" class="btn btn-neo btn-neo-primary w-100 p-3" style="font-size: 1.2rem;" <?= $ready_data_count < 10 ? 'disabled' : '' ?>>
                    🤖 LATIH MODEL AI SEKARANG
                </button>
            </form>
            <?php if ($ready_data_count < 10): ?>
                <div class="text-danger mt-2 small fw-bold">⚠ Minimal butuh 10 data yang sudah divalidasi untuk mulai melatih AI.</div>
            <?php endif; ?>
            <div class="mt-3 text-muted small">
                <em>*Proses ini akan menjalankan library Sastrawi, TF-IDF, dan SVM di background. Model terbaik akan langsung diaktifkan untuk prediksi otomatis.</em>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="n-card" style="background-color: #e0f7fa;">
            <h4>Status Model Aktif</h4>
            <?php if ($active_model): ?>
                <?php $metrics = json_decode($active_model['evaluation_metrics'], true); ?>
                <table class="table table-borderless mt-3 fw-bold">
                    <tr>
                        <td width="40%">Versi Model</td>
                        <td>: Model <?= htmlspecialchars($active_model['version_number']) ?></td>
                    </tr>
                    <tr>
                        <td>Akurasi</td>
                        <td>: <?= number_format(($metrics['accuracy'] ?? 0) * 100, 2) ?>%</td>
                    </tr>
                    <tr>
                        <td>Data Training</td>
                        <td>: <?= (int)($active_model['training_data_count'] ?? 0) ?> data</td>
                    </tr>
                    <tr>
                        <td>Diperbarui</td>
                        <td>: <?= date('d M Y H:i', strtotime($active_model['trained_at'])) ?></td>
                    </tr>
                    <tr>
                        <td>Status</td>
                        <td>:
                            <?php if ($model_ok): ?>
                                <span class="badge badge-neo badge-positif">ONLINE</span>
                            <?php else: ?>
                                <span class="badge badge-neo badge-negatif">FILE MODEL TIDAK DITEMUKAN</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                <?php if (!$model_ok): ?>
                    <div class="alert alert-danger fw-bold border-dark mt-3 mb-0">
                        Model aktif tidak dapat dimuat. Jalankan training ulang agar prediksi otomatis berfungsi.
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-warning fw-bold border-dark mt-3">
                    Belum ada model AI yang dilatih. Sistem Prediksi Otomatis sedang Offline (Label: BELUM ADA).
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="n-card mt-4">
    <h4 class="mb-3">Riwayat Versi Model</h4>
    <?php if (count($model_history) > 0): ?>
        <table class="table-neo">
            <thead>
                <tr>
                    <th>Versi</th>
                    <th>Akurasi</th>
                    <th>Waktu Training</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($model_history as $row): ?>
                <?php $h_metrics = json_decode($row['evaluation_metrics'], true); ?>
                <tr>
                    <td>Model <?= htmlspecialchars($row['version_number']) ?></td>
                    <td><?= number_format(($h_metrics['accuracy'] ?? 0) * 100, 2) ?>%</td>
                    <td><?= date('d/m/Y H:i', strtotime($row['trained_at'])) ?></td>
                    <td>
                        <?php if ($row['is_active']): ?>
                            <span class="badge badge-neo badge-positif">AKTIF</span>
                        <?php else: ?>
                            <span class="badge badge-neo" style="background: #e0e0e0;">LAMA</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="text-muted">Belum ada riwayat model.</p>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
