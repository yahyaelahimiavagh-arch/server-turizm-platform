INSERT INTO app_settings (setting_key, setting_value) VALUES
('max_concurrent_leave_employees', '2')
ON DUPLICATE KEY UPDATE setting_value = setting_value;
