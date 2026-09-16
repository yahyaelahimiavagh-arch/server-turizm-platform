# Tour Sheet → Production Direct Sync — Runtime Acceptance Evidence

**Date:** 2026-09-16

**Scope:** Controlled end-to-end Production connection proof for the separated Tour Google Apps Script stack, covering both CREATE/idempotency and one intentional UPDATE/idempotency cycle.

## Fixture

Original Sheet row:

`Iran Test Turu`

Intentional UPDATE title:

`Iran Test Turu Update Test`

Operator-local Tour ID:

`ID-642D23A3`

Canonical Tour Stable ID allocated by WordPress:

`STT-000002`

The local `ID-*` value remained a Sheet/business identifier and was not used as canonical STTI identity.

---

## A. CREATE / CONNECTION EVIDENCE

### A1. Local validation

Result:

```text
SATIR UYGUN
Target: NEW
```

Non-blocking source warnings were preserved rather than guessed:

- airline/source field was present while Segment DB provider fields were unresolved; no automatic provider inference was performed;
- price basis had no dedicated Sheet field and remained `UNKNOWN`.

### A2. Remote validate — no WordPress write

First transport attempt encountered a transient DNS failure.

Retry recovered automatically on the second attempt.

Accepted result:

```text
SYNC OK
STT-000002 — CREATE
```

The validate surface explicitly reported `WordPress yazma yok`.

### A3. Controlled CREATE Apply

Accepted result:

```text
SYNC OK
STT-000002 — CREATE
```

The transport path again recovered from a temporary connection problem on the second attempt.

Canonical Stable ID allocated/persisted:

`STT-000002`

The returned Stable ID + checksum were written into the hidden technical Sheet state by the accepted Tour client.

### A4. CREATE idempotency / second validation

One subsequent attempt exceeded the Apps Script maximum execution time during the unstable transport period. No second Apply was performed.

A later validate-only retry completed successfully:

```text
SYNC OK
STT-000002 — UNCHANGED
```

This proved the Sheet row targets the same canonical Tour identity and the no-change path does not propose a second CREATE.

---

## B. CONTROLLED UPDATE EVIDENCE

A single safe business-field change was made on the same accepted fixture:

```text
Tur Adı
Iran Test Turu
→ Iran Test Turu Update Test
```

The operator-local ID, dates and hidden Stable ID/checksum state were not manually changed.

### B1. Local validation after edit

Accepted result:

```text
SATIR UYGUN
Target: STT-000002
```

This proved identity continuity before any write.

### B2. Remote validate — no WordPress write

Accepted result:

```text
SYNC OK
STT-000002 — UPDATE
```

The request recovered from a temporary connection problem on the second attempt.

### B3. Controlled UPDATE Apply

Accepted result:

```text
SYNC OK
STT-000002 — UPDATE
```

The same immutable canonical Stable ID was preserved.

### B4. UPDATE idempotency / post-update validation

Accepted result:

```text
SYNC OK
STT-000002 — UNCHANGED
```

The validate request recovered from transient transport instability on the third attempt.

This proves the updated canonical state and hidden expected checksum were synchronized back to the Sheet correctly; repeating validation after the intentional update does not propose another UPDATE.

---

## Acceptance result

```text
Local row validation                         PASS
Production authentication / endpoint         PASS
Validate-only CREATE classification           PASS
Controlled CREATE Apply                       PASS
Canonical Stable ID                           STT-000002
Technical Sheet state persisted               PASS
Post-create validate                          UNCHANGED
Duplicate CREATE on accepted row              NOT OBSERVED
Intentional safe UPDATE classification        PASS
Controlled UPDATE Apply                       PASS
Stable ID preserved across UPDATE             PASS
Post-update validate                          UNCHANGED
Duplicate/looping UPDATE                      NOT OBSERVED
Transient DNS/network retry recovery          PASS
Transport stability                           DEGRADED / monitor
```

## Important transport note

Production connectivity from Google Apps Script showed intermittent DNS/latency behavior:

- transient DNS failures recovered by bounded retry logic on successful calls;
- one validate attempt exceeded Apps Script maximum execution time;
- successful calls recovered on the second or third attempt;
- canonical identity and idempotency remained correct after recovery.

Therefore the functional Direct Sync connection is accepted for the controlled CREATE + UPDATE paths, but transport health should continue to be monitored.

Do not interpret one execution-time expiry as a canonical-data failure and do not repeat Apply merely because a response is delayed.

## Publication / SEO boundary

This checkpoint proves canonical Tour ingestion/update only.

It does **not** authorize or prove:

- Tour Public Master activation;
- individual Tour public route activation;
- Tour indexation;
- sitemap inclusion;
- schema output;
- canonical exposure;
- homepage exposure;
- Tour Hub Master activation.

Those remain separate gates.

## Next safe checkpoint

The Tour Sheet → Production Direct Sync operator path is now accepted for:

```text
CREATE → APPLY → UNCHANGED
UPDATE → APPLY → UNCHANGED
```

No bulk Tour mutation is authorized by this acceptance evidence.

Next ordered checkpoint:

1. merge the documentation/evidence PR after final green CI;
2. verify/install Production Tour Intelligence v1.1 with Hub Master explicitly OFF;
3. prove existing `/kultur-turlari/` remains unchanged while Hub Master is OFF;
4. only then inspect controlled Hub eligibility before any live Hub activation.
