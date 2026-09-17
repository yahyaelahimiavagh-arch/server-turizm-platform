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
8. **Tests** — dependency-free PHP CLI regression tests
9. **Documentation** — `docs/`

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
- Aynı email+IP için 15 dakika içinde 8 başarısız login sonrası geçici throttle uygulanır.
- Rate-limit kimlikleri raw email/IP yerine HMAC hash olarak saklanır.

---

## 5. CSRF ve input/output güvenliği

- Tüm POST formlarında CSRF token zorunlu.
- Tüm DB işlemleri PDO prepared statement.
- Tüm kullanıcı girdileri server-side validate edilir.
- HTML output `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` ile escape edilir.
- Production'da `display_errors=0`.
- Hata detayları kullanıcıya gösterilmez.
- DB credentials Git repository veya public web root içinde tutulmaz.
- Internal PHP/SQL/docs/tests klasörleri `.htaccess` ile doğrudan web erişimine kapatılır.
- PHP response'larında `Content-Security-Policy`, `no-store`, `nosniff` ve HTTPS altında HSTS kullanılır.

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
- Geçersiz takvim tarihleri normalize edilmez; request reddedilir

Half Day V1 kuralı:

- `start_date = end_date` olmak zorunda
- `morning` veya `afternoon` seçilir

Örnek:

Friday → Monday, hafta sonunda tatil yoksa = 2 gün.

### Overlap kuralı

Aynı employee için PENDING veya APPROVED request günleri yeni request ile çakışamaz.

İstisna:

- aynı tarihte `Half Day Morning` + `Half Day Afternoon` birlikte kullanılabilir.

Aynı yarım günün iki kez talep edilmesi engellenir.

Concurrent request creation sırasında employee row `FOR UPDATE` ile lock edilir; böylece aynı anda gönderilen iki request overlap veya allowance sınırını race-condition ile aşamaz.

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

Tarihsel tutarlılık kuralı:

- Bir Leave Type en az bir request'te kullanıldıktan sonra `deducts_annual_allowance` davranışı değiştirilemez.
- İsim, renk, sıra ve aktif/pasif durumu değiştirilebilir.

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
8. `login_failures`

Detaylı SQL: `database/schema.sql`

Seed data: `database/seed.sql`

Migration dosyaları: `database/migrations/`

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
7. ilk admin hesabını güvenli setup script ile oluştur
8. HTTPS altında login testi yap
9. `docs/ACCEPTANCE-TESTS.md` senaryolarını çalıştır
10. setup helper istenirse production'dan tamamen sil

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

Implemented in code:

- strict date validation
- overlap protection
- concurrent employee request serialization
- login throttle
- security response headers
- historical leave-type allowance protection
- dependency-free calculator regression tests
- acceptance checklist

Still requires target-environment evidence:

- full PHP syntax lint
- real MySQL/MariaDB import
- runtime auth / CSRF / IDOR tests
- runtime overlap / allowance concurrency tests
- cross-month / cross-year report QA
- mobile responsive QA
- cPanel HTTPS deployment

---

## 14. V1 kabul kriterleri

V1 production accepted sayılırsa:

- employee kendi hesabıyla giriş yapabilir,
- başka çalışanın özel verisini göremez,
- izin talebi oluşturabilir,
- hafta sonu / resmî tatil / half-day hesabı doğru çalışır,
- overlap talepler güvenli şekilde engellenir,
- concurrent talepler allowance sınırını aşamaz,
- admin approve/reject yapabilir,
- allowance doğru düşer,
- pending ayrı görünür,
- aylık/yıllık rapor doğru çıkar,
- public holiday ve leave type admin tarafından yönetilir,
- kullanılan Leave Type'ın allowance davranışı geçmişi değiştirecek şekilde değiştirilemez,
- login brute-force throttle çalışır,
- production config public web root dışında tutulur,
- SQL injection / CSRF / IDOR temel kontrolleri uygulanmıştır,
- syntax lint ve acceptance testleri PASS olur,
- sistem cPanel üzerinde Node/Docker olmadan HTTPS ile çalışır.

---

## 15. Hardening checkpoint — 2026-09-17

Branch: `feat/izin-v1-hardening`

Calculator regression suite, committed logic ile PHP 8.4 üzerinde çalıştırıldı:

`8 passed / 0 failed`

Kapsam:

- Friday → Monday
- full holiday
- half holiday
- half-day period semantics
- cross-year ledger dates
- invalid calendar date
- invalid multi-day Half Day

Production acceptance henüz verilmemiştir. MySQL/cPanel runtime kanıtları `docs/ACCEPTANCE-TESTS.md` tamamlandıktan sonra verilecektir.
