INSERT INTO leave_types (code, name, deducts_annual_allowance, requires_attachment, color_hex, is_active, sort_order) VALUES
('annual', 'Yıllık İzin', 1, 0, '#C9A227', 1, 10),
('medical', 'Raporlu İzin', 0, 1, '#2E6F95', 1, 20),
('excuse', 'Mazeret İzni', 0, 0, '#8A6D3B', 1, 30),
('unpaid', 'Ücretsiz İzin', 0, 0, '#6C757D', 1, 40);

INSERT INTO app_settings (setting_key, setting_value) VALUES
('default_annual_allowance_days', '20.00'),
('company_name', 'Server Turizm'),
('app_name', 'Server Turizm İzin Yönetim Sistemi'),
('working_weekdays', '1,2,3,4,5,6'),
('work_schedule_json', '{"1":"full_day","2":"full_day","3":"full_day","4":"full_day","5":"full_day","6":"morning","7":"off"}'),
('leave_full_day_weights_json', '{"1":1,"2":1,"3":1,"4":1,"5":1,"6":1,"7":0}'),
('developer_name', 'elahimiavagh.com'),
('developer_url', 'https://elahimiavagh.com'),
('attachment_max_mb', '10'),
('max_concurrent_leave_employees', '2');
