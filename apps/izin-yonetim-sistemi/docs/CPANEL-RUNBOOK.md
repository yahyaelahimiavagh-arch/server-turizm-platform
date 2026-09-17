# Server Turizm İzin Yönetim Sistemi — cPanel Production Runbook

Target: `https://serverturizm.com.tr/izin/`

Bu doküman yeni ve boş production kurulumu içindir.

## 1. PHP kontrolü

cPanel > MultiPHP Manager / Select PHP Version

- PHP 8.1 veya üzeri seç.
- `pdo_mysql` aktif olmalı.
- `mbstring` aktif olmalı.

Önerilen: hosting destekliyorsa PHP 8.3 veya 8.4.

## 2. MySQL database oluştur

cPanel > MySQL Databases

1. Yeni database oluştur. Örnek: `CPUSER_izin`.
2. Yeni database user oluştur. Örnek: `CPUSER_izinuser`.
3. Güçlü ve benzersiz bir DB şifresi oluştur.
4. User'ı database'e ekle.
5. `ALL PRIVILEGES` ver.
6. Database name, username ve password'u güvenli bir yerde tut.

## 3. SQL schema ve seed import et

cPanel > phpMyAdmin

1. Yeni database'i seç.
2. Import ekranını aç.
3. Önce `database/schema.sql` import et.
4. Sonra `database/seed.sql` import et.

Yeni kurulumda `database/migrations/001-login-failures.sql` ayrıca çalıştırılmaz; `login_failures` tablosu güncel `schema.sql` içinde zaten vardır.

Import sonrasında şu tablolar görünmelidir:

- `users`
- `leave_types`
- `annual_allowances`
- `public_holidays`
- `leave_requests`
- `leave_request_days`
- `app_settings`
- `login_failures`

Seed sonrasında:

- `default_annual_allowance_days = 20.00`
- En az 4 izin türü
- Yalnız başlangıç `Yıllık İzin` türü yıllık haktan düşecek şekilde tanımlı olmalı.

## 4. Uygulama dosyalarını yükle

File Manager > `public_html/`

1. `izin` klasörü oluştur.
2. Repository içindeki `apps/izin-yonetim-sistemi/` klasörünün içeriğini `public_html/izin/` içine yükle.
3. `.htaccess` dosyasının gerçekten yüklendiğini kontrol et.
4. `app`, `database`, `docs`, `tests`, `tools` klasörlerini URL ile açmaya çalışma; 403/404 beklenir.

Beklenen ana yol:

`/home/CPANEL_USER/public_html/izin/index.php`

## 5. Private config oluştur

File Manager'da `public_html` dışına çık:

`/home/CPANEL_USER/`

Burada:

1. `izin-private` klasörü oluştur.
2. İçinde `config.php` oluştur.
3. `config/config.example.php` içeriğini temel al.
4. Gerçek database bilgilerini doldur.
5. `setup_key` için en az 32 karakterlik rastgele bir değer kullan; 48–64 karakter tercih edilir.

Beklenen dosya:

`/home/CPANEL_USER/izin-private/config.php`

Örnek yapı:

```php
<?php
return [
    'app' => [
        'env' => 'production',
        'base_path' => '/izin',
        'timezone' => 'Europe/Istanbul',
        'session_name' => 'server_turizm_izin',
        'setup_key' => 'BURAYA_UZUN_RASTGELE_BIR_DEGER',
    ],
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'CPUSER_izin',
        'user' => 'CPUSER_izinuser',
        'password' => 'GERCEK_DB_SIFRESI',
        'charset' => 'utf8mb4',
    ],
];
```

Bu dosyayı `public_html` içine koyma ve GitHub'a commit etme.

## 6. Preflight çalıştır

cPanel Terminal / SSH varsa:

```bash
php /home/CPANEL_USER/public_html/izin/tools/preflight.php
```

Başarılı sonuç en sonda:

```text
PREFLIGHT PASS
```

Preflight şunları kontrol eder:

- PHP >= 8.1
- PDO / pdo_mysql / mbstring
- private config erişimi
- `/izin` base path ve Europe/Istanbul timezone
- güçlü setup key
- MySQL bağlantısı
- utf8mb4 connection
- tüm gerekli tablolar
- default allowance seed
- leave type seed

Preflight DB credentials değerlerini output'a yazmaz.

