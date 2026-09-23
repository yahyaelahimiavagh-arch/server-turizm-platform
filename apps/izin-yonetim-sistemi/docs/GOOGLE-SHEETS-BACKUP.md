# Google Sheets One-Way Backup

## Purpose

This integration is a backup/mirror, not an application dependency.

- MySQL remains authoritative.
- The website writes outbound to Google Sheets.
- Google Sheets never calls the website and receives no website credentials.
- Google API failures never block leave requests or approvals; events remain in the local sync queue.
- Passwords, password hashes, sessions, API tokens, uploaded medical documents and free-text employee/admin notes are excluded from the Sheet projection.

## Workbook layout

One spreadsheet is used.

System tabs:

- `_INDEX` — employee backup registry and archive state.
- `_EVENTS` — idempotent event UUID projection from the local queue.
- `_SYNC_LOG` — recent queue state and errors.

Employee tabs:

- `EMP-000001 - Name`
- deleted employees are retained as `DELETED - EMP-000001 - Name`.

Each employee tab contains profile fields, service-year entitlements, annual-leave balance and leave-request history. Attachments are represented only as `Var/Yok`.

## Authentication

Use a Google Cloud service account with Sheets API access.

The JSON credential file must be stored outside `public_html`, e.g.

`/home/<cpanel-user>/izin-private/google-service-account.json`

Private config:

```php
'google_sheets_backup' => [
    'credentials_file' => '/home/<cpanel-user>/izin-private/google-service-account.json',
],
```

Create the Google Sheet manually and share it with the service-account email as **Editor**. The spreadsheet ID or URL is then saved from the admin UI.

## Queue and worker

Application writes only to `google_sheet_sync_queue` during normal requests.

The worker can be run manually from Admin → Yedek or with cPanel Cron:

```bash
php ~/public_html/izin/tools/google-sheets-sync.php --limit=50
```

Recommended cadence: every 5 minutes.

Retries are bounded and exponential. A MySQL named lock prevents concurrent workers.

## Deletion behavior

Permanent deletion creates a final archive snapshot before the local employee data is removed. The employee Google tab is never deleted by the backup engine; it is renamed to the `DELETED - ...` archive form.

## Initial activation

1. Apply migration `008-google-sheets-backup.sql`.
2. Create a Google Cloud service account and enable Google Sheets API.
3. Put the JSON credential outside `public_html`.
4. Create a blank spreadsheet and share it with the service-account email as Editor.
5. Admin → Yedek: enter the spreadsheet URL/ID and enable backup.
6. Click **Tüm Çalışanları Kuyruğa Al**.
7. Click **Şimdi Senkronize Et**.
8. Configure cPanel Cron after runtime acceptance.
