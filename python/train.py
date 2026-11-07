import sys
import json
import joblib
import datetime
import os
import warnings

import mysql.connector
import pandas as pd
from sklearn.model_selection import train_test_split
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.svm import LinearSVC
from sklearn.metrics import (
    accuracy_score,
    precision_score,
    recall_score,
    f1_score,
    confusion_matrix,
    classification_report,
)

from db_config import get_db_config

warnings.filterwarnings("ignore", category=UserWarning)

LABELS = ["Positif", "Negatif", "Netral"]


def get_db_connection():
    return mysql.connector.connect(**get_db_config())


def main():
    trained_by = None
    if len(sys.argv) >= 2 and str(sys.argv[1]).isdigit():
        trained_by = int(sys.argv[1])

    try:
        conn = get_db_connection()

        query = (
            "SELECT id, preprocessed_text, validation_label "
            "FROM responses "
            "WHERE validation_status IN ('valid', 'corrected') "
            "AND relevance_status = 'relevant'"
        )
        df = pd.read_sql(query, conn)

        if len(df) < 10:
            print(json.dumps({
                "status": "error",
                "message": f"Data tervalidasi masih terlalu sedikit ({len(df)} data). Minimal kumpulkan 10 data untuk melatih AI."
            }))
            return

        df = df.dropna(subset=["preprocessed_text", "validation_label"])
        df = df[df["preprocessed_text"].astype(str).str.strip() != ""]

        if len(df) < 10:
            print(json.dumps({"status": "error", "message": "Setelah dibersihkan dari teks kosong, data kurang dari 10."}))
            return

        X = df["preprocessed_text"].astype(str)
        y = df["validation_label"].astype(str)

        if y.nunique() < 2:
            print(json.dumps({
                "status": "error",
                "message": "Model membutuhkan minimal 2 variasi label untuk bisa belajar membedakan. Saat ini semua data memiliki label yang sama."
            }))
            return

        class_distribution = {str(k): int(v) for k, v in y.value_counts().items()}
        stratify = y if int(y.value_counts().min()) >= 2 else None

        X_train, X_test, y_train, y_test = train_test_split(
            X,
            y,
            test_size=0.2,
            random_state=42,
            stratify=stratify,
        )

        vectorizer = TfidfVectorizer()
        X_train_vec = vectorizer.fit_transform(X_train)
        X_test_vec = vectorizer.transform(X_test)

        model = LinearSVC(random_state=42)
        model.fit(X_train_vec, y_train)

        y_pred = model.predict(X_test_vec)

        acc = accuracy_score(y_test, y_pred)
        prec = precision_score(y_test, y_pred, average="weighted", zero_division=0, labels=LABELS)
        rec = recall_score(y_test, y_pred, average="weighted", zero_division=0, labels=LABELS)
        f1 = f1_score(y_test, y_pred, average="weighted", zero_division=0, labels=LABELS)
        prec_macro = precision_score(y_test, y_pred, average="macro", zero_division=0, labels=LABELS)
        rec_macro = recall_score(y_test, y_pred, average="macro", zero_division=0, labels=LABELS)
        f1_macro = f1_score(y_test, y_pred, average="macro", zero_division=0, labels=LABELS)

        report = classification_report(
            y_test,
            y_pred,
            labels=LABELS,
            output_dict=True,
            zero_division=0,
        )
        per_class = {}
        for label in LABELS:
            item = report.get(label, {})
            per_class[label] = {
                "precision": float(item.get("precision", 0.0)),
                "recall": float(item.get("recall", 0.0)),
                "f1_score": float(item.get("f1-score", 0.0)),
                "support": int(item.get("support", 0)),
            }

        cm = confusion_matrix(y_test, y_pred, labels=LABELS).tolist()

        timestamp = datetime.datetime.now().strftime("%Y%m%d_%H%M%S")
        models_dir = os.path.join(os.path.dirname(__file__), "models")
        os.makedirs(models_dir, exist_ok=True)

        model_filename = f"svm_model_{timestamp}.pkl"
        vec_filename = f"tfidf_vec_{timestamp}.pkl"

        model_path = os.path.join(models_dir, model_filename)
        vec_path = os.path.join(models_dir, vec_filename)

        joblib.dump(model, model_path)
        joblib.dump(vectorizer, vec_path)

        model_relative_path = f"python/models/{model_filename}"
        vectorizer_relative_path = f"python/models/{vec_filename}"

        parameters = {
            "algorithm": "LinearSVC",
            "kernel": "linear",
            "C": 1.0,
            "test_size": 0.2,
            "random_state": 42,
            "stratify": stratify is not None,
            "tfidf": {
                "lowercase": True,
                "ngram_range": [1, 1],
                "max_features": None,
            },
        }

        eval_metrics = {
            "accuracy": float(acc),
            "precision": float(prec),
            "recall": float(rec),
            "f1_score": float(f1),
            "precision_weighted": float(prec),
            "recall_weighted": float(rec),
            "f1_weighted": float(f1),
            "precision_macro": float(prec_macro),
            "recall_macro": float(rec_macro),
            "f1_macro": float(f1_macro),
            "per_class": per_class,
            "confusion_matrix": cm,
            "labels": LABELS,
            "total_data": int(len(df)),
            "train_data": int(len(X_train)),
            "test_data": int(len(X_test)),
            "class_distribution": class_distribution,
        }

        if not conn.in_transaction:
            conn.start_transaction()
        cursor = conn.cursor()
        cursor.execute("UPDATE model_versions SET is_active = 0")

        cursor.execute("SHOW COLUMNS FROM model_versions LIKE 'trained_by'")
        has_trained_by = cursor.fetchone() is not None

        columns = [
            "version_number",
            "model_file_path",
            "vectorizer_file_path",
            "training_data_count",
            "class_distribution",
            "parameters",
            "evaluation_metrics",
            "is_active",
        ]
        values = [
            timestamp,
            model_relative_path,
            vectorizer_relative_path,
            int(len(df)),
            json.dumps(class_distribution),
            json.dumps(parameters),
            json.dumps(eval_metrics),
            1,
        ]

        if has_trained_by:
            columns.append("trained_by")
            values.append(trained_by)

        placeholders = ", ".join(["%s"] * len(columns))
        insert_query = f"INSERT INTO model_versions ({', '.join(columns)}) VALUES ({placeholders})"
        cursor.execute(insert_query, values)
        conn.commit()
        cursor.close()
        conn.close()

        print(json.dumps({
            "status": "success",
            "message": "AI SVM berhasil dilatih dan disimpan!",
            "metrics": eval_metrics,
            "model_name": f"Model SVM {timestamp}",
        }))

    except Exception as e:
        try:
            if 'conn' in locals() and conn.is_connected():
                conn.rollback()
                conn.close()
        except Exception:
            pass
        print(json.dumps({
            "status": "error",
            "message": f"Error internal Python: {str(e)}"
        }))


if __name__ == "__main__":
    main()
