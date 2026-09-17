# Server Turizm İzin Yönetim Sistemi — V1 Master Plan

## 1. Amaç

Server Turizm için yaklaşık 6 çalışanı yönetecek, hızlı, güvenli ve cPanel üzerinde doğrudan çalışacak bağımsız bir izin yönetim sistemi geliştirmek.

Production hedefi:

`https://serverturizm.com.tr/izin/`

Teknoloji sınırları:

- PHP 8.x
- MySQL / MariaDB
- HTML5
- CSS3
- Vanilla JavaScript
- PDO
- Harici framework yok
- Node.js, Docker, PostgreSQL yok
- Gereksiz dependency yok

Arayüz dili: Türkçe.

Tasarım: Server Turizm kurumsal teması — Navy `#071B4D` / `#0B1B2E`, Gold `#C9A227` / `#D4AF37`, ivory/white.

---

## 2. V1 kapsamı

### Employee

- E-posta + şifre ile giriş
- Kendi dashboard'u
- Yıllık izin hakkı / onaylanan / bekleyen / kalan değerleri
- Yeni izin talebi
- Kendi taleplerini listeleme
- Kendi takvimi
- Kendi izin türü bazlı istatistikleri

### Admin / Manager

- Admin dashboard
- Çalışan ekleme, düzenleme, pasifleştirme
- İzin taleplerini görme
- Onaylama / reddetme
- Çalışan + yıl bazında yıllık izin hakkı tanımlama
- İzin türlerini yönetme
- Resmî tatilleri yönetme
- Aylık / yıllık raporlar
- Tüm onaylı izinleri takvimde görme

V1 dışı / sonraki sürüm:

- CSV / Excel export
- Gelişmiş takvim kütüphanesi
- E-posta / WhatsApp bildirimleri
- Çok seviyeli onay akışı
- Departman yapısı
- Bordro / HR entegrasyonu

---

## 3. Mimari

V1 için klasik, küçük ve okunabilir bir PHP page-controller mimarisi kullanılacak.

Her public sayfa küçük bir controller görevi görecek; ortak güvenlik, DB, auth, validation ve izin hesaplama mantığı `app/` altında tutulacak.

Katmanlar:

1. **Entry pages** — `index.php`, `login.php`, `dashboard.php`, vb.
2. **Admin pages** — `admin/`
3. **Application core** — auth, CSRF, DB, validation, leave calculation
4. **Data access** — PDO prepared statements
5. **Views / shared UI** — `templates/`
6. **Static assets** — `assets/`
7. **Database definition** — `database/`
8. **Documentation** — `docs/`

V1'de custom router, ORM, service container veya template engine kullanılmayacak.

---

## 4. Authentication ve authorization

- Şifreler sadece `password_hash()` ile saklanır.
- Giriş kontrolü `password_verify()` ile yapılır.
- Login sonrası session ID regenerate edilir.
- Session cookie: `Secure`, `HttpOnly`, `SameSite=Lax`.
- `session.use_strict_mode=1`.
- Employee sayfalarında `require_login()`.
- Admin sayfalarında `require_admin()`.
- Employee hiçbir endpoint'te başka kullanıcının `user_id` değerini URL'den vererek veri okuyamaz.
- Employee sorguları daima session'daki kullanıcı ID'sine bağlanır.

---

## 5. CSRF ve input/output güvenliği

- Tüm POST formlarında CSRF token zorunlu.
- Tüm DB işlemleri PDO prepared statement.
- Tüm kullanıcı girdileri server-side validate edilir.
- HTML output `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` ile escape edilir.
- Production'da `display_errors=0`.
- Hata detayları kullanıcıya gösterilmez.
- DB credentials Git repository veya public web root içinde tutulmaz.

Production config yolu varsayılan olarak:

`/home/<cpanel-user>/izin-private/config.php`

Uygulama bunu `dirname($_SERVER['DOCUMENT_ROOT']) . '/izin-private/config.php'` üzerinden bulur. İstenirse `IZIN_CONFIG_FILE` environment variable ile override edilebilir.

---

## 6. İzin günü hesaplama modeli

Ana kural:

- Cumartesi: sayılmaz
- Pazar: sayılmaz
- Tam gün resmî tatil: sayılmaz
- Yarım gün resmî tatil: uygun durumda 0.5 sayılır
- Half Day talep: en fazla 0.5 gün

Half Day V1 kuralı:

- `start_date = end_date` olmak zorunda
- `morning` veya `afternoon` seçilir

Örnek:

Friday → Monday, hafta sonunda tatil yoksa = 2 gün.

### Neden `leave_request_days` tablosu var?

Her talebin hesaplanan çalışma günleri ayrı satırlar olarak snapshot şeklinde tutulur.

Örnek:

- 2026-12-31 = 1.0
- 2027-01-04 = 1.0

Böylece:

- yıl geçişleri doğru raporlanır,
- aylık raporlar doğru çıkar,
- 0.5 günler doğru toplanır,
- aynı `requested_days` değerini birden fazla raporda yanlış bölüştürme riski olmaz,
- sonradan tatil listesi değişse bile onaylanmış geçmiş kayıt sessizce değişmez.

