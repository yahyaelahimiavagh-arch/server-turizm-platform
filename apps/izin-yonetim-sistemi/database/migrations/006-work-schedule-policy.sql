INSERT INTO app_settings (setting_key, setting_value) VALUES
(
    'work_schedule_json',
    '{"1":"full_day","2":"full_day","3":"full_day","4":"full_day","5":"full_day","6":"morning","7":"off"}'
),
(
    'leave_full_day_weights_json',
    '{"1":1,"2":1,"3":1,"4":1,"5":1,"6":1,"7":0}'
)
ON DUPLICATE KEY UPDATE setting_value = setting_value;
