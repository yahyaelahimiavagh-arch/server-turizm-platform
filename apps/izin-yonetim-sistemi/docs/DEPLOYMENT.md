# cPanel Deployment Guide

Target: `https://serverturizm.com.tr/izin/`

## 1. PHP version

cPanel > MultiPHP Manager / Select PHP Version

- PHP 8.1+ önerilir.
- `pdo_mysql` aktif olmalıdır.
- `mbstring` aktif olmalıdır.

## 2. MySQL database oluştur

cPanel > MySQL Databases

1. Yeni database oluştur. Örnek: `cpuser_izin`
2. Yeni database user oluştur. Örnek: `cpuser_izinuser`
3. Güçlü ve benzersiz şifre kullan.
4. User'ı database'e ekle.
5. `ALL PRIVILEGES` ver.

## 3. SQL import

cPanel > phpMyAdmin

1. Oluşturulan database'i seç.
2. Import bölümünü aç.
3. Önce `database/schema.sql` dosyasını import et.
4. Sonra `database/seed.sql` dosyasını import et.
5. `users`, `leave_types`, `annual_allowances`, `public_holidays`, `leave_requests`, `leave_request_days`, `app_settings` tablolarının oluştuğunu kontrol et.

Not: Gerçek Türkiye resmî tatilleri seed edilmez. Admin panelden doğrulanarak girilir.

## 4. Uygulama dosyalarını yükle

File Manager:

1. `public_html/` içine gir.
2. `izin` klasörü oluştur.
3. `apps/izin-yonetim-sistemi/` içindeki runtime dosyalarını `public_html/izin/` içine yükle.
4. `.htaccess` dosyasının da yüklendiğini doğrula.

Sonuç örneği:

`/home/CPANEL_USER/public_html/izin/index.php`

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

- `setup_key` değerini private config'ten kaldırabilir veya rastgele yeni bir değerle değiştirebilirsin.
- İstersen `setup-admin.php` dosyasını production'dan silebilirsin.

## 7. Login testi

Aç:

`https://serverturizm.com.tr/izin/`

Beklenen:

- login yoksa login ekranına yönlenir,
- admin login sonrası `/izin/admin/dashboard.php` açılır.

## 8. İlk production ayarları

Admin panel:

1. Ayarlar > varsayılan yıllık hak değerini kontrol et (`20`).
2. Resmî Tatiller bölümünden doğrulanmış tatilleri ekle.
3. Çalışanlar bölümünden 6 çalışanı ekle.
4. Her çalışan için ilgili yılın izin hakkını kontrol et.
5. İzin Türleri bölümünden dört başlangıç türünü kontrol et.

## 9. Production güvenlik kontrolü

- HTTPS çalışıyor olmalı.
- DB şifresi public_html içinde olmamalı.
- `display_errors` production'da kapalı olmalı.
- `database/*.sql` URL'den açılmamalı.
- `docs/*.md` URL'den açılmamalı.
- Employee hesabıyla başka employee verisine URL değiştirerek erişilememeli.
- POST formlarında CSRF doğrulaması çalışmalı.

## 10. Backup

Canlı kullanımdan önce:

- Uygulama dosyalarının ZIP yedeğini al.
- MySQL database export al.
- Private config'i güvenli şekilde ayrı yedekle.
