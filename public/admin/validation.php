<?php
require_once 'auth.php';
require_once '../../config/database.php';

// Handle aksi validasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['response_id'])) {
    verify_csrf();
    $response_id = (int)$_POST['response_id'];
    
    // Jika tombol yang ditekan adalah "Tandai Tidak Relevan" (Reject)
    if (isset($_POST['action']) && $_POST['action'] === 'reject') {
        $stmt = $pdo->prepare("UPDATE responses SET relevance_status = 'irrelevant', validation_status = 'pending', validation_label = NULL WHERE id = ?");
        $stmt->execute([$response_id]);
        header('Location: validation.php?msg=rejected');
        exit;
    }
    
    // Jika tombol yang ditekan adalah "Simpan Validasi"
    if (isset($_POST['final_label'])) {
        $final_label = $_POST['final_label'];
        $reason = trim($_POST['reason'] ?? '');
        $allowed_labels = ['Positif', 'Negatif', 'Netral'];

        if (!in_array($final_label, $allowed_labels, true)) {
            $error = 'Label final tidak valid.';
        } elseif (mb_strlen($reason) > 500) {
            $error = 'Alasan koreksi terlalu panjang (maksimal 500 karakter).';
        } else {
            // Cek label sebelumnya dan svm_prediction (meski sementara bisa null)
            $stmt = $pdo->prepare("SELECT validation_label, svm_prediction FROM responses WHERE id = ?");
            $stmt->execute([$response_id]);
            $current = $stmt->fetch();

            if (!$current) {
                $error = 'Data respons tidak ditemukan.';
            } else {
                $old_label = $current['validation_label'] ?? $current['svm_prediction'];
                $status = ($current['svm_prediction'] === $final_label) ? 'valid' : 'corrected';

                $pdo->beginTransaction();
                try {
                    // Update response
                    $stmt_upd = $pdo->prepare("UPDATE responses SET validation_label = ?, validation_status = ? WHERE id = ?");
                    $stmt_upd->execute([$final_label, $status, $response_id]);

                    // Catat di validation logs
                    $stmt_log = $pdo->prepare("INSERT INTO validation_logs (response_id, old_label, new_label, validated_by, reason) VALUES (?, ?, ?, ?, ?)");
                    $stmt_log->execute([$response_id, $old_label, $final_label, $_SESSION['admin_user_id'], $reason ?: null]);

                    $pdo->commit();
                    header('Location: validation.php?msg=success');
                    exit;
                } catch (Exception $e) {
                    $pdo->rollBack();
                    error_log('Validation failed: ' . $e->getMessage());
                    $error = 'Gagal memvalidasi data. Silakan coba lagi.';
                }
            }
        }
    }
}

