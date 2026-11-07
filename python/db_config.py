import json
import os
import pathlib

BASE_DIR = pathlib.Path(__file__).resolve().parent
PROJECT_ROOT = BASE_DIR.parent

DEFAULT_CONFIG = {
    "host": "127.0.0.1",
    "user": "root",
    "password": "",
    "database": "skripsi_sentimen_ai",
}


def get_db_config():
    config = dict(DEFAULT_CONFIG)

    local_file = BASE_DIR / "db_config.local.json"
    if local_file.is_file():
        try:
            local_config = json.loads(local_file.read_text(encoding="utf-8"))
            if isinstance(local_config, dict):
                config.update(local_config)
        except Exception:
            pass

    config["host"] = os.getenv("DB_HOST", config["host"])
    config["user"] = os.getenv("DB_USER", config["user"])
    config["password"] = os.getenv("DB_PASS", config["password"])
    config["database"] = os.getenv("DB_NAME", config["database"])
    return config


def resolve_model_path(path):
    if not path:
        return path
    model_path = pathlib.Path(path)
    if model_path.is_absolute():
        return str(model_path)
    return str(PROJECT_ROOT / path)
