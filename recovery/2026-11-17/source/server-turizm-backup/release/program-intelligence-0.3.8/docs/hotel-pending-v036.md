# v0.3.6 — Explicit pending hotel approval

The administrator action `Approve with hotel pending` verifies the candidate, records the actor and time, adds the exact Turkish pending label only to unnamed stays, and keeps `seo.index_policy=noindex`. It does not replace supplied hotel names or Stable Hotel IDs. The existing READY threshold remains 90. Complete, verified pending stays qualify for the existing seven accommodation completeness points only when exact dates, duration, stay sequence and destinations agree.

A receipt binds the pre-approval checksum, resulting checksum, source key and source hash. Direct Sync 0.1.5 uses this receipt for precisely the approval transition. Other stale checksums still fail. Existing human-approved pending stays are carried through source updates only for the same sequence and destination, while new Hotel IDs must resolve and pass the original hotel gates.

Publishing Integration 0.4.20 projects pending text, city, nights and dates without a hotel entity, photo, star rating, map, gallery or Hotel Intelligence relation. Pending pages cannot enter indexable mode. The exact 0.4.19 -> 0.4.20 transition preserves the existing registry and runtime gates. No data is changed during plugin replacement.

Validation: PHP 8.3.6, 66 integration assertions, PHP syntax, ST-TDE 1.0.0 schema for both canonical pending candidates, frontend isolation, admin bootstrap.

Run the bundled integration test with PHP CLI and all three unpacked plugin folders:

```sh
php -n server-turizm-program-intelligence-v0.1.0-shadow/tests/test_hotel_pending.php server-turizm-program-intelligence-v0.1.0-shadow/tests/hotel-pending-source.json server-turizm-program-publishing-integration-v0.4.9-temporal-recovery-hotfix /tmp/hotel-pending-preview direct-sync-foundation
```

Create `/tmp/hotel-pending-preview` before running. Tests use in-memory WordPress stubs and do not connect to a live site.
