#!/usr/bin/env python3
from pathlib import Path

ROOT = Path(__file__).resolve().parents[4]
client = (ROOT / 'integrations/google-sheets/shared/ST-Direct-Sync.gs').read_text(encoding='utf-8')

checks = {
    'client version bumped': "VERSION: '0.1.3.2'" in client,
    'retry attempts bounded': 'TRANSPORT_MAX_ATTEMPTS: 3' in client,
    'same request id created outside retry loop': client.index("var requestId = 'STS-'") < client.index('for (var attempt = 1; attempt <= maxAttempts; attempt++)'),
    'same raw body created outside retry loop': client.index('var body = JSON.stringify(envelope);') < client.index('for (var attempt = 1; attempt <= maxAttempts; attempt++)'),
    'fresh signed fetch per attempt': 'stDirectSyncSignedFetch_(endpoint, keyId, secret, body, bodyHash)' in client,
    'transient classifier used': 'stDirectSyncIsTransientTransportError_(e)' in client,
    'dns error retried': r'dns\s*error' in client,
    'timeout retried': 'timeout|timed\\s*out' in client,
    'network error retried': r'network\s*error' in client,
    'connection failures retried': r'connection\s*(?:reset|refused|timed\s*out)' in client,
    'normal http errors still fail': "if (code < 200 || code >= 300) throw new Error('Direct Sync HTTP '" in client,
    'processing replay is polled': "parsed.code === 'stds_processing'" in client,
    'retry recovery surfaced': 'transport_retry_recovered' in client and 'Geçici bağlantı hatası otomatik retry ile kurtarıldı' in client,
}

failed = [name for name, ok in checks.items() if not ok]
for name, ok in checks.items():
    print(f"{name}: {'PASS' if ok else 'FAIL'}")
raise SystemExit(1 if failed else 0)
