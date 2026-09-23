CREATE TABLE google_sheet_sync_queue (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    event_uuid CHAR(36) NOT NULL,
    employee_ref VARCHAR(32) NOT NULL,
    event_type VARCHAR(80) NOT NULL,
    payload_json LONGTEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    available_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    locked_at DATETIME NULL,
    processed_at DATETIME NULL,
    last_error VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_google_sheet_sync_queue_event_uuid (event_uuid),
    KEY idx_google_sheet_sync_queue_status_available (status, available_at, id),
    KEY idx_google_sheet_sync_queue_employee (employee_ref, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE google_sheet_backup_registry (
    employee_ref VARCHAR(32) NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    display_name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL,
    status VARCHAR(20) NOT NULL,
    sheet_id BIGINT NULL,
    sheet_title VARCHAR(100) NOT NULL,
    last_synced_at DATETIME NULL,
    last_event_uuid CHAR(36) NULL,
    deleted_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (employee_ref),
    KEY idx_google_sheet_backup_registry_status (status, display_name),
    KEY idx_google_sheet_backup_registry_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO app_settings (setting_key, setting_value) VALUES
('google_sheets_backup_enabled', '0'),
('google_sheets_spreadsheet_id', ''),
('google_sheets_backup_batch_size', '20')
ON DUPLICATE KEY UPDATE setting_value = setting_value;
