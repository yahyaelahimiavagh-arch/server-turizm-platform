ALTER TABLE users
    ADD COLUMN birth_date DATE NULL AFTER hire_date;

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

INSERT INTO annual_leave_policy_tiers
(min_completed_years, max_completed_years, company_days, legal_minimum_days, is_active, sort_order)
VALUES
(1, 5, 15.00, 14.00, 1, 10),
(6, 14, 20.00, 20.00, 1, 20),
(15, NULL, 26.00, 26.00, 1, 30);

INSERT INTO annual_leave_age_rules
(min_age, max_age, legal_minimum_days, is_active, sort_order)
VALUES
(NULL, 18, 20.00, 1, 10),
(50, NULL, 20.00, 1, 20);

INSERT INTO app_settings (setting_key, setting_value) VALUES
('annual_leave_accrual_basis', 'service_anniversary'),
('annual_leave_unused_carryover', '1'),
('annual_leave_active_cashout_allowed', '0')
ON DUPLICATE KEY UPDATE setting_value = setting_value;
