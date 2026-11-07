<?php
require_once 'auth.php';
require_once '../../config/database.php';

// Handle aksi update relevansi & undo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['response_id'])) {
    verify_csrf();
    $response_id = (int)$_POST['response_id'];
    
    if ($_POST['action'] === 'undo') {
        $stmt = $pdo->prepare("UPDATE responses SET relevance_status = 'pending' WHERE id = ?");
        $stmt->execute([$response_id]);
        header('Location: relevance.php?msg=undo_success');
        exit;
    } else {
        if ($_POST['action'] === 'approve') {
            $stmt = $pdo->prepare("UPDATE responses SET relevance_status = 'relevant' WHERE id = ?");
            $stmt->execute([$response_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE responses SET relevance_status = 'irrelevant', validation_status = 'pending', validation_label = NULL WHERE id = ?");
            $stmt->execute([$response_id]);
        }
        header('Location: relevance.php?msg=success');
        exit;
    }
}

// Ambil data yang pending
$stmt = $pdo->query("SELECT r.id, r.raw_text, r.created_at, rsp.nama, rsp.semester, rsp.kelas 
    FROM responses r 
    JOIN respondents rsp ON r.respondent_id = rsp.id 
    WHERE r.relevance_status = 'pending' ORDER BY r.created_at ASC");
$pending_responses = $stmt->fetchAll();

// Ambil riwayat keputusan (15 terakhir) untuk fitur UNDO
$stmt_history = $pdo->query("SELECT r.id, r.raw_text, r.relevance_status, r.created_at, rsp.nama 
    FROM responses r 
    JOIN respondents rsp ON r.respondent_id = rsp.id 
    WHERE r.relevance_status != 'pending' ORDER BY r.id DESC LIMIT 15");
$history_responses = $stmt_history->fetchAll();

include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Cek Relevansi Data</h2>
    <span class="badge badge-neo badge-pending fs-6"><?= count($pending_responses) ?> Menunggu</span>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
    <div class="alert alert-success fw-bold" style="border: 3px solid #111; border-radius: 8px; box-shadow: 4px 4px 0px #111;">
        Status relevansi berhasil diupdate!
    </div>
<?php elseif (isset($_GET['msg']) && $_GET['msg'] == 'undo_success'): ?>
    <div class="alert alert-warning fw-bold" style="border: 3px solid #111; border-radius: 8px; box-shadow: 4px 4px 0px #111;">
        Keputusan berhasil dibatalkan. Data kembali ke antrean!
    </div>
<?php endif; ?>

<div class="n-card mb-4">
    <h4 class="mb-3">Antrean Menunggu (Pending)</h4>
    <p class="mb-4">Pada tahap ini, Anda menyaring data mentah. Tandai "Tidak Relevan" jika teks berupa spam, tidak bermakna, atau merupakan duplikasi persis dari jawaban sebelumnya.</p>
    
    <?php if (count($pending_responses) > 0): ?>
        <table class="table-neo">
            <thead>
                <tr>
                    <th width="18%">Nama Responden</th>
                    <th width="10%">Kelas</th>
                    <th width="12%">Tanggal</th>
                    <th width="40%">Teks Jawaban Mentah</th>
                    <th width="20%">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pending_responses as $row): ?>
                <tr>
                    <td class="fw-bold"><?= htmlspecialchars($row['nama']) ?></td>
                    <td><?= htmlspecialchars($row['kelas']) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                    <td><?= nl2br(htmlspecialchars($row['raw_text'])) ?></td>
                    <td>
                        <form method="POST" action="relevance.php" class="d-inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="response_id" value="<?= $row['id'] ?>">
                            <button type="submit" name="action" value="approve" class="btn btn-neo btn-neo-success btn-sm mb-1 w-100">Relevan</button>
                            <button type="submit" name="action" value="reject" class="btn btn-neo btn-neo-danger btn-sm mb-1 w-100" onclick="return confirm('Yakin menandai tidak relevan?');">Tidak Relevan</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="text-center py-4">
            <h5 class="text-muted">Hore! Tidak ada data yang perlu dicek.</h5>
        </div>
    <?php endif; ?>
</div>

<!-- RIWAYAT KEPUTUSAN UNTUK UNDO -->
<div class="n-card" style="background-color: #f8f9fa;">
    <h4 class="mb-3">Riwayat Keputusan Terakhir</h4>
    <p class="text-muted small">Jika Anda salah pencet, Anda bisa membatalkannya di sini agar data kembali ke antrean atas.</p>
    
    <?php if (count($history_responses) > 0): ?>
        <table class="table-neo" style="font-size: 0.9rem;">
            <thead>
                <tr>
                    <th width="20%">Nama Responden</th>
                    <th width="45%">Teks Jawaban</th>
                    <th width="15%">Status</th>
                    <th width="20%">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($history_responses as $row): ?>
                <tr>
                    <td class="fw-bold"><?= htmlspecialchars($row['nama']) ?></td>
                    <td><div style="max-height: 80px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; word-break: break-word;"><?= htmlspecialchars($row['raw_text']) ?></div></td>
                    <td>
                        <?php if($row['relevance_status'] == 'relevant'): ?>
                            <span class="badge badge-neo badge-positif">Relevan</span>
                        <?php else: ?>
                            <span class="badge badge-neo badge-negatif">Tidak Relevan</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="POST" action="relevance.php" class="d-inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="response_id" value="<?= $row['id'] ?>">
                            <button type="submit" name="action" value="undo" class="btn btn-neo btn-neo-secondary btn-sm" onclick="return confirm('Batalkan keputusan ini?');">Batalkan (Undo)</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="text-center py-3"><small class="text-muted">Belum ada riwayat.</small></div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
