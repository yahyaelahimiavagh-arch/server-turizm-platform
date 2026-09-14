# Server Turizm AI Assistant - Modular Build

این نسخه همان پلاگین قبلی است که به فایل‌های کوچک‌تر تقسیم شده است.

## Structure

- `serverturizm-ai-assistant.php` — loader اصلی پلاگین
- `includes/settings-core.php` — helperهای تنظیمات پایه
- `includes/core-helpers.php` — helperهای عمومی
- `includes/logging.php` — جدول لاگ و ثبت eventها
- `includes/programs.php` — Google Sheet برنامه‌ها، intentها و کارت‌ها
- `includes/faq.php` — FAQ Sheet و matching
- `includes/rate-limit.php` — anti-spam، rate limit و Gemini cooldown
- `includes/gemini.php` — اتصال Gemini API
- `includes/admin-data.php` — helperهای آمار داشبورد
- `includes/rest-handlers.php` — handlerهای REST API
- `includes/rest-routes.php` — ثبت routeهای REST
- `admin/admin.php` — Dashboard، Settings و Tools
- `frontend/frontend.php` — لود CSS/JS و HTML ویجت

## مهم

پوشه `assets/` فعلی روی سایت را حذف نکن. فایل‌های CSS و JS فعلی تو باید همان‌جا بمانند:

- `assets/stai-widget.css`
- `assets/stai-widget.js`

این ZIP فقط PHPها را ماژولار کرده و یک فایل README داخل assets گذاشته است.

## wp-config.php

اگر این constantها را داری، همان‌طور در `wp-config.php` نگه دار:

```php
define('STAI_SHEET_CSV_URL', '...');
define('STAI_FAQ_CSV_URL', '...');
define('STAI_GEMINI_API_KEY', '...');
define('STAI_GEMINI_MODEL', 'gemini-2.5-flash');
define('STAI_WHATSAPP_NUMBER', '905XXXXXXXXX');
```

برای reCAPTCHA v3 در آینده:

```php
define('STAI_RECAPTCHA_SITE_KEY', '...');
define('STAI_RECAPTCHA_SECRET_KEY', '...');
```
