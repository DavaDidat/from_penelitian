<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kuesioner Penelitian - Dampak AI</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-color: #f4f0ec;
            --card-bg: #ffffff;
            --border-color: #111111;
            --primary-color: #b388ff; /* Purple for neo-brutalism */
            --secondary-color: #ffeb3b;
            --success-color: #00e676; /* Green */
            --border-width: 3px;
            --shadow-offset: 8px;
        }
        body {
            background-color: var(--bg-color);
            font-family: 'Space Grotesk', sans-serif;
            color: var(--border-color);
        }
        .section-form {
            display: none;
        }
        .section-form.active {
            display: block;
        }
        .card {
            background-color: var(--card-bg);
            border: var(--border-width) solid var(--border-color);
            border-radius: 16px;
            box-shadow: var(--shadow-offset) var(--shadow-offset) 0px var(--border-color);
        }
        h3, h4, h5 {
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: -1px;
        }
        .btn {
            font-weight: 700;
            border: var(--border-width) solid var(--border-color);
            border-radius: 8px;
            box-shadow: 4px 4px 0px var(--border-color);
            transition: all 0.15s ease;
            text-transform: uppercase;
            padding: 10px 20px;
        }
        .btn:hover {
            transform: translate(2px, 2px);
            box-shadow: 2px 2px 0px var(--border-color);
        }
        .btn:active {
            transform: translate(4px, 4px);
            box-shadow: 0px 0px 0px var(--border-color);
        }
        .btn-primary {
            background-color: var(--primary-color);
            color: #000;
        }
        .btn-primary:hover, .btn-primary:active {
            background-color: var(--primary-color);
            color: #000;
            border-color: var(--border-color);
        }
        .btn-secondary {
            background-color: #fff;
            color: #000;
        }
        .btn-secondary:hover, .btn-secondary:active {
            background-color: #e0e0e0;
            color: #000;
            border-color: var(--border-color);
        }
        .btn-success {
            background-color: var(--success-color);
            color: #000;
        }
        .btn-success:hover, .btn-success:active {
            background-color: var(--success-color);
            color: #000;
            border-color: var(--border-color);
        }
        .form-control, .form-select {
            border: var(--border-width) solid var(--border-color);
            border-radius: 8px;
            font-weight: 600;
            box-shadow: 4px 4px 0px var(--border-color);
            transition: all 0.2s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--border-color);
            box-shadow: 6px 6px 0px var(--border-color);
            outline: none;
        }
        .form-check-input {
            border: 2px solid var(--border-color);
            width: 1.5em;
            height: 1.5em;
            margin-top: 0.1em;
            cursor: pointer;
        }
        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--border-color);
            box-shadow: 2px 2px 0px var(--border-color);
        }
        .form-check-label {
            font-weight: 600;
            margin-left: 0.5em;
            padding-top: 0.2em;
            cursor: pointer;
        }
        .modal-content {
            border: var(--border-width) solid var(--border-color);
            border-radius: 16px;
            box-shadow: var(--shadow-offset) var(--shadow-offset) 0px var(--border-color);
        }
        .modal-header {
            border-bottom: var(--border-width) solid var(--border-color);
        }
        .modal-footer {
            border-top: var(--border-width) solid var(--border-color);
        }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body p-5">
                    
                    <div id="alert-container"></div>

                    <form id="kuesionerForm" action="api/submit.php" method="POST">
                        
                        <!-- SECTION 1: Consent -->
                        <div id="sec-consent" class="section-form active">
                            <h3 class="mb-4 text-center">Kuesioner Penelitian Skripsi</h3>
                            <p>Penelitian ini bertujuan untuk menganalisis <strong>Klasifikasi Sentimen terhadap Dampak Artificial Intelligence pada Produktivitas dalam Aktivitas Pemrograman pada Mahasiswa Teknik Informatika Universitas Pamulang Menggunakan Support Vector Machine</strong>.</p>
                            <p>Partisipasi Anda sangat berarti. Seluruh data jawaban bersifat rahasia, anonim, dan hanya akan digunakan untuk keperluan akademis penelitian ini.</p>
                            
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" id="consentCheck" name="consent" value="1" required>
                                <label class="form-check-label" for="consentCheck">
                                    Saya bersedia mengisi kuesioner ini dengan jujur dan sesuai dengan pengalaman pribadi saya.
                                </label>
                            </div>
                            
                            <div class="mt-4 text-end">
                                <button type="button" class="btn btn-primary" onclick="nextSection('sec-consent', 'sec-screening')">Mulai Mengisi</button>
                            </div>
                        </div>

                        <!-- SECTION 2: Screening -->
                        <div id="sec-screening" class="section-form">
                            <h4 class="mb-4">Screening Kelayakan</h4>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">1. Apakah Anda merupakan mahasiswa aktif Program Studi Teknik Informatika Universitas Pamulang?</label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="screening_1" id="scr1_ya" value="Ya" required>
                                        <label class="form-check-label" for="scr1_ya">Ya</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="screening_1" id="scr1_tidak" value="Tidak">
                                        <label class="form-check-label" for="scr1_tidak">Tidak</label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">2. Apakah Anda pernah menggunakan tools Artificial Intelligence (seperti ChatGPT, GitHub Copilot, dll) untuk membantu aktivitas pemrograman/coding Anda?</label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="screening_2" id="scr2_ya" value="Ya" required>
                                        <label class="form-check-label" for="scr2_ya">Ya</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="screening_2" id="scr2_tidak" value="Tidak">
                                        <label class="form-check-label" for="scr2_tidak">Tidak</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4 text-end">
                                <button type="button" class="btn btn-secondary me-2" onclick="prevSection('sec-screening', 'sec-consent')">Kembali</button>
                                <button type="button" class="btn btn-primary" onclick="checkScreening()">Lanjut</button>
                            </div>
                        </div>

                        <!-- SECTION: Rejected -->
                        <div id="sec-rejected" class="section-form text-center py-5">
                            <h4 class="text-danger">Anda Tidak Memenuhi Kriteria</h4>
                            <p>Mohon maaf, kuesioner ini ditujukan khusus bagi mahasiswa aktif TI UNPAM yang memiliki pengalaman menggunakan AI dalam pemrograman.</p>
                            <p>Terima kasih atas partisipasi dan waktu Anda.</p>
                        </div>

                        <!-- SECTION 3: Profile & AI Usage -->
                        <div id="sec-main" class="section-form">
                            <h4 class="mb-4">Profil & Penggunaan AI</h4>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Nama Lengkap (Opsional)</label>
                                <input type="text" class="form-control" name="nama" id="nama" maxlength="100" placeholder="Boleh dikosongkan (anonim)" autocomplete="off">
                                <div class="form-text">Nama tidak wajib diisi. Data Anda tetap dapat dianalisis secara anonim.</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Semester saat ini</label>
                                    <select class="form-select" name="semester" id="semester" required>
                                        <option value="">Pilih Semester...</option>
                                        <option value="1-2">Semester 1 - 2</option>
                                        <option value="3-4">Semester 3 - 4</option>
                                        <option value="5-6">Semester 5 - 6</option>
                                        <option value="7-8">Semester 7 - 8</option>
                                        <option value="9-10">Semester 9 atau lebih</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mt-3 mt-md-0">
                                    <label class="form-label fw-bold">Kelas / Reguler</label>
                                    <select class="form-select" name="kelas" id="kelas" required>
                                        <option value="">Pilih Kelas...</option>
                                        <option value="Reguler A">Reguler A</option>
                                        <option value="Reguler B">Reguler B</option>
                                        <option value="Reguler C">Reguler C (CK/CS)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Tools AI apa saja yang sering Anda gunakan? (Bisa pilih lebih dari satu)</label>
                                <div class="form-check">
                                    <input class="form-check-input tool-checkbox" type="checkbox" name="ai_tools[]" value="1" id="tool_chatgpt">
                                    <label class="form-check-label" for="tool_chatgpt">ChatGPT</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input tool-checkbox" type="checkbox" name="ai_tools[]" value="2" id="tool_copilot">
                                    <label class="form-check-label" for="tool_copilot">GitHub Copilot</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input tool-checkbox" type="checkbox" name="ai_tools[]" value="3" id="tool_gemini">
                                    <label class="form-check-label" for="tool_gemini">Gemini</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input tool-checkbox" type="checkbox" name="ai_tools[]" value="4" id="tool_claude">
                                    <label class="form-check-label" for="tool_claude">Claude</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input tool-checkbox" type="checkbox" name="ai_tools[]" value="5" id="tool_lainnya">
                                    <label class="form-check-label" for="tool_lainnya">Lainnya</label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Untuk tujuan apa Anda biasanya menggunakan AI dalam coding? (Bisa pilih lebih dari satu)</label>
                                <div class="form-check">
                                    <input class="form-check-input purpose-checkbox" type="checkbox" name="ai_purposes[]" value="1" id="purp_debug">
                                    <label class="form-check-label" for="purp_debug">Debugging / Mencari error</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input purpose-checkbox" type="checkbox" name="ai_purposes[]" value="2" id="purp_new">
                                    <label class="form-check-label" for="purp_new">Membuat kode baru</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input purpose-checkbox" type="checkbox" name="ai_purposes[]" value="3" id="purp_learn">
                                    <label class="form-check-label" for="purp_learn">Mempelajari konsep logika pemrograman</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input purpose-checkbox" type="checkbox" name="ai_purposes[]" value="4" id="purp_lainnya">
                                    <label class="form-check-label" for="purp_lainnya">Lainnya</label>
                                </div>
                            </div>

                            <div class="mt-4 text-end">
                                <button type="button" class="btn btn-secondary me-2" onclick="prevSection('sec-main', 'sec-screening')">Kembali</button>
                                <button type="button" class="btn btn-primary" onclick="nextSection('sec-main', 'sec-opinion')">Lanjut</button>
                            </div>
                        </div>

                        <!-- SECTION 4: Opinion -->
                        <div id="sec-opinion" class="section-form">
                            <h4 class="mb-4">Pendapat Utama</h4>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Menurut Anda, bagaimana dampak penggunaan Artificial Intelligence terhadap produktivitas Anda dalam melakukan aktivitas pemrograman / coding?</label>
                                <p class="text-muted small mb-2">Ceritakan pengalaman Anda. Apakah sangat membantu mempercepat pekerjaan, atau justru membuat Anda merasa malas berpikir dan terlalu bergantung pada AI? (Mohon berikan setidaknya 1-2 kalimat)</p>
                                <textarea class="form-control" name="main_answer" id="main_answer" rows="5" maxlength="5000" required></textarea>
                                <div class="form-text">Kata yang diketik: <span id="word_count">0</span></div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Berdasarkan jawaban Anda di atas, bagaimana Anda menilai kecenderungan sentimen (opini) Anda sendiri?</label>
                                <div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="self_label" id="label_positif" value="Positif" required>
                                        <label class="form-check-label text-success fw-bold" for="label_positif">Positif</label>
                                        <span class="text-muted small d-block">AI berdampak baik, mempercepat pekerjaan, membantu belajar.</span>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="self_label" id="label_negatif" value="Negatif">
                                        <label class="form-check-label text-danger fw-bold" for="label_negatif">Negatif</label>
                                        <span class="text-muted small d-block">AI berdampak buruk, membuat ketergantungan, menghambat pola pikir mandiri.</span>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="self_label" id="label_netral" value="Netral">
                                        <label class="form-check-label text-secondary fw-bold" for="label_netral">Netral</label>
                                        <span class="text-muted small d-block">Dampaknya seimbang, biasa saja, atau tergantung situasi.</span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 text-end">
                                <button type="button" class="btn btn-secondary me-2" onclick="prevSection('sec-opinion', 'sec-main')">Kembali</button>
                                <button type="submit" class="btn btn-success" id="btnSubmit">Kirim Jawaban</button>
                            </div>
                        </div>

                        <!-- SECTION: Success (Modal/View after submit) -->
                        <div id="sec-success" class="section-form text-center py-5">
                            <h4 class="text-success">Terima Kasih!</h4>
                            <p>Data respons Anda telah berhasil disimpan.</p>
                            <p>Bantuan Anda sangat berharga untuk kesuksesan penelitian skripsi ini.</p>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Soft Warning Peringatan Kata -->
<div class="modal fade" id="warningModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Konfirmasi Jawaban</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Jawaban Anda agak pendek (<span id="modal-word-count"></span> kata). Mohon pertimbangkan untuk memberikan 1-2 kalimat tambahan agar argumen Anda lebih jelas untuk penelitian skripsi ini.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tulis Ulang</button>
        <button type="button" class="btn btn-primary" id="btnConfirmSubmit">Tetap Kirim</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/form.js"></script>
</body>
</html>
