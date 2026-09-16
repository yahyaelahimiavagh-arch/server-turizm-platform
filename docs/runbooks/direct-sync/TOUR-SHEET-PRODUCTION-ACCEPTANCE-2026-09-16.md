# Tour Sheet → Production Direct Sync — Runtime Acceptance Evidence

**Date:** 2026-09-16

**Scope:** First controlled end-to-end Production connection proof for the separated Tour Google Apps Script stack.

## Fixture

Sheet row:

`Iran Test Turu`

Operator-local Tour ID:

`ID-642D23A3`

Canonical Tour Stable ID allocated by WordPress:

`STT-000002`

The local `ID-*` value remained a Sheet/business identifier and was not used as canonical STTI identity.

## Evidence sequence

### 1. Local validation

Result:

```text
SATIR UYGUN
Target: NEW
```

Non-blocking source warnings were preserved rather than guessed:

- airline/source field was present while Segment DB provider fields were unresolved; no automatic provider inference was performed;
- price basis had no dedicated Sheet field and remained `UNKNOWN`.

### 2. Remote validate — no WordPress write

First transport attempt encountered a transient DNS failure.

Retry recovered automatically on the second attempt.

Accepted result:

```text
SYNC OK
STT-000002 — CREATE
```

The validate surface explicitly reported `WordPress yazma yok`.

### 3. Controlled Apply

Accepted result:

```text
SYNC OK
STT-000002 — CREATE
```

The transport path again recovered from a temporary connection problem on the second attempt.

Canonical Stable ID allocated/persisted:

`STT-000002`

The returned Stable ID + checksum were written into the hidden technical Sheet state by the accepted Tour client.

### 4. Idempotency / second validation

One subsequent attempt exceeded the Apps Script maximum execution time during the unstable transport period. No second Apply was performed.

A later validate-only retry completed successfully:

```text
SYNC OK
STT-000002 — UNCHANGED
```

This proves the Sheet row now targets the same canonical Tour identity and the no-change path does not propose a second CREATE.

## Acceptance result

```text
Local row validation                         PASS
Production authentication / endpoint         PASS
Validate-only CREATE classification           PASS
Controlled CREATE Apply                       PASS
Canonical Stable ID                           STT-000002
Technical Sheet state persisted               PASS (proven by later targeted UNCHANGED)
Post-create validate                          UNCHANGED
Duplicate CREATE on accepted row              NOT OBSERVED
Transient DNS retry recovery                  PASS
Transport stability                           DEGRADED / monitor
```

## Important transport note

Production connectivity from Google Apps Script showed intermittent DNS/latency behavior:

- transient DNS failures recovered by the bounded retry logic on successful calls;
- one later validate attempt exceeded Apps Script maximum execution time;
- a subsequent validate completed as `UNCHANGED`.

Therefore the functional Direct Sync connection is accepted, but transport health should continue to be monitored. Do not interpret one execution-time expiry as a canonical-data failure and do not repeat Apply merely because a response is delayed.

## Publication / SEO boundary

This checkpoint proves canonical Tour ingestion only.

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

The Sheet → Production Direct Sync connection proof is closed for the controlled CREATE/idempotency path.

Next work should use a separate controlled checkpoint for one intentional Tour UPDATE or for Production Tour Hub v1.1 installation with Hub Master still OFF. No bulk Tour mutation is authorized by this acceptance evidence.
