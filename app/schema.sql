-- SQLite Schema (Updated)

CREATE TABLE IF NOT EXISTS datasets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    excel_filename TEXT,
    jsonl_filename TEXT,
    notes TEXT
);

CREATE TABLE IF NOT EXISTS projects (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    dataset_id INTEGER NOT NULL,

    -- Core Identity
    project_id TEXT NOT NULL,
    project_number TEXT,
    acronym TEXT,
    title TEXT,
    cordis_url TEXT,

    -- Metadata
    framework_programme TEXT,
    pillar TEXT,
    thematic_priority TEXT,
    type_of_action TEXT,
    status TEXT,
    signature_date DATE,

    -- Financials
    eu_contribution REAL,
    net_eu_contribution REAL,
    total_cost REAL,

    -- Coordinator
    coordinator_name TEXT,
    coordinator_country TEXT,

    -- Greek Flags
    has_greek_participant INTEGER DEFAULT 0,
    has_greek_beneficiary INTEGER DEFAULT 0,
    has_greek_any_role INTEGER DEFAULT 0,
    is_greek_coordinator INTEGER DEFAULT 0,

    -- Enhanced Data
    keywords_text TEXT,
    fields_text TEXT,
    invest_priorities_json TEXT,
    error_text TEXT,

    FOREIGN KEY (dataset_id) REFERENCES datasets(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_projects_dataset_project_id ON projects(dataset_id, project_id);
CREATE INDEX IF NOT EXISTS idx_projects_coord_country ON projects(dataset_id, coordinator_country);

CREATE TABLE IF NOT EXISTS project_countries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    dataset_id INTEGER NOT NULL,
    project_db_id INTEGER NOT NULL,
    country TEXT NOT NULL,
    role TEXT NOT NULL,
    FOREIGN KEY (dataset_id) REFERENCES datasets(id) ON DELETE CASCADE,
    FOREIGN KEY (project_db_id) REFERENCES projects(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_pc_dataset_country ON project_countries(dataset_id, country);

CREATE TABLE IF NOT EXISTS project_keywords (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    dataset_id INTEGER NOT NULL,
    project_db_id INTEGER NOT NULL,
    keyword TEXT NOT NULL,
    FOREIGN KEY (dataset_id) REFERENCES datasets(id) ON DELETE CASCADE,
    FOREIGN KEY (project_db_id) REFERENCES projects(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_pk_dataset_keyword ON project_keywords(dataset_id, keyword);

CREATE TABLE IF NOT EXISTS project_fields (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    dataset_id INTEGER NOT NULL,
    project_db_id INTEGER NOT NULL,
    path_text TEXT NOT NULL,
    level1 TEXT,
    level2 TEXT,
    level3 TEXT,
    FOREIGN KEY (dataset_id) REFERENCES datasets(id) ON DELETE CASCADE,
    FOREIGN KEY (project_db_id) REFERENCES projects(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_pf_dataset_level1 ON project_fields(dataset_id, level1);

CREATE TABLE IF NOT EXISTS invest_priorities (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    dataset_id INTEGER NOT NULL,
    project_db_id INTEGER NOT NULL,
    label TEXT NOT NULL,
    percent REAL NOT NULL,
    FOREIGN KEY (dataset_id) REFERENCES datasets(id) ON DELETE CASCADE,
    FOREIGN KEY (project_db_id) REFERENCES projects(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_ip_dataset_label ON invest_priorities(dataset_id, label);
