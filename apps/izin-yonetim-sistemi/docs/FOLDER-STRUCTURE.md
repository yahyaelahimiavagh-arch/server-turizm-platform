# V1 Folder Structure

Production'da bu klasörün runtime içeriği `public_html/izin/` altına yerleştirilir.

```text
izin/
├── .htaccess
├── index.php
├── login.php
├── logout.php
├── setup-admin.php
├── dashboard.php
├── leave-new.php
├── my-leaves.php
├── calendar.php
│
├── admin/
│   ├── dashboard.php
│   ├── requests.php
│   ├── employees.php
│   ├── employee-edit.php
│   ├── reports.php
│   ├── leave-types.php
│   ├── holidays.php
│   └── settings.php
│
├── app/
│   ├── bootstrap.php
│   ├── config.php
│   ├── db.php
│   ├── auth.php
│   ├── csrf.php
│   ├── helpers.php
│   ├── login-rate-limit.php
│   ├── leave-calculator.php
│   └── repositories/
│       ├── UserRepository.php
│       ├── LeaveRepository.php
│       └── ReportRepository.php
│
├── templates/
│   ├── header.php
│   └── footer.php
│
├── assets/
│   ├── css/
│   │   └── app.css
│   └── js/
│       └── app.js
│
├── database/
│   ├── schema.sql
│   ├── seed.sql
│   └── migrations/
│       └── 001-login-failures.sql
│
├── tests/
│   └── leave-calculator-test.php
│
├── tools/
│   └── preflight.php
│
├── docs/
│   ├── MASTER-PLAN.md
│   ├── FOLDER-STRUCTURE.md
│   ├── DEPLOYMENT.md
│   ├── CPANEL-RUNBOOK.md
│   ├── ACCEPTANCE-TESTS.md
│   └── STATUS.md
│
└── config/
    └── config.example.php
```

## Production secret config

DB şifresi repo içinde tutulmaz.

Production dosyası:

```text
/home/<cpanel-user>/izin-private/config.php
```

Uygulama önce `IZIN_CONFIG_FILE` environment variable'ını, yoksa `dirname($_SERVER['DOCUMENT_ROOT']) . '/izin-private/config.php'` yolunu kontrol eder.

CLI preflight script aynı production config'i kullanır ve cPanel Terminal üzerinden çalıştırılabilir:

```bash
php /home/<cpanel-user>/public_html/izin/tools/preflight.php
```

## Web erişimi kapalı klasörler

Aşağıdaki klasörler `.htaccess` ile doğrudan HTTP erişimine kapatılır:

- `app/`
- `config/`
- `database/`
- `docs/`
- `templates/`
- `tests/`
- `tools/`

PHP sayfaları gerekli internal dosyaları filesystem üzerinden include eder.

## Production note

İlk admin oluşturulduktan sonra `setup-admin.php` kendi kontrolü nedeniyle yeni admin oluşturmaz. Ek sertleştirme için production kurulumundan sonra dosya tamamen silinebilir.
