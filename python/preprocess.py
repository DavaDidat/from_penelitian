import re
import sys
import json
from Sastrawi.Stemmer.StemmerFactory import StemmerFactory
from Sastrawi.StopWordRemover.StopWordRemoverFactory import StopWordRemoverFactory

# 1. Kamus Slang (30-50 kata gaul mahasiswa IT UNPAM)
SLANG_DICT = {
    "gw": "saya", "gua": "saya", "lu": "anda", "lo": "anda", "banget": "sangat",
    "bgt": "sangat", "codingan": "kode", "ngoding": "coding", "error": "galat",
    "ngebantu": "membantu", "bikin": "membuat", "kalo": "kalau", "klo": "kalau",
    "dpt": "dapat", "krn": "karena", "karna": "karena", "yg": "yang", "aja": "saja",
    "gampang": "mudah", "susah": "sulit", "cepet": "cepat", "bener": "benar",
    "bner": "benar", "males": "malas", "pake": "pakai", "pakek": "pakai",
    "tp": "tetapi", "tapi": "tetapi", "udh": "sudah", "udah": "sudah",
    "gak": "tidak", "ga": "tidak", "gk": "tidak", "ngga": "tidak", "engga": "tidak",
    "dgn": "dengan", "dg": "dengan", "utk": "untuk", "buat": "untuk",
    "keren": "bagus", "mantap": "bagus", "anjir": "sangat", "jelek": "buruk"
}

# Inisialisasi Sastrawi (bisa memakan waktu beberapa detik saat pertama load)
stemmer = StemmerFactory().create_stemmer()
stopword = StopWordRemoverFactory().create_stop_word_remover()

def preprocess_text(text):
    if not text:
        return ""
        
    # 1. Case Folding
    text = text.lower()
    
    # 2. Cleaning (Hapus URL dan karakter selain huruf alphabet)
    text = re.sub(r'http\S+', '', text)
    text = re.sub(r'[^a-z\s]', ' ', text)
    
    # 3. Normalisasi Slang
    words = text.split()
    normalized_words = [SLANG_DICT.get(word, word) for word in words]
    text = ' '.join(normalized_words)
    
    # 4. Stopword Removal
    text = stopword.remove(text)
    
    # 5. Stemming
    text = stemmer.stem(text)
    
    return text.strip()

if __name__ == "__main__":
    if len(sys.argv) > 1:
        input_text = sys.argv[1]
    else:
        input_text = sys.stdin.buffer.read().decode("utf-8", errors="replace")
    print(preprocess_text(input_text))
