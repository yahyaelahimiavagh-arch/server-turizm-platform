# V1 Folder Structure

Production'da bu klasörün içeriği `public_html/izin/` altına yerleştirilir.

```text
izin/
├── .htaccess
├── index.php
├── login.php
├── logout.php
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
│   └── holidays.php
│
├── app/
│   ├── bootstrap.php
│   ├── config.php
│   ├── db.php
│   ├── auth.php
│   ├── csrf.php
│   ├── helpers.php
│   ├── validation.php
│   ├── leave-calculator.php
│   └── repositories/
│       ├── UserRepository.php
│       ├── LeaveRepository.php
│       └── ReportRepository.php
│
├── templates/
│   ├── header.php
│   ├── footer.php
│   ├── flash.php
│   └── nav.php
│
├── assets/
│   ├── css/
│   │   └── app.css
│   └── js/
│       └── app.js
│
├── database/
│   ├── schema.sql
│   └── seed.sql
│
├── docs/
│   ├── MASTER-PLAN.md
│   ├── FOLDER-STRUCTURE.md
│   ├── DEPLOYMENT.md
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

## Web erişimi kapalı klasörler

Aşağıdaki klasörler `.htaccess` ile doğrudan HTTP erişimine kapatılır:

- `app/`
- `config/`
- `database/`
- `docs/`
- `templates/`

PHP sayfaları bu dosyaları filesystem üzerinden include eder.
