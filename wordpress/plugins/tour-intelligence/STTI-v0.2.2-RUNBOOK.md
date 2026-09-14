# STTI v0.2.2 — JSON Evidence Export Runbook

## Kurulum
1. Mevcut STTI üzerine `Replace Current Version` ile kurun.
2. Public locks'ın OFF kaldığını doğrulayın.

## A — Tüm sistem JSON export
1. `Tour Intelligence → Import / Export` açın.
2. `Tüm JSON Evidence İndir` butonuna basın.
3. İnen `.json` dosyasını ChatGPT'ye yükleyin.
4. Bu dosya preview fixtures + tüm saved private candidates + canonical payload + validation + checksum + tüm audit history + release locks içerir.
5. Export read-only'dir; DB/public write yapmaz.

## B — Henüz save edilmemiş aktif Tour taslağı
1. `Create / Edit Tour` ekranında alanları doldurun.
2. Alt bardaki `Taslak JSON İndir` butonuna basın.
3. İnen JSON dosyasını ChatGPT'ye yükleyin.
4. Böylece formun her sekmesi için ekran görüntüsü göndermek gerekmez.

## Güvenlik / sınırlar
- Public Renderer OFF
- Public Routes OFF
- Sitemap OFF
- Indexation OFF
- Homepage Adapter OFF
- JSON import hâlâ LOCKED
- Delete path yok
