<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../config/database.php';

function respond(string $status, string $message, int $http_code = 200): void
{
    http_response_code($http_code);
    echo json_encode(['status' => $status, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function run_python(string $script_path, string $input, int $timeout = 30): array
{
    if (!function_exists('proc_open')) {
        return ['ok' => false, 'stdout' => '', 'stderr' => 'proc_open dinonaktifkan di server'];
    }

    $python_cmd = getenv('PYTHON_BIN') ?: 'python';
    $cmd = escapeshellarg($python_cmd) . ' ' . escapeshellarg($script_path);
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($cmd, $descriptors, $pipes, dirname($script_path));
    if (!is_resource($process)) {
        return ['ok' => false, 'stdout' => '', 'stderr' => 'Gagal menjalankan Python'];
    }

    fwrite($pipes[0], $input);
    fclose($pipes[0]);
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
        if ((time() - $started) > $timeout) {
            proc_terminate($process);
            $stderr .= "\nProses Python melebihi batas waktu.";
            break;
        }
        usleep(100000);
    }

    $stdout .= stream_get_contents($pipes[1]);
    $stderr .= stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);

    return ['ok' => true, 'stdout' => $stdout, 'stderr' => $stderr];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond('error', 'Method not allowed', 405);
}

try {
    $scr1 = $_POST['screening_1'] ?? '';
    $scr2 = $_POST['screening_2'] ?? '';
    $consent = $_POST['consent'] ?? '';

    if ($scr1 !== 'Ya' || $scr2 !== 'Ya') {
        respond('error', 'Anda tidak memenuhi kriteria screening.', 422);
    }
    if ($consent !== '1') {
        respond('error', 'Persetujuan partisipasi wajib dicentang.', 422);
    }

    $nama = trim((string)($_POST['nama'] ?? ''));
    $semester = trim((string)($_POST['semester'] ?? ''));
    $kelas = trim((string)($_POST['kelas'] ?? ''));
    $main_answer = trim((string)($_POST['main_answer'] ?? ''));
    $self_label = trim((string)($_POST['self_label'] ?? ''));
    $ai_tools = $_POST['ai_tools'] ?? [];
    $ai_purposes = $_POST['ai_purposes'] ?? [];

    $allowed_semesters = ['1-2', '3-4', '5-6', '7-8', '9-10'];
    $allowed_kelas = ['Reguler A', 'Reguler B', 'Reguler C'];
    $allowed_labels = ['Positif', 'Negatif', 'Netral'];

    if (mb_strlen($nama) > 100) {
        respond('error', 'Nama maksimal 100 karakter.', 422);
    }
    if (!in_array($semester, $allowed_semesters, true)) {
        respond('error', 'Semester tidak valid.', 422);
    }
    if (!in_array($kelas, $allowed_kelas, true)) {
        respond('error', 'Kelas tidak valid.', 422);
    }
    if ($main_answer === '') {
        respond('error', 'Jawaban utama wajib diisi.', 422);
    }
    if (mb_strlen($main_answer) > 5000) {
        respond('error', 'Jawaban utama maksimal 5000 karakter.', 422);
    }
    if (!in_array($self_label, $allowed_labels, true)) {
        respond('error', 'Label sentimen tidak valid.', 422);
    }

    $ai_tools = is_array($ai_tools) ? array_values(array_unique(array_map('intval', array_filter($ai_tools, 'is_scalar')))) : [];
    $ai_purposes = is_array($ai_purposes) ? array_values(array_unique(array_map('intval', array_filter($ai_purposes, 'is_scalar')))) : [];
    $ai_tools = array_values(array_filter($ai_tools, fn($id) => $id > 0));
    $ai_purposes = array_values(array_filter($ai_purposes, fn($id) => $id > 0));

    if (count($ai_tools) === 0) {
        respond('error', 'Pilih minimal satu Tools AI.', 422);
    }
    if (count($ai_purposes) === 0) {
        respond('error', 'Pilih minimal satu Tujuan Penggunaan AI.', 422);
    }

    $valid_tools = $pdo->query("SELECT id FROM ai_tools")->fetchAll(PDO::FETCH_COLUMN);
    $valid_purposes = $pdo->query("SELECT id FROM ai_purposes")->fetchAll(PDO::FETCH_COLUMN);
    $ai_tools = array_values(array_intersect($ai_tools, array_map('intval', $valid_tools)));
    $ai_purposes = array_values(array_intersect($ai_purposes, array_map('intval', $valid_purposes)));

    if (count($ai_tools) === 0) {
        respond('error', 'Tools AI yang dipilih tidak valid.', 422);
    }
    if (count($ai_purposes) === 0) {
        respond('error', 'Tujuan penggunaan AI yang dipilih tidak valid.', 422);
    }

    // Prediksi ML. Teks dikirim lewat stdin agar aman untuk karakter non-ASCII.
    $preprocessed_text = null;
    $svm_prediction = null;
    $model_version_id = null;

    $predict_script = realpath(__DIR__ . '/../../python/predict.py');
    if ($predict_script) {
        $predict_run = run_python($predict_script, $main_answer);
        $result = null;

        if ($predict_run['ok']) {
            $lines = preg_split('/\r?\n/', trim($predict_run['stdout']));
            $json_line = end($lines);
            $result = json_decode($json_line, true);

            if (!is_array($result)) {
                error_log('Predict output invalid: ' . trim($predict_run['stdout'] . ' ' . $predict_run['stderr']));
            }
        } else {
            error_log('Predict process failed: ' . $predict_run['stderr']);
        }

        if (is_array($result) && array_key_exists('preprocessed_text', $result)) {
            $preprocessed_text = (string)$result['preprocessed_text'];
        }

        if (is_array($result) && !empty($result['prediction']) && in_array($result['prediction'], $allowed_labels, true)) {
            $svm_prediction = $result['prediction'];
            if (!empty($result['model_version_id'])) {
                $model_version_id = (int)$result['model_version_id'];
            }
        }

        if (is_array($result) && ($result['status'] ?? '') !== 'success') {
            error_log('Predict warning: ' . ($result['message'] ?? 'unknown'));
        }
    }

    // Fallback: pastikan teks tetap dipreprocess meskipun model/predict gagal.
    if ($preprocessed_text === null) {
        $preprocess_script = realpath(__DIR__ . '/../../python/preprocess.py');
        if ($preprocess_script) {
            $preprocess_run = run_python($preprocess_script, $main_answer, 20);
            if ($preprocess_run['ok']) {
                $preprocessed_text = trim($preprocess_run['stdout']);
            } else {
                error_log('Preprocess process failed: ' . $preprocess_run['stderr']);
            }
        }
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO respondents (nama, semester, kelas) VALUES (?, ?, ?)");
    $stmt->execute([$nama, $semester, $kelas]);
    $respondent_id = (int)$pdo->lastInsertId();

    $stmt2 = $pdo->prepare(
        "INSERT INTO responses (respondent_id, raw_text, preprocessed_text, self_label, svm_prediction, model_version_id) "
        . "VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt2->execute([$respondent_id, $main_answer, $preprocessed_text, $self_label, $svm_prediction, $model_version_id]);
    $response_id = (int)$pdo->lastInsertId();

    $stmt_tool = $pdo->prepare("INSERT INTO response_ai_tools (response_id, ai_tool_id) VALUES (?, ?)");
    foreach ($ai_tools as $tool_id) {
        $stmt_tool->execute([$response_id, $tool_id]);
    }

    $stmt_purp = $pdo->prepare("INSERT INTO response_ai_purposes (response_id, ai_purpose_id) VALUES (?, ?)");
    foreach ($ai_purposes as $purp_id) {
        $stmt_purp->execute([$response_id, $purp_id]);
    }

    $pdo->commit();
    respond('success', 'Data berhasil disimpan');

} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Submit failed: ' . $e->getMessage());
    respond('error', 'Terjadi kesalahan pada server. Silakan coba lagi.', 500);
}
