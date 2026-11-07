CREATE DATABASE IF NOT EXISTS skripsi_sentimen_ai CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE skripsi_sentimen_ai;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS respondents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL DEFAULT '',
    semester VARCHAR(20) NOT NULL,
    kelas VARCHAR(20) NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS model_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    version_number VARCHAR(30) NULL,
    trained_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    training_data_count INT,
    class_distribution JSON,
    parameters JSON,
    evaluation_metrics JSON,
    is_active BOOLEAN DEFAULT 0,
    trained_by INT NULL,
    model_file_path VARCHAR(255),
    vectorizer_file_path VARCHAR(255),
    CONSTRAINT model_versions_trained_by_fk FOREIGN KEY (trained_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    respondent_id INT NOT NULL,
    raw_text TEXT NOT NULL,
    preprocessed_text TEXT NULL,
    self_label ENUM('Positif', 'Negatif', 'Netral') NOT NULL,
    svm_prediction ENUM('Positif', 'Negatif', 'Netral') NULL,
    model_version_id INT NULL,
    validation_label ENUM('Positif', 'Negatif', 'Netral') NULL,
    validation_status ENUM('pending', 'valid', 'corrected') DEFAULT 'pending',
    relevance_status ENUM('pending', 'relevant', 'irrelevant') DEFAULT 'pending',
    relevance_note TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_responses_relevance (relevance_status),
    INDEX idx_responses_validation (validation_status),
    INDEX idx_responses_created (created_at),
    INDEX idx_responses_relevance_validation (relevance_status, validation_status),
    FOREIGN KEY (respondent_id) REFERENCES respondents(id) ON DELETE CASCADE,
    FOREIGN KEY (model_version_id) REFERENCES model_versions(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS ai_tools (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

-- Insert default AI tools
INSERT IGNORE INTO ai_tools (id, name) VALUES 
(1, 'ChatGPT'), 
(2, 'GitHub Copilot'), 
(3, 'Gemini'), 
(4, 'Claude'), 
(5, 'Lainnya');

CREATE TABLE IF NOT EXISTS ai_purposes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

-- Insert default purposes
INSERT IGNORE INTO ai_purposes (id, name) VALUES 
(1, 'Debugging / Mencari error'), 
(2, 'Membuat kode baru'), 
(3, 'Mempelajari konsep logika pemrograman'), 
(4, 'Lainnya');

CREATE TABLE IF NOT EXISTS response_ai_tools (
    response_id INT NOT NULL,
    ai_tool_id INT NOT NULL,
    PRIMARY KEY (response_id, ai_tool_id),
    FOREIGN KEY (response_id) REFERENCES responses(id) ON DELETE CASCADE,
    FOREIGN KEY (ai_tool_id) REFERENCES ai_tools(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS response_ai_purposes (
    response_id INT NOT NULL,
    ai_purpose_id INT NOT NULL,
    PRIMARY KEY (response_id, ai_purpose_id),
    FOREIGN KEY (response_id) REFERENCES responses(id) ON DELETE CASCADE,
    FOREIGN KEY (ai_purpose_id) REFERENCES ai_purposes(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS validation_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    response_id INT NOT NULL,
    old_label VARCHAR(20) NULL,
    new_label VARCHAR(20) NOT NULL,
    validated_by INT NOT NULL,
    reason TEXT NULL,
    validated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (response_id) REFERENCES responses(id) ON DELETE CASCADE,
    FOREIGN KEY (validated_by) REFERENCES users(id) ON DELETE CASCADE
);
