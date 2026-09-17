# cPanel Deployment Guide

Target: `https://serverturizm.com.tr/izin/`

## 1. PHP version

cPanel > MultiPHP Manager / Select PHP Version

- PHP 8.1+ önerilir.
- `pdo_mysql` aktif olmalıdır.
- `mbstring` aktif olmalıdır.

Production upload öncesi mümkünse Terminal'de:

```bash
find . -name '*.php' -print0 | xargs -0 -n1 php -l
php tests/leave-calculator-test.php
```

Beklenen calculator sonucu: `8 passed, 0 failed`.

Tam kabul listesi: `docs/ACCEPTANCE-TESTS.md`.

## 2. MySQL database oluştur

cPanel > MySQL Databases

1. Yeni database oluştur. Örnek: `cpuser_izin`
2. Yeni database user oluştur. Örnek: `cpuser_izinuser`
3. Güçlü ve benzersiz şifre kullan.
4. User'ı database'e ekle.
5. `ALL PRIVILEGES` ver.

## 3. SQL import

### Fresh install

cPanel > phpMyAdmin

1. Oluşturulan database'i seç.
2. Import bölümünü aç.
3. Önce `database/schema.sql` dosyasını import et.
4. Sonra `database/seed.sql` dosyasını import et.
5. Aşağıdaki tabloların oluştuğunu kontrol et:
   - `users`
   - `leave_types`
   - `annual_allowances`
   - `public_holidays`
   - `leave_requests`
   - `leave_request_days`
   - `app_settings`
   - `login_failures`

### Eski V1 core schema daha önce import edildiyse

Fresh schema'yı tekrar import etme. Sadece:

`database/migrations/001-login-failures.sql`

migration dosyasını bir kez çalıştır.

Not: Gerçek Türkiye resmî tatilleri seed edilmez. Admin panelden doğrulanarak girilir.

## 4. Uygulama dosyalarını yükle

File Manager:

1. `public_html/` içine gir.
2. `izin` klasörü oluştur.
3. `apps/izin-yonetim-sistemi/` içindeki runtime dosyalarını `public_html/izin/` içine yükle.
4. `.htaccess` dosyasının da yüklendiğini doğrula.

Sonuç örneği:

`/home/CPANEL_USER/public_html/izin/index.php`

`.htaccess` şu internal klasörleri web erişimine kapatır: `app`, `config`, `database`, `docs`, `templates`, `tests`.

## 5. Private config oluştur

File Manager'da `public_html` dışındaki home dizinine dön:

`/home/CPANEL_USER/`

Burada:

1. `izin-private` klasörü oluştur.
2. İçine `config.php` oluştur.
3. `config/config.example.php` içeriğini kopyala.
4. DB bilgilerini doldur.
5. `setup_key` değerini en az 32 karakterlik rastgele ve tahmin edilemez bir değer yap.

Production config yolu:

`/home/CPANEL_USER/izin-private/config.php`

Örnek:

```php
<?php
return [
    'app' => [
        'env' => 'production',
        'base_path' => '/izin',
        'timezone' => 'Europe/Istanbul',
        'session_name' => 'server_turizm_izin',
        'setup_key' => 'UZUN_RASTGELE_BIR_DEGER',
    ],
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'cpuser_izin',
        'user' => 'cpuser_izinuser',
        'password' => 'GERCEK_DB_SIFRESI',
        'charset' => 'utf8mb4',
    ],
];
```

Bu dosyayı GitHub'a yükleme.

`setup_key` login rate-limit HMAC anahtarında da kullanıldığı için production boyunca güçlü ve gizli tutulması önerilir.

## 6. İlk Admin oluştur

Tarayıcıda aç:

`https://serverturizm.com.tr/izin/setup-admin.php`

1. Ad soyad gir.
2. Admin e-posta adresini gir.
3. En az 10 karakterlik güçlü şifre gir.
4. Private config'teki `setup_key` değerini gir.
5. `Yönetici Oluştur` seç.

İlk admin oluşturulduktan sonra setup endpoint yeni admin oluşturmaz.

Ek sertleştirme:

- `setup_key` değerini silme; login throttle hashing için güçlü bir secret olarak tutulur.
- İstersen `setup-admin.php` dosyasını production'dan tamamen silebilirsin.

## 7. Login testi

Aç:

`https://serverturizm.com.tr/izin/`

Beklenen:

- login yoksa login ekranına yönlenir,
- admin login sonrası `/izin/admin/dashboard.php` açılır,
- başarısız login generic hata verir,
- aynı email+IP için 8 başarısız denemeden sonra 15 dakika throttle uygulanır.

## 8. İlk production ayarları

Admin panel:

1. Ayarlar > varsayılan yıllık hak değerini kontrol et (`20`).
2. Resmî Tatiller bölümünden doğrulanmış tatilleri ekle.
3. Çalışanlar bölümünden çalışanları ekle.
4. Her çalışan için ilgili yılın izin hakkını kontrol et.
5. İzin Türleri bölümünden dört başlangıç türünü kontrol et.

Not: Bir Leave Type request'lerde kullanıldıktan sonra `Yıllık izin hakkından düş` davranışı tarihsel tutarlılık için değiştirilemez.

## 9. Production güvenlik kontrolü

- HTTPS çalışıyor olmalı.
- DB şifresi public_html içinde olmamalı.
- `display_errors` production'da kapalı olmalı.
- `database/*.sql` URL'den açılmamalı.
- `docs/*.md` URL'den açılmamalı.
- `tests/*.php` URL'den açılmamalı.
- Employee hesabıyla başka employee verisine URL değiştirerek erişilememeli.
- POST formlarında CSRF doğrulaması çalışmalı.
- Response üzerinde CSP / no-store / nosniff header'ları görünmeli.
- HTTPS üzerinde HSTS görünmeli.

## 10. Leave workflow smoke test

Production açılmadan önce en az:

- Friday → Monday = 2 gün,
- public holiday exclusion,
- Half Day,
- overlap block,
- allowance exhaustion,
- approve/reject,
- cross-month report,
- employee/admin authorization

senaryolarını `docs/ACCEPTANCE-TESTS.md` üzerinden doğrula.

## 11. Backup

Canlı kullanımdan önce:

- Uygulama dosyalarının ZIP yedeğini al.
- MySQL database export al.
- Private config'i güvenli şekilde ayrı yedekle.
