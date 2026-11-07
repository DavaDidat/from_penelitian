let isSubmitting = false;

function safeStorageGet(key) {
    try {
        return localStorage.getItem(key);
    } catch (e) {
        return null;
    }
}

function safeStorageSet(key, value) {
    try {
        localStorage.setItem(key, value);
    } catch (e) {
        // localStorage bisa diblokir oleh mode privat; abaikan.
    }
}

document.addEventListener("DOMContentLoaded", function() {
    // 1. Cek LocalStorage untuk mencegah double submit
    if (safeStorageGet("kuesioner_submitted") === "true") {
        document.querySelectorAll('.section-form').forEach(sec => sec.classList.remove('active'));
        let successSec = document.getElementById("sec-success");
        successSec.innerHTML = `<h4 class="text-success">Anda Sudah Mengisi</h4>
                                <p>Terima kasih! Menurut catatan kami, Anda sudah pernah berpartisipasi dalam kuesioner ini.</p>`;
        successSec.classList.add("active");
        return;
    }

    // 2. Hitung jumlah kata secara live
    const mainAnswerInput = document.getElementById('main_answer');
    const wordCountSpan = document.getElementById('word_count');

    if (mainAnswerInput) {
        mainAnswerInput.addEventListener('input', function() {
            let text = this.value.trim();
            let wordCount = text === "" ? 0 : text.split(/\s+/).length;
            wordCountSpan.innerText = wordCount;
        });
    }

    // 3. Form Submit Logic (Soft warning & AJAX)
    const kuesionerForm = document.getElementById('kuesionerForm');
    const warningModalElement = document.getElementById('warningModal');
    let warningModal = null;

    if (window.bootstrap && warningModalElement) {
        warningModal = new bootstrap.Modal(warningModalElement, {});
    }

    kuesionerForm.addEventListener('submit', function(e) {
        e.preventDefault();

        if (isSubmitting) {
            return;
        }

        if (!kuesionerForm.checkValidity()) {
            kuesionerForm.reportValidity();
            return;
        }

        // Validasi Checkbox Tools & Purpose minimal 1
        const toolsChecked = document.querySelectorAll('.tool-checkbox:checked').length;
        const purposeChecked = document.querySelectorAll('.purpose-checkbox:checked').length;

        if (toolsChecked === 0) {
            alert("Mohon pilih minimal satu Tools AI yang sering Anda gunakan.");
            prevSection('sec-opinion', 'sec-main');
            return;
        }
        if (purposeChecked === 0) {
            alert("Mohon pilih minimal satu Tujuan Penggunaan AI.");
            prevSection('sec-opinion', 'sec-main');
            return;
        }

        // Cek jumlah kata
        let text = mainAnswerInput.value.trim();
        let wordCount = text === "" ? 0 : text.split(/\s+/).length;

        if (wordCount < 15 && !bypassWarning) {
            document.getElementById('modal-word-count').innerText = wordCount;
            if (warningModal) {
                warningModal.show();
            } else if (confirm("Jawaban Anda agak pendek. Tetap kirim?")) {
                bypassWarning = true;
                submitFormData();
            }
            return;
        }

        submitFormData();
    });

    const confirmButton = document.getElementById('btnConfirmSubmit');
    if (confirmButton) {
        confirmButton.addEventListener('click', function() {
            bypassWarning = true;
            if (warningModal) {
                warningModal.hide();
            }
            submitFormData();
        });
    }
});

let bypassWarning = false;

function nextSection(currentId, nextId) {
    let currentSec = document.getElementById(currentId);

    if (currentId === 'sec-main') {
        const toolsChecked = document.querySelectorAll('.tool-checkbox:checked').length;
        const purposeChecked = document.querySelectorAll('.purpose-checkbox:checked').length;
        if (toolsChecked === 0) {
            alert("Mohon pilih minimal satu Tools AI yang sering Anda gunakan.");
            return;
        }
        if (purposeChecked === 0) {
            alert("Mohon pilih minimal satu Tujuan Penggunaan AI.");
            return;
        }
    }

    // Validasi sederhana elemen required pada section ini sebelum lanjut
    let inputs = currentSec.querySelectorAll('[required]');
    let allValid = true;

    for (let input of inputs) {
        if (input.type === "radio") {
            let group = currentSec.querySelectorAll(`[name="${input.name}"]`);
            let checked = Array.from(group).some(r => r.checked);
            if (!checked) {
                allValid = false;
                break;
            }
        } else if (input.type === "checkbox") {
            if (!input.checked) {
                allValid = false;
                break;
            }
        } else {
            if (!input.value.trim()) {
                allValid = false;
                break;
            }
        }
    }

    if (!allValid) {
        alert("Mohon lengkapi semua isian wajib sebelum melanjutkan.");
        return;
    }

    document.getElementById(currentId).classList.remove('active');
    document.getElementById(nextId).classList.add('active');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function prevSection(currentId, prevId) {
    document.getElementById(currentId).classList.remove('active');
    document.getElementById(prevId).classList.add('active');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function checkScreening() {
    let scr1 = document.querySelector('input[name="screening_1"]:checked');
    let scr2 = document.querySelector('input[name="screening_2"]:checked');

    if (!scr1 || !scr2) {
        alert("Mohon jawab kedua pertanyaan screening.");
        return;
    }

    document.getElementById('sec-screening').classList.remove('active');

    if (scr1.value === "Tidak" || scr2.value === "Tidak") {
        document.getElementById('sec-rejected').classList.add('active');
    } else {
        document.getElementById('sec-main').classList.add('active');
    }
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function submitFormData() {
    if (isSubmitting) {
        return;
    }

    let form = document.getElementById('kuesionerForm');
    let formData = new FormData(form);
    let btn = document.getElementById('btnSubmit');
    let controller = new AbortController();
    let timeoutId = setTimeout(() => controller.abort(), 30000);

    isSubmitting = true;
    btn.disabled = true;
    btn.innerText = "Mengirim...";

    fetch(form.action, {
        method: 'POST',
        body: formData,
        signal: controller.signal
    })
    .then(async response => {
        let text = await response.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            throw new Error("Respons server tidak valid.");
        }
        if (!response.ok) {
            throw new Error(data.message || "Terjadi kesalahan pada server.");
        }
        return data;
    })
    .then(data => {
        clearTimeout(timeoutId);
        if (data.status === 'success') {
            safeStorageSet("kuesioner_submitted", "true");
            document.querySelectorAll('.section-form').forEach(sec => sec.classList.remove('active'));
            document.getElementById('sec-success').classList.add('active');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
            throw new Error(data.message || "Gagal menyimpan data");
        }
    })
    .catch(error => {
        clearTimeout(timeoutId);
        console.error('Error:', error);
        if (error.name === 'AbortError') {
            alert("Waktu pengiriman habis. Silakan coba lagi.");
        } else {
            alert("Terjadi kesalahan: " + (error.message || "Gagal menyimpan data"));
        }
        isSubmitting = false;
        btn.disabled = false;
        btn.innerText = "Kirim Jawaban";
    });
}
