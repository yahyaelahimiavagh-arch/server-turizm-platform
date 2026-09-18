INSERT INTO app_settings (setting_key, setting_value) VALUES
('working_weekdays', '1,2,3,4,5'),
('developer_name', 'elahimiavagh.com'),
('developer_url', 'https://elahimiavagh.com')
ON DUPLICATE KEY UPDATE setting_value = setting_value;