cPanel Terminal yoksa bu adımı atla ve sonraki browser smoke test adımlarını uygula.

## 7. İlk Admin oluştur

Tarayıcı:

`https://serverturizm.com.tr/izin/setup-admin.php`

1. Admin ad/soyad gir.
2. Admin e-posta gir.
3. En az 10 karakterlik güçlü bir şifre seç.
4. Private config'teki `setup_key` değerini gir.
5. `Yönetici Oluştur` butonuna bas.

Admin oluşturulduktan sonra endpoint yeni admin oluşturmayı reddeder.

Ek güvenlik için ilk admin oluşturulduktan sonra:

- `setup_key` değerini private config'te değiştir veya kaldır,
- tercihen `setup-admin.php` dosyasını production'dan sil.

## 8. Login smoke test

`https://serverturizm.com.tr/izin/`

Beklenen:

- login yoksa login ekranı,
- doğru admin bilgileri ile giriş,
- admin girişinden sonra `/izin/admin/dashboard.php`,
- logout sonrasında korumalı sayfalara erişilememe.

## 9. İlk Employee test hesabı

Admin > Çalışanlar

1. Bir test employee oluştur.
2. 2026 için yıllık hakkın 20 gün geldiğini kontrol et.
3. Test employee ile login yap.
4. Dashboard'da kendi verilerini görmesini kontrol et.
5. Başka employee verilerine URL değiştirerek erişemediğini kontrol et.

## 10. Leave workflow smoke test

Test employee ile:

1. Normal bir tam gün Yıllık İzin talebi oluştur.
2. Friday→Monday tarih aralığında 2 gün çıktığını kontrol et.
3. Admin hesabıyla pending talebi gör.
4. Approve et.
5. Employee dashboard'da `Onaylanan` ve `Kalan` değerlerini kontrol et.
6. İkinci kez aynı tarih için overlap talebi gönder; sistem reddetmeli.
7. Aynı gün için morning ve afternoon ayrı half-day taleplerinin mantığını kontrol et.

## 11. Resmî tatil smoke test

Admin > Resmî Tatiller

Production tatil listesini topluca girmeden önce geçici test tarihi kullan:

1. Bir hafta içi tarihi tam gün tatil olarak ekle.
2. O tarihi kapsayan izin talebinde günün sayılmadığını doğrula.
3. Bir başka tarihi half-day tatil olarak tanımla.
4. Morning/afternoon davranışını doğrula.
5. Test tatillerini sil.

Gerçek Türkiye resmî tatilleri yalnız doğrulandıktan sonra production'a eklenir.

## 12. Reports smoke test

Admin > Raporlar

- employee + year filtresi
- all employees + year görünümü
- monthly dağılım
- Yıllık / Raporlu / Mazeret / Ücretsiz breakdown
- 0.5 günlerin doğru görünmesi
- yıl sonunu geçen bir izin talebinin iki yılın raporuna doğru ayrılması

## 13. Güvenlik negatif testleri

Kontrol et:

- Employee `/admin/` sayfalarında 403 alıyor.
- Employee URL parametresi değiştirerek başka kullanıcı kaydı göremiyor.
- CSRF token olmadan POST işlemi başarısız oluyor.
- `database/schema.sql` URL ile indirilemiyor.
- `docs/`, `tests/`, `tools/` URL ile açılamıyor.
- Hatalı login denemeleri sonrasında rate limit devreye giriyor.
- Production hata ekranında raw exception / DB password görünmüyor.

## 14. Mobile QA

En az:

- 360 px genişlik
- Android Chrome
- desktop Chrome

Kontrol:

- login form
- dashboard cards
- leave form
- admin pending cards
- employee table
- reports table
- calendar

## 15. Go-live öncesi backup

- `public_html/izin/` ZIP backup
- MySQL database export
- private config ayrı güvenli backup

## 16. Go-live kabul kriteri

Canlı kabul için aşağıdakilerin tamamı gerekir:

- preflight PASS veya manuel eşdeğer kontroller
- login/logout PASS
- employee isolation PASS
- request/approve/reject PASS
- overlap PASS
- allowance PASS
- holiday/half-day PASS
- reports PASS
- CSRF/IDOR negative PASS
- mobile QA PASS
- HTTPS PASS
- verified Türkiye public holidays loaded

Bu noktadan önce sistem production-ready olarak işaretlenmez.
