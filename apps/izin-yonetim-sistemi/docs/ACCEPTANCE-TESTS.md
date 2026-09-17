# V1 Acceptance Tests

Bu kontrol listesi production deploy öncesi çalıştırılır.

## 1. PHP syntax lint

Proje kökünde:

```bash
find . -name '*.php' -print0 | xargs -0 -n1 php -l
```

Beklenen: tüm PHP dosyaları `No syntax errors detected` sonucu vermeli.

## 2. Dependency-free calculator regression tests

```bash
php tests/leave-calculator-test.php
```

Beklenen:

```text
Result: 8 passed, 0 failed
```

Kapsanan senaryolar:

- Friday → Monday = 2 iş günü
- tam gün resmî tatil hariç tutulur
- yarım gün tatilde yalnızca 0.5 iş günü kalır
- yarım gün izin + yarım gün tatil kombinasyonu
- yıl geçişi (`2026-12-31` → `2027-01-01`)
- geçersiz takvim tarihi reddi
- birden fazla güne yayılan Half Day reddi

## 3. Fresh database smoke test

1. Boş MySQL/MariaDB database oluştur.
2. `database/schema.sql` import et.
3. `database/seed.sql` import et.
4. Aşağıdaki tabloları doğrula:
   - `users`
   - `leave_types`
   - `annual_allowances`
   - `public_holidays`
   - `leave_requests`
   - `leave_request_days`
   - `app_settings`
   - `login_failures`
5. Foreign key ve unique index hatası olmadığını doğrula.

## 4. Authentication smoke test

- İlk admin `setup-admin.php` üzerinden yalnızca bir kez oluşturulabilmeli.
- Yanlış setup key admin oluşturmamalı.
- Doğru email/password login olmalı.
- Yanlış password generic hata vermeli.
- 8 başarısız denemeden sonra aynı email+IP 15 dakika bloke olmalı.
- Başarılı login session ID'yi yenilemeli.
- Logout yalnızca POST + geçerli CSRF ile çalışmalı.
- Pasif employee login olamamalı.

## 5. Authorization / IDOR negative tests

Employee hesabı ile:

- `/admin/dashboard.php` → 403
- `/admin/employees.php` → 403
- `/admin/requests.php` → 403
- başka employee'nin request ID'si URL/form ile okunamamalı
- kendi dashboard ve request listesi yalnızca session user ID'si ile filtrelenmeli

POST formunda CSRF token silinirse/değiştirilirse işlem gerçekleşmemeli.

## 6. Leave calculation runtime tests

Admin test tatilleri ekler, sonra employee talep oluşturur.

### A — Weekend

Friday → Monday tam gün talep: `2.0` gün.

### B — Full holiday

Aradaki bir hafta içi günü tam gün tatil yap: o gün `0` sayılmalı.

### C — Half holiday

Öğleden sonra yarım gün tatil tanımlı bir hafta içi gününde:

- full-day request → `0.5`
- morning half-day request → `0.5`
- afternoon half-day request → hesaplanabilir izin günü `0`

### D — Cross year

Yıl sonundan sonraki yıla geçen request, `leave_request_days` üzerinden her günü doğru yıla yazmalı.

## 7. Overlap protection

Aynı employee için:

- Pending Full Day + aynı gün yeni Full Day → reddedilmeli.
- Approved Full Day + aynı gün yeni Half Day → reddedilmeli.
- Pending Half Day Morning + yeni Half Day Morning → reddedilmeli.
- Pending Half Day Morning + yeni Half Day Afternoon → kabul edilebilmeli.
- Rejected request aynı tarihi bloke etmemeli.

## 8. Allowance tests

Employee için yıl hakkını `2.0` yap.

- `1.0` pending oluştur → Bekleyen `1.0`.
- ikinci `1.0` pending → kabul.
- üçüncü `0.5` annual request → bakiye yetersiz hatası.
- ilk talebi reject et → tekrar `0.5` oluşturulabilmeli.
- approved + pending toplamı entitlement değerini aşmamalı.

İki browser/tab üzerinden aynı anda request gönderildiğinde employee row lock nedeniyle iki işlem de toplam hakkı aşacak şekilde başarılı olmamalı.

## 9. Leave type history protection

Bir Leave Type ile en az bir request oluştur.

Sonra Admin > İzin Türleri bölümünde `Yıllık izin hakkından düş` değerini tersine çevirmeye çalış.

Beklenen: değişiklik reddedilmeli; isim, renk ve aktif/pasif durumu değiştirilebilmeli.

## 10. Reports

- Ocak–Aralık toplamları yalnızca approved ledger günlerini göstermeli.
- Half Day `0.5` görünmeli.
- Cross-month request günleri gerçek aylarına dağılmalı.
- Cross-year request günleri gerçek yıllarına dağılmalı.
- Yıllık Hak / Onaylanan / Bekleyen / Kalan tanımları dashboard ile aynı olmalı.

## 11. Web exposure

Tarayıcıdan doğrudan erişim denenir:

- `/izin/database/schema.sql` → erişim yok
- `/izin/docs/STATUS.md` → erişim yok
- `/izin/app/db.php` → erişim yok
- `/izin/tests/leave-calculator-test.php` → erişim yok

Response header kontrolü:

- `Content-Security-Policy`
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: SAMEORIGIN`
- `Cache-Control: no-store`
- HTTPS üzerinde `Strict-Transport-Security`

## 12. Mobile QA

En az:

- 360 px genişlik
- 390 px genişlik
- desktop 1366 px+

Kontrol:

- Login
- Employee dashboard
- New request form
- My leaves table
- Admin dashboard
- Pending request approval
- Employee edit
- Reports

Hiçbir kritik buton veya tablo erişilemez durumda olmamalı.

## Acceptance rule

Production accepted sayılması için:

- syntax lint PASS,
- calculator regression tests PASS,
- fresh DB import PASS,
- auth/CSRF/IDOR tests PASS,
- overlap/allowance tests PASS,
- reports PASS,
- mobile QA PASS,
- cPanel HTTPS deployment PASS

olmalıdır.
