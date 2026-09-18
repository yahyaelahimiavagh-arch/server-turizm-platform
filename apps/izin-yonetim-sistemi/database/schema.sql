SET NAMES utf8mb4;
SET time_zone = '+03:00';

CREATE TABLE users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'employee',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    hire_date DATE NULL,
    birth_date DATE NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role_active (role, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE leave_types (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    deducts_annual_allowance TINYINT(1) NOT NULL DEFAULT 0,
    requires_attachment TINYINT(1) NOT NULL DEFAULT 0,
    color_hex CHAR(7) NOT NULL DEFAULT '#071B4D',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_leave_types_code (code),
    KEY idx_leave_types_active_sort (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE annual_allowances (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    allowance_year SMALLINT UNSIGNED NOT NULL,
    entitlement_days DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_annual_allowances_user_year (user_id, allowance_year),
    KEY idx_annual_allowances_year (allowance_year),
    CONSTRAINT fk_annual_allowances_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE annual_leave_policy_tiers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    min_completed_years SMALLINT UNSIGNED NOT NULL,
    max_completed_years SMALLINT UNSIGNED NULL,
    company_days DECIMAL(6,2) NOT NULL,
    legal_minimum_days DECIMAL(6,2) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_annual_leave_policy_tiers_active (is_active, sort_order),
    UNIQUE KEY uq_annual_leave_policy_tiers_range (min_completed_years, max_completed_years)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE annual_leave_age_rules (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    min_age SMALLINT UNSIGNED NULL,
    max_age SMALLINT UNSIGNED NULL,
    legal_minimum_days DECIMAL(6,2) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_annual_leave_age_rules_active (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE annual_leave_entitlements (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    service_year_number SMALLINT UNSIGNED NOT NULL,
    service_period_start DATE NOT NULL,
    service_period_end DATE NOT NULL,
    earned_on DATE NOT NULL,
    entitlement_days DECIMAL(6,2) NOT NULL,
    company_policy_days DECIMAL(6,2) NOT NULL,
    legal_minimum_days DECIMAL(6,2) NOT NULL,
    age_minimum_days DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    policy_snapshot_json LONGTEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_annual_leave_entitlements_user_service_year (user_id, service_year_number),
    KEY idx_annual_leave_entitlements_user_earned (user_id, earned_on),
    CONSTRAINT fk_annual_leave_entitlements_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE public_holidays (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    holiday_date DATE NOT NULL,
    name VARCHAR(150) NOT NULL,
    holiday_year SMALLINT UNSIGNED NOT NULL,
    is_half_day TINYINT(1) NOT NULL DEFAULT 0,
    half_day_period VARCHAR(20) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_public_holidays_date (holiday_date),
    KEY idx_public_holidays_year_date (holiday_year, holiday_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE leave_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    leave_type_id BIGINT UNSIGNED NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    duration_type VARCHAR(20) NOT NULL DEFAULT 'full_day',
    half_day_period VARCHAR(20) NULL,
    requested_days DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    employee_comment TEXT NULL,
    admin_note TEXT NULL,
    processed_by BIGINT UNSIGNED NULL,
    processed_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_leave_requests_user_status (user_id, status),
    KEY idx_leave_requests_status_created (status, created_at),
    KEY idx_leave_requests_dates (start_date, end_date),
    KEY idx_leave_requests_type (leave_type_id),
    KEY idx_leave_requests_processed_by (processed_by),
    CONSTRAINT fk_leave_requests_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_leave_requests_type
        FOREIGN KEY (leave_type_id) REFERENCES leave_types(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_leave_requests_processed_by
        FOREIGN KEY (processed_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE leave_request_days (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    leave_request_id BIGINT UNSIGNED NOT NULL,
    leave_date DATE NOT NULL,
    day_value DECIMAL(3,2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_leave_request_days_request_date (leave_request_id, leave_date),
    KEY idx_leave_request_days_date (leave_date),
    CONSTRAINT fk_leave_request_days_request
        FOREIGN KEY (leave_request_id) REFERENCES leave_requests(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE leave_attachments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    leave_request_id BIGINT UNSIGNED NOT NULL,
    uploaded_by BIGINT UNSIGNED NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name CHAR(64) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    size_bytes BIGINT UNSIGNED NOT NULL,
    sha256 CHAR(64) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_leave_attachments_stored_name (stored_name),
    UNIQUE KEY uq_leave_attachments_request (leave_request_id),
    KEY idx_leave_attachments_uploaded_by (uploaded_by),
    CONSTRAINT fk_leave_attachments_request
        FOREIGN KEY (leave_request_id) REFERENCES leave_requests(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_leave_attachments_uploaded_by
        FOREIGN KEY (uploaded_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_log (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    actor_user_id BIGINT UNSIGNED NULL,
    event_type VARCHAR(80) NOT NULL,
    entity_type VARCHAR(80) NOT NULL,
    entity_id VARCHAR(191) NOT NULL,
    metadata_json LONGTEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_entity (entity_type, entity_id, id),
    KEY idx_audit_actor (actor_user_id, id),
    KEY idx_audit_event (event_type, id),
    CONSTRAINT fk_audit_actor
        FOREIGN KEY (actor_user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE app_settings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    setting_key VARCHAR(100) NOT NULL,
    setting_value VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_app_settings_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_failures (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    email_hash CHAR(64) NOT NULL,
    ip_hash CHAR(64) NOT NULL,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_login_failures_identity_time (email_hash, ip_hash, attempted_at),
    KEY idx_login_failures_time (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


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
