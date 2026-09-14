#!/usr/bin/env python3
"""Contract and safety invariants for STTI v0.6.5 AI Completion."""

from copy import deepcopy
from datetime import date
from hashlib import sha256
import json
from pathlib import Path


PLUGIN = Path(__file__).resolve().parents[1]
CONTRACTS = PLUGIN / "contracts"
EXAMPLE = CONTRACTS / "STTI-AI-COMPLETION-1.0.0.example.json"
PHP = PLUGIN / "server-turizm-tour-intelligence.php"


def canonical_hash(value):
    encoded = json.dumps(
        value, ensure_ascii=False, sort_keys=True, separators=(",", ":")
    ).encode("utf-8")
    return sha256(encoded).hexdigest()


def meaningful_changes(source, candidate, path=""):
    changes = []
    if isinstance(candidate, dict):
        for key, value in candidate.items():
            child = f"{path}/{key.replace('~', '~0').replace('/', '~1')}"
            if isinstance(value, (dict, list)):
                prior = source.get(key) if isinstance(source, dict) else None
                changes.extend(meaningful_changes(prior, value, child))
            elif value not in (None, "") and (
                not isinstance(source, dict) or source.get(key) != value
            ):
                changes.append(child)
    elif isinstance(candidate, list):
        for index, value in enumerate(candidate):
            child = f"{path}/{index}"
            prior = source[index] if isinstance(source, list) and index < len(source) else None
            if isinstance(value, (dict, list)):
                changes.extend(meaningful_changes(prior, value, child))
            elif value not in (None, "") and prior != value:
                changes.append(child)
    elif candidate not in (None, "") and source != candidate:
        changes.append(path or "/")
    return changes


TECHNICAL = {
    "/mode",
    "/producer/type",
    "/producer/name",
    "/producer/version",
    "/producer/generated_at",
}
DERIVABLE = {
    "/tour/date/duration_days",
    "/tour/date/duration_nights",
}
PUBLIC_KEYS = {
    "publication",
    "public_route",
    "hub_visible",
    "homepage_visible",
    "indexable",
    "sitemap",
    "schema_output",
    "canonical",
    "robots",
}


def walk_keys(value):
    if isinstance(value, dict):
        for key, item in value.items():
            yield key
            yield from walk_keys(item)
    elif isinstance(value, list):
        for item in value:
            yield from walk_keys(item)


def validate(envelope):
    errors = []
    source = envelope["source_document"]
    candidate = envelope["candidate_document"]
    if envelope["completion_contract"] != "STTI-AI-COMPLETION-1.0.0":
        errors.append("contract")
    if canonical_hash(source) != envelope["source_document_sha256"]:
        errors.append("source hash")
    if source["mode"] != "partial" or candidate["mode"] != "full":
        errors.append("modes")
    if candidate["producer"]["type"] != "ai":
        errors.append("producer")
    for key in ("policy", "target", "sources"):
        if source[key] != candidate[key]:
            errors.append(f"immutable {key}")
    for key in ("start_date", "end_date"):
        source_value = source.get("tour", {}).get("date", {}).get(key)
        if source_value is not None and candidate.get("tour", {}).get("date", {}).get(key) != source_value:
            errors.append(f"immutable date {key}")
    if candidate["tour"]["lifecycle"]["editorial"] != "needs_review":
        errors.append("review lifecycle")
    if envelope["review"] != {
        "required": True,
        "status": "pending",
        "reviewer": None,
        "reviewed_at": None,
        "notes": "FULL means structurally complete; it does not mean all facts are known.",
    }:
        errors.append("pending review")
    if PUBLIC_KEYS.intersection(walk_keys(candidate["tour"])):
        errors.append("publication")

    source_ids = {item["source_id"] for item in source["sources"]}
    claim_map = {claim["path"]: claim for claim in envelope["claims"]}
    changes = [
        path
        for path in meaningful_changes(source, candidate)
        if path not in TECHNICAL
    ]
    for path in changes:
        if path not in claim_map:
            errors.append(f"unclaimed {path}")
    for claim in envelope["claims"]:
        if not set(claim["source_ids"]).issubset(source_ids):
            errors.append(f"source id {claim['path']}")
        if claim["action"] == "deterministic_derivation" and not (
            claim["path"] in DERIVABLE
            or claim["path"].startswith("/tour/itinerary/")
            and claim["path"].rsplit("/", 1)[-1] in {"day_number", "date"}
        ):
            errors.append(f"derivation {claim['path']}")
        if claim["action"] == "editorial_generated" and not (
            claim["path"].startswith("/tour/content/") and claim["generated"] is True
        ):
            errors.append(f"editorial {claim['path']}")
    start_text = source.get("tour", {}).get("date", {}).get("start_date")
    end_text = source.get("tour", {}).get("date", {}).get("end_date")
    if start_text and end_text:
        expected_days = (date.fromisoformat(end_text) - date.fromisoformat(start_text)).days + 1
        expected = {
            "/tour/date/duration_days": expected_days,
            "/tour/date/duration_nights": expected_days - 1,
        }
        for path, value in expected.items():
            claim = claim_map.get(path)
            field = path.rsplit("/", 1)[-1]
            if claim and claim["action"] == "deterministic_derivation" and candidate["tour"]["date"].get(field) != value:
                errors.append(f"derived value {path}")
    return errors


