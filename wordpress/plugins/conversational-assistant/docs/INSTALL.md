# STCA v1.0.0 — Installation / Go-Live Runbook

## 1. Install package

Install the verified ZIP in WordPress and activate **Server Turizm Conversational Assistant**. Do not enable Instagram yet.

Expected admin state:

- Instagram Master: OFF
- Program Intelligence: AVAILABLE
- Hotel Intelligence: AVAILABLE
- Tour Intelligence: AVAILABLE
- Simulator works with live canonical data

## 2. Add secrets to wp-config.php

```php
define('STCA_INSTAGRAM_ENABLED', false);
define('STCA_META_GRAPH_VERSION', 'v26.0');
define('STCA_META_VERIFY_TOKEN', 'LONG_RANDOM_VALUE');
define('STCA_META_APP_SECRET', '...');
define('STCA_IG_ACCESS_TOKEN', '...');
define('STCA_IG_ACCOUNT_ID', '...');
```

Do not paste secrets into Git, Google Sheets or WordPress admin fields.

## 3. Meta app requirements

Use an Instagram Professional account and configure Instagram messaging with the permission required by Meta for business messaging. Set the callback URL to:

`https://www.serverturizm.com.tr/wp-json/server-turizm/v1/instagram/webhook`

Use the same value as `STCA_META_VERIFY_TOKEN` when Meta asks for the verification token.

## 4. Pre-live test

In WordPress: **ST Assistant → Safe Simulator**.

Required scenarios:

1. `Ümre ziyareti ile ilgili bilgi alabilir miyim ekim kasım ayı gibi`
2. `Kasım ayında 2 kişilik fiyat nedir?`
3. `Oteller hangileri?`
4. `Temsilciye bağlanmak istiyorum`
5. `Vize için hangi belgeler gerekiyor?`
6. A random unsupported question

Check that every numeric fact comes only from canonical records and that fallback responses use the official Umrah URL and contact numbers.

## 5. Webhook verification

Complete Meta webhook verification while `STCA_INSTAGRAM_ENABLED` is still false. The GET handshake remains available; incoming POST events are accepted only with a valid Meta signature and are not replied to while the master is OFF.

## 6. Controlled live activation

After verification and one test DM from the Instagram account owner/customer test account, switch only:

```php
define('STCA_INSTAGRAM_ENABLED', true);
```

No SEO/publication setting needs to change.

## 7. Rollback

Immediate rollback is one line:

```php
define('STCA_INSTAGRAM_ENABLED', false);
```

This stops outbound Instagram replies without touching Program, Hotel, Tour, SEO or website publishing state.
