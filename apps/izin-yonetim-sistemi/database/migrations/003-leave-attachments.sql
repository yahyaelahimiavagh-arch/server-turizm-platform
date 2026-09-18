ALTER TABLE leave_types
    ADD COLUMN requires_attachment TINYINT(1) NOT NULL DEFAULT 0 AFTER deducts_annual_allowance;

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
    KEY idx_leave_attachments_request (leave_request_id),
    KEY idx_leave_attachments_uploaded_by (uploaded_by),
    CONSTRAINT fk_leave_attachments_request
        FOREIGN KEY (leave_request_id) REFERENCES leave_requests(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_leave_attachments_uploaded_by
        FOREIGN KEY (uploaded_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO app_settings (setting_key, setting_value) VALUES
('attachment_max_mb', '10')
ON DUPLICATE KEY UPDATE setting_value = setting_value;

UPDATE leave_types
SET requires_attachment = 1
WHERE code = 'medical';