example = json.loads(EXAMPLE.read_text(encoding="utf-8"))
schema = json.loads(
    (CONTRACTS / "STTI-AI-COMPLETION-1.0.0.schema.json").read_text(encoding="utf-8")
)
import_schema = json.loads(
    (CONTRACTS / "STTI-TOUR-IMPORT-1.0.0.schema.json").read_text(encoding="utf-8")
)
assert schema["$schema"] == "https://json-schema.org/draft/2020-12/schema"
assert schema["properties"]["source_document"]["allOf"][0]["$ref"] == "STTI-TOUR-IMPORT-1.0.0.schema.json"
assert import_schema["properties"]["import_contract"]["const"] == "STTI-TOUR-IMPORT-1.0.0"
assert set(schema["required"]) == set(example)
assert not validate(example), validate(example)

tampered = deepcopy(example)
tampered["source_document"]["tour"]["identity"]["public_title"] = "Tampered"
assert "source hash" in validate(tampered)

unclaimed = deepcopy(example)
unclaimed["candidate_document"]["tour"]["pricing"]["currency"] = "EUR"
assert "unclaimed /tour/pricing/currency" in validate(unclaimed)

retargeted = deepcopy(example)
retargeted["candidate_document"]["target"]["stable_id"] = "STT-000001"
assert "immutable target" in validate(retargeted)

invented_public = deepcopy(example)
invented_public["candidate_document"]["tour"]["content"]["indexable"] = True
assert "publication" in validate(invented_public)

bad_derivation = deepcopy(example)
bad_derivation["claims"][0]["path"] = "/tour/pricing/amount"
assert "derivation /tour/pricing/amount" in validate(bad_derivation)

wrong_duration = deepcopy(example)
wrong_duration["candidate_document"]["tour"]["date"]["duration_days"] = 99
assert "derived value /tour/date/duration_days" in validate(wrong_duration)

changed_date = deepcopy(example)
changed_date["candidate_document"]["tour"]["date"]["start_date"] = "2026-10-17"
assert "immutable date start_date" in validate(changed_date)

php = PHP.read_text(encoding="utf-8")
assert "Version: 0.6.5" in php and "define('STTI_VERSION', '0.6.5');" in php
assert "STTI-AI-COMPLETION-1.0.0" in php
assert "review_only_no_write" in php
assert "source_document_sha256" in php
assert "Meaningful candidate change için exact claim zorunlu" in php

completion_runtime = php.split("v0.6.5 AI Completion Contract — REVIEW ONLY.", 1)[1]
completion_runtime = completion_runtime.split("function stti_import_tone", 1)[0]
for prohibited_write in ("$wpdb->insert", "$wpdb->update", "$wpdb->delete", "stti_allocate_stable_id"):
    assert prohibited_write not in completion_runtime, prohibited_write

print("STTI v0.6.5 AI Completion contract invariants: PASS")
