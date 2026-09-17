INSERT INTO leave_types (code, name, deducts_annual_allowance, color_hex, is_active, sort_order) VALUES
('annual', 'Yıllık İzin', 1, '#C9A227', 1, 10),
('medical', 'Raporlu İzin', 0, '#2E6F95', 1, 20),
('excuse', 'Mazeret İzni', 0, '#8A6D3B', 1, 30),
('unpaid', 'Ücretsiz İzin', 0, '#6C757D', 1, 40);

INSERT INTO app_settings (setting_key, setting_value) VALUES
('default_annual_allowance_days', '20.00'),
('company_name', 'Server Turizm'),
('app_name', 'Server Turizm İzin Yönetim Sistemi');
