# Server Turizm İzin Yönetim Sistemi

Server Turizm için hafif, güvenli ve cPanel uyumlu izin yönetim sistemi.

## Stack

- PHP 8.x
- MySQL / MariaDB
- HTML / CSS / Vanilla JavaScript
- PDO

## Production target

`https://serverturizm.com.tr/izin/`

## Documentation

- `docs/MASTER-PLAN.md`
- `docs/FOLDER-STRUCTURE.md`
- `docs/STATUS.md`

## Database

- `database/schema.sql`
- `database/seed.sql`

## Important

Production database credentials must not be committed to Git. The runtime config is stored outside the public web root at `/home/<cpanel-user>/izin-private/config.php` by default.
