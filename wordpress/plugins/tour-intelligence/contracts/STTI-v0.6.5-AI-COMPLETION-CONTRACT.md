# STTI v0.6.5 — AI Completion Contract

**Contract:** `STTI-AI-COMPLETION-1.0.0`  
**Input:** accepted `STTI-TOUR-IMPORT-1.0.0` PARTIAL document  
**Output:** source-bound FULL/REVIEW candidate  
**Runtime state:** validate + review dry run only; no AI provider call and no write

## Outcome

This gate turns a business-facing Sheet export into a structurally complete review candidate without treating AI output as canonical truth. The envelope embeds the original PARTIAL document, its canonical SHA-256, the proposed FULL document, an exact claim ledger, missing-information prompts and a mandatory pending human review.

`FULL` means every major schema section exists. It does not mean every business fact is known. `null`, `[]`, `unknown`, `unresolved` and `needs_review` are valid and preferred whenever a source does not support a fact.

## Allowed transformations

- copy a supplied fact;
- normalize spelling or structure without changing meaning;
- structure explicitly supplied prose;
- derive only mechanically certain values;
- classify supplied facts into existing fields;
- preserve unknowns and identify missing information;
- generate editorial copy only below `tour.content`, labeled `editorial_generated` and `generated=true`.

Deterministic derivation is restricted to:

- `tour.date.duration_days`;
- `tour.date.duration_nights`;
- itinerary `day_number` and `date` scaffolding derived from exact dates.

## Forbidden transformations

AI cannot invent or silently infer hotels, variants, itinerary facts, airline/flight/transfer facts, visa requirements, services, prices, price basis, occupancy or exact dates. It cannot change `policy`, `target` or `sources`; request publication/indexation/SEO state; set editorial state beyond `needs_review`; or write canonical, audit or sequence data.

## Binding and audit rules

1. `source_document` must be valid PARTIAL import JSON.
2. `source_document_sha256` is calculated from recursively key-sorted JSON encoded without escaped Unicode or slashes.
3. `candidate_document` must be valid FULL import JSON with `producer.type=ai`.
4. Source and candidate `policy`, `target` and `sources` must match exactly.
5. Every meaningful added or changed scalar requires an exact JSON Pointer claim.
6. Every claim references at least one source ID present in the original document.
7. `review.required=true` and `review.status=pending` are immutable at this gate.
8. A valid envelope is classified `REVIEW`; the nested import dry run still reports CREATE/UPDATE/UNCHANGED semantics without writing.

## Runtime flow

```text
Sheet → PARTIAL JSON → AI completion envelope
      → Completion validation
      → Existing import validation/normalization/dry run
      → REVIEW / INVALID
      → later human-review implementation
```

The v0.6.5 WordPress workbench accepts a pasted completion envelope. It does not call an external model. Provider integration and human approval/commit remain separate future gates.

## Acceptance checks

- the shipped example hash matches its embedded PARTIAL document;
- immutable `policy`, `target` and `sources` are preserved;
- unclaimed meaningful changes fail;
- stale/tampered source hashes fail;
- unsupported deterministic derivation paths fail;
- generated editorial outside `tour.content` fails;
- public/indexation controls remain rejected by the underlying import contract;
- the completion path contains no canonical, audit or sequence write.
