<?php
require_once 'auth.php';
require_once '../../config/database.php';

// Hitung statistik
$stats = [
    'total_responses' => 0,
    'pending_relevance' => 0,
    'relevant' => 0,
    'irrelevant' => 0,
    'pending_validation' => 0,
    'validated' => 0
];

try {
    // Total responses
    $stmt = $pdo->query("SELECT COUNT(*) FROM responses");
    $stats['total_responses'] = $stmt->fetchColumn();

    // Relevancy stats
    $stmt = $pdo->query("SELECT relevance_status, COUNT(*) as count FROM responses GROUP BY relevance_status");
    while ($row = $stmt->fetch()) {
        if ($row['relevance_status'] == 'pending') $stats['pending_relevance'] = $row['count'];
        if ($row['relevance_status'] == 'relevant') $stats['relevant'] = $row['count'];
        if ($row['relevance_status'] == 'irrelevant') $stats['irrelevant'] = $row['count'];
    }

    // Validation stats (only from relevant data)
    $stmt = $pdo->query("SELECT validation_status, COUNT(*) as count FROM responses WHERE relevance_status = 'relevant' GROUP BY validation_status");
    while ($row = $stmt->fetch()) {
        if ($row['validation_status'] == 'pending') $stats['pending_validation'] = $row['count'];
        if ($row['validation_status'] == 'valid' || $row['validation_status'] == 'corrected') {
            $stats['validated'] += $row['count'];
        }
    }
} catch (Exception $e) {
    error_log('Dashboard stats failed: ' . $e->getMessage());
}

include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Overview Statistik</h2>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="n-card" style="background-color: #e0f7fa;">
            <h5>Total Data Masuk</h5>
            <h1 class="display-4 font-weight-bold"><?= $stats['total_responses'] ?></h1>
            <p class="mb-0">Responden yang mensubmit form</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="n-card" style="background-color: #fff9c4;">
            <h5>Menunggu Relevansi</h5>
            <h1 class="display-4 font-weight-bold"><?= $stats['pending_relevance'] ?></h1>
            <p class="mb-0">Perlu dicek manual kelayakannya</p>
            <a href="relevance.php" class="btn btn-neo btn-neo-primary btn-sm mt-3">Cek Sekarang</a>
        </div>
    </div>
    <div class="col-md-4">
        <div class="n-card" style="background-color: #c8e6c9;">
            <h5>Menunggu Validasi</h5>
            <h1 class="display-4 font-weight-bold"><?= $stats['pending_validation'] ?></h1>
            <p class="mb-0">Data relevan yang belum dilabeli final</p>
            <a href="validation.php" class="btn btn-neo btn-neo-primary btn-sm mt-3">Validasi Sekarang</a>
        </div>
    </div>
</div>

<div class="row mt-2">
    <div class="col-md-6">
        <div class="n-card">
            <h4>Status Relevansi</h4>
            <ul class="list-group list-group-flush mt-3" style="border: 2px solid #111; border-radius: 8px;">
                <li class="list-group-item d-flex justify-content-between align-items-center fw-bold border-dark">
                    Relevan
                    <span class="badge badge-neo badge-positif rounded-pill fs-6"><?= $stats['relevant'] ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center fw-bold border-dark">
                    Tidak Relevan (Spam/Ganda)
                    <span class="badge badge-neo badge-negatif rounded-pill fs-6"><?= $stats['irrelevant'] ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center fw-bold border-dark">
                    Menunggu Dicek
                    <span class="badge badge-neo badge-pending rounded-pill fs-6"><?= $stats['pending_relevance'] ?></span>
                </li>
            </ul>
        </div>
    </div>
    <div class="col-md-6">
        <div class="n-card">
            <h4>Status Validasi (Data Relevan)</h4>
            <ul class="list-group list-group-flush mt-3" style="border: 2px solid #111; border-radius: 8px;">
                <li class="list-group-item d-flex justify-content-between align-items-center fw-bold border-dark">
                    Sudah Divalidasi
                    <span class="badge badge-neo badge-positif rounded-pill fs-6"><?= $stats['validated'] ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center fw-bold border-dark">
                    Menunggu Validasi
                    <span class="badge badge-neo badge-pending rounded-pill fs-6"><?= $stats['pending_validation'] ?></span>
                </li>
            </ul>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
