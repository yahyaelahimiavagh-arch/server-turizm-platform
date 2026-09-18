ALTER TABLE public_holidays
    ADD COLUMN source_type VARCHAR(20) NOT NULL DEFAULT 'manual' AFTER half_day_period,
    ADD COLUMN source_uid VARCHAR(191) NULL AFTER source_type,
    ADD COLUMN imported_at DATETIME NULL AFTER source_uid;

INSERT INTO app_settings (setting_key, setting_value)
VALUES ('annual_leave_public_holidays_deducted', '0')
ON DUPLICATE KEY UPDATE setting_value = setting_value;