Pending bir talep düzenlenirse gün satırları yeniden hesaplanır.

---

## 7. Yıllık izin bakiye tanımları

Sadece `leave_types.deducts_annual_allowance = 1` olan izin türleri yıllık haktan düşer.

Başlangıçta bu yalnızca `Yıllık İzin` olacaktır.

Bir çalışan/yıl için:

- **Yıllık Hak** = `annual_allowances.entitlement_days`
- **Onaylanan** = o yıl içindeki APPROVED + allowance düşen `leave_request_days.day_value` toplamı
- **Bekleyen** = o yıl içindeki PENDING + allowance düşen `leave_request_days.day_value` toplamı
- **Kalan** = `Yıllık Hak - Onaylanan`
- **Onay sonrası kullanılabilir** = `Yıllık Hak - Onaylanan - Bekleyen`

UI'da ana `Kalan` değerinde pending ikinci kez düşülmez. Bekleyen ayrı gösterilir. Böylece double count oluşmaz.

Admin isterse pending dahil kullanılabilir değeri de ayrı bilgi olarak görebilir.

Default yıllık hak hard-code edilmez. `app_settings.default_annual_allowance_days` başlangıçta `20.00` olarak seed edilir.

---

## 8. Durum akışı

Yeni talep:

`PENDING`

Admin kararı:

`PENDING -> APPROVED`

veya

`PENDING -> REJECTED`

V1'de employee approved/rejected talebi değiştiremez.

`processed_by`, `processed_at`, `admin_note` karar audit bilgisini tutar.

---

## 9. Database tabloları

V1 tabloları:

1. `users`
2. `leave_types`
3. `annual_allowances`
4. `public_holidays`
5. `leave_requests`
6. `leave_request_days`
7. `app_settings`

Detaylı SQL: `database/schema.sql`

Seed data: `database/seed.sql`

---

## 10. Raporlama

Rapor kaynağı `leave_request_days` olacaktır.

Admin filtreleri:

- year
- employee / all employees

Aylık toplamlar `YEAR(leave_date)` ve `MONTH(leave_date)` üzerinden hesaplanır.

İzin türü breakdown, request + leave_type join ile hesaplanır.

Bu model ileride CSV/Excel export için aynı query katmanını tekrar kullanmaya uygundur.

---

## 11. Calendar

V1 Core tamamlandıktan sonra basit aylık takvim yapılır.

Employee:

- sadece kendi izinleri

Admin:

- tüm APPROVED izinler

Renk:

- `leave_types.color_hex`

V1 ilk sürümde harici calendar dependency zorunlu değildir.

---

## 12. Deployment modeli

Web dosyaları:

`public_html/izin/`

Private config:

`/home/<cpanel-user>/izin-private/config.php`

Kurulum sırası:

1. cPanel MySQL database oluştur
2. DB user oluştur ve database'e bağla
3. `database/schema.sql` import et
4. `database/seed.sql` import et
5. proje dosyalarını `public_html/izin/` içine yükle
6. `/home/<cpanel-user>/izin-private/config.php` oluştur
7. ilk admin hesabını güvenli setup script veya tek kullanımlık CLI/PHP helper ile oluştur
8. HTTPS altında login testi yap
9. setup helper varsa sil/devre dışı bırak

---

## 13. V1 geliştirme sırası

### Step 01 — Foundation

- Master Plan
- Database schema
- Folder structure
- config loader
- PDO connection
- session security
- helper functions
- base layout

### Step 02 — Authentication

- login
- logout
- auth guards
- password hashing
- first admin bootstrap method

### Step 03 — Employee Core

- dashboard
- allowance cards
- create leave request
- day calculator
- my leaves

### Step 04 — Admin Core

- admin dashboard
- pending requests
- approve/reject
- employees CRUD
- allowance per employee/year

### Step 05 — Settings

- leave types CRUD
- public holidays CRUD
- default allowance setting

### Step 06 — Reports

- employee/year report
- all employees/year report
- monthly matrix
- leave type breakdown

### Step 07 — Calendar

- employee calendar
- admin approved-leave calendar

### Step 08 — Production hardening

- security review
- IDOR tests
- CSRF tests
- validation edge cases
- date calculation tests
- production config/error settings
- cPanel deployment checklist

---

## 14. V1 kabul kriterleri

V1 tamamlanmış sayılırsa:

- employee kendi hesabıyla giriş yapabilir,
- başka çalışanın özel verisini göremez,
- izin talebi oluşturabilir,
- hafta sonu / resmî tatil / half-day hesabı doğru çalışır,
- admin approve/reject yapabilir,
- allowance doğru düşer,
- pending ayrı görünür,
- aylık/yıllık rapor doğru çıkar,
- public holiday ve leave type admin tarafından yönetilir,
- production config public web root dışında tutulur,
- SQL injection / CSRF / IDOR temel kontrolleri uygulanmıştır,
- sistem cPanel üzerinde Node/Docker olmadan çalışır.
