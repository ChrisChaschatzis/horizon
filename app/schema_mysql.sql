-- MySQL Schema

CREATE TABLE IF NOT EXISTS datasets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    excel_filename VARCHAR(255),
    jsonl_filename VARCHAR(255),
    notes TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dataset_id INT NOT NULL,
    project_id VARCHAR(255) NOT NULL,
    cordis_url TEXT,
    title TEXT,
    acronym VARCHAR(255),
    coordinator_name TEXT,
    coordinator_country VARCHAR(100),
    has_greek_participant TINYINT(1) DEFAULT 0,
    has_greek_beneficiary TINYINT(1) DEFAULT 0,
    has_greek_any_role TINYINT(1) DEFAULT 0,
    is_greek_coordinator TINYINT(1) DEFAULT 0,
    keywords_text TEXT,
    fields_text TEXT,
    invest_priorities_json JSON,
    signature_date DATE,
    eu_contribution DECIMAL(15,2),
    total_cost DECIMAL(15,2),
    pillar VARCHAR(255),
    type_of_action VARCHAR(255),
    status VARCHAR(100),
    error_text TEXT,
    INDEX (dataset_id),
    INDEX (dataset_id, project_id),
    INDEX (dataset_id, coordinator_country),
    FOREIGN KEY (dataset_id) REFERENCES datasets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_countries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dataset_id INT NOT NULL,
    project_db_id INT NOT NULL,
    country VARCHAR(100) NOT NULL,
    role VARCHAR(50) NOT NULL,
    INDEX (dataset_id),
    INDEX (dataset_id, country),
    FOREIGN KEY (dataset_id) REFERENCES datasets(id) ON DELETE CASCADE,
    FOREIGN KEY (project_db_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_keywords (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dataset_id INT NOT NULL,
    project_db_id INT NOT NULL,
    keyword VARCHAR(255) NOT NULL,
    INDEX (dataset_id),
    INDEX (dataset_id, keyword),
    FOREIGN KEY (dataset_id) REFERENCES datasets(id) ON DELETE CASCADE,
    FOREIGN KEY (project_db_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_fields (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dataset_id INT NOT NULL,
    project_db_id INT NOT NULL,
    path_text TEXT NOT NULL,
    level1 VARCHAR(255),
    level2 VARCHAR(255),
    level3 VARCHAR(255),
    INDEX (dataset_id),
    INDEX (dataset_id, level1),
    FOREIGN KEY (dataset_id) REFERENCES datasets(id) ON DELETE CASCADE,
    FOREIGN KEY (project_db_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS invest_priorities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dataset_id INT NOT NULL,
    project_db_id INT NOT NULL,
    label VARCHAR(255) NOT NULL,
    percent DECIMAL(5,2) NOT NULL,
    INDEX (dataset_id),
    INDEX (dataset_id, label),
    FOREIGN KEY (dataset_id) REFERENCES datasets(id) ON DELETE CASCADE,
    FOREIGN KEY (project_db_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
