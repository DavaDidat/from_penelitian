import sys
import json
import os

import joblib
import mysql.connector

from db_config import get_db_config, resolve_model_path
from preprocess import preprocess_text


def get_active_model():
    try:
        conn = mysql.connector.connect(**get_db_config())
        cursor = conn.cursor(dictionary=True)
        cursor.execute(
            "SELECT id, model_file_path, vectorizer_file_path "
            "FROM model_versions WHERE is_active = 1 ORDER BY id DESC LIMIT 1"
        )
        result = cursor.fetchone()
        cursor.close()
        conn.close()
        return result
    except Exception as e:
        sys.stderr.write(f"DB Error: {str(e)}\n")
        return None


def read_input_text():
    if len(sys.argv) >= 2:
        return sys.argv[1]
    return sys.stdin.buffer.read().decode("utf-8", errors="replace")


def respond(payload):
    print(json.dumps(payload, ensure_ascii=False))


def main():
    raw_text = read_input_text()

    if not raw_text or not raw_text.strip():
        respond({
            "status": "error",
            "message": "Tidak ada teks input",
            "prediction": None,
            "preprocessed_text": "",
            "model_version_id": None,
        })
        return

    clean_text = preprocess_text(raw_text)
    active_model = get_active_model()

    if not active_model:
        respond({
            "status": "warning",
            "message": "Model belum dilatih",
            "prediction": None,
            "preprocessed_text": clean_text,
            "model_version_id": None,
        })
        return

    model_version_id = active_model.get("id")
    model_path = resolve_model_path(active_model.get("model_file_path"))
    vectorizer_path = resolve_model_path(active_model.get("vectorizer_file_path"))

    if not model_path or not os.path.isfile(model_path) or not vectorizer_path or not os.path.isfile(vectorizer_path):
        respond({
            "status": "error",
            "message": "File model tidak ditemukan",
            "prediction": None,
            "preprocessed_text": clean_text,
            "model_version_id": model_version_id,
        })
        return

    try:
        model = joblib.load(model_path)
        vectorizer = joblib.load(vectorizer_path)
    except Exception as e:
        respond({
            "status": "error",
            "message": f"Gagal meload model: {str(e)}",
            "prediction": None,
            "preprocessed_text": clean_text,
            "model_version_id": model_version_id,
        })
        return

    if not clean_text:
        respond({
            "status": "warning",
            "message": "Teks kosong setelah preprocessing",
            "prediction": None,
            "preprocessed_text": clean_text,
            "model_version_id": model_version_id,
        })
        return

    try:
        X = vectorizer.transform([clean_text])
        prediction = model.predict(X)[0]
        respond({
            "status": "success",
            "prediction": str(prediction),
            "preprocessed_text": clean_text,
            "model_version_id": model_version_id,
        })
    except Exception as e:
        respond({
            "status": "error",
            "message": f"Error prediksi: {str(e)}",
            "prediction": None,
            "preprocessed_text": clean_text,
            "model_version_id": model_version_id,
        })


if __name__ == "__main__":
    main()