// Ambil data yang relevan tetapi belum divalidasi
$stmt = $pdo->query("SELECT r.id, r.raw_text, r.preprocessed_text, r.self_label, r.svm_prediction, rsp.nama, rsp.kelas
    FROM responses r
    JOIN respondents rsp ON r.respondent_id = rsp.id
    WHERE r.relevance_status = 'relevant' AND r.validation_status = 'pending' ORDER BY r.created_at ASC");
$pending_validations = $stmt->fetchAll();

include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Validasi Label Sentimen</h2>
    <span class="badge badge-neo badge-pending fs-6"><?= count($pending_validations) ?> Menunggu</span>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
    <div class="alert alert-success fw-bold" style="border: 3px solid #111; border-radius: 8px; box-shadow: 4px 4px 0px #111;">
        Label berhasil divalidasi dan dicatat pada Audit Log!
    </div>
<?php elseif (isset($_GET['msg']) && $_GET['msg'] == 'rejected'): ?>
    <div class="alert alert-warning fw-bold" style="border: 3px solid #111; border-radius: 8px; box-shadow: 4px 4px 0px #111;">
        Data dikembalikan ke status Tidak Relevan (Spam/Ganda).
    </div>
<?php endif; ?>
<?php if (isset($error)): ?>
    <div class="alert alert-danger fw-bold" style="border: 3px solid #111; border-radius: 8px; box-shadow: 4px 4px 0px #111;">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="n-card">
    <p class="mb-4">Tentukan label akhir (ground truth) berdasarkan pedoman klasifikasi. Anda bisa menyetujui Label Awal / Prediksi SVM, atau mengoreksinya. Jika Anda merasa jawaban ini lolos dari penyaringan relevansi (ternyata spam), Anda bisa menghapusnya.</p>
    
    <?php if (count($pending_validations) > 0): ?>
        <table class="table-neo">
            <thead>
                <tr>
                    <th width="15%">Nama Responden</th>
                    <th width="38%">Teks Jawaban</th>
                    <th width="15%">Label & Prediksi</th>
                    <th width="32%">Aksi Validasi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pending_validations as $row): ?>
                <tr>
                    <td>
                        <div class="fw-bold"><?= htmlspecialchars($row['nama']) ?></div>
                        <small class="text-muted"><?= htmlspecialchars($row['kelas']) ?></small>
                    </td>
                    <td>
                        <div class="mb-2"><strong>Asli:</strong><br><?= nl2br(htmlspecialchars($row['raw_text'])) ?></div>
                        <?php if($row['preprocessed_text']): ?>
                            <div class="text-muted small"><strong>Preprocessed:</strong><br><?= htmlspecialchars($row['preprocessed_text'] ?? '') ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="mb-2">
                            <small class="fw-bold d-block">Self-Label Responden:</small>
                            <span class="badge badge-neo <?php echo ($row['self_label'] == 'Positif') ? 'badge-positif' : (($row['self_label'] == 'Negatif') ? 'badge-negatif' : 'badge-netral'); ?>">
                                <?= htmlspecialchars($row['self_label']) ?>
                            </span>
                        </div>
                        <div>
                            <small class="fw-bold d-block">Prediksi SVM:</small>
                            <?php if ($row['svm_prediction']): ?>
                                <span class="badge badge-neo <?php echo ($row['svm_prediction'] == 'Positif') ? 'badge-positif' : (($row['svm_prediction'] == 'Negatif') ? 'badge-negatif' : 'badge-netral'); ?>">
                                    <?= htmlspecialchars($row['svm_prediction'] ?? '') ?>
                                </span>
                            <?php else: ?>
                                <span class="badge badge-neo" style="background:#eee;">Belum ada</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <form method="POST" action="validation.php" style="background: #f9f9f9; padding: 10px; border: 2px solid #111; border-radius: 8px;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="response_id" value="<?= $row['id'] ?>">
                            
                            <div class="mb-2">
                                <label class="fw-bold small">Pilih Label Final:</label>
                                <select name="final_label" class="form-select border-dark" required style="border-width:2px; box-shadow:none;">
                                    <?php $default_val = $row['svm_prediction'] ? $row['svm_prediction'] : ''; ?>
                                    <option value="" disabled <?= empty($default_val) ? 'selected' : '' ?>>-- Tentukan --</option>
                                    <option value="Positif" <?= ($default_val == 'Positif') ? 'selected' : '' ?>>Positif</option>
                                    <option value="Negatif" <?= ($default_val == 'Negatif') ? 'selected' : '' ?>>Negatif</option>
                                    <option value="Netral" <?= ($default_val == 'Netral') ? 'selected' : '' ?>>Netral</option>
                                </select>
                            </div>
                            
                            <div class="mb-2">
                                <label class="fw-bold small">Alasan Koreksi (Opsional):</label>
                                <input type="text" name="reason" maxlength="500" class="form-control border-dark form-control-sm" style="border-width:2px; box-shadow:none;" placeholder="Diisi jika berbeda dgn Self-Label/SVM">
                            </div>
                            
                            <button type="submit" name="action" value="validate" class="btn btn-neo btn-neo-primary btn-sm w-100 mb-2">Simpan Validasi</button>
                            <button type="submit" name="action" value="reject" class="btn btn-neo btn-neo-danger btn-sm w-100" formnovalidate onclick="return confirm('Tandai jawaban ini sebagai Spam / Tidak Relevan?');">Hapus ke Tidak Relevan</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="text-center py-5">
            <h4 class="text-muted">Semua data relevan telah selesai divalidasi.</h4>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
