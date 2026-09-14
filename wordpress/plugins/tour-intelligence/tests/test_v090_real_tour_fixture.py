#!/usr/bin/env python3
"""Static source-binding guard for STTI v0.9.0 First Real Full Tour."""

from __future__ import annotations

import csv
import hashlib
import io
import json
from datetime import date, timedelta
from pathlib import Path

PLUGIN = Path(__file__).resolve().parents[1]
ROOT = PLUGIN.parents[2]
SOURCE = ROOT / "examples/operator-sheet-snapshots/culture-tours-2026-09-14.csv"
FIXTURE = PLUGIN / "tests/fixtures/STT-000001-v090-real-tour.json"

source_bytes = SOURCE.read_bytes()
source_text = source_bytes.decode("utf-8")
fixture = json.loads(FIXTURE.read_text(encoding="utf-8"))
evidence = fixture["source_evidence"]
facts = fixture["business_facts"]
payload = fixture["canonical_payload"]

# Bind to the exact repository snapshot blob and exact populated CSV row.
git_blob_sha1 = hashlib.sha1(
    f"blob {len(source_bytes)}\0".encode("ascii") + source_bytes
).hexdigest()
assert git_blob_sha1 == evidence["git_blob_sha1"] == "161f1df200c5e9222b35a7b4471d03eb8e6f88ca"
raw_lines = source_text.splitlines()
raw_row = raw_lines[evidence["csv_row_number"] - 1]
assert hashlib.sha256(raw_row.encode("utf-8")).hexdigest() == evidence["raw_row_sha256"]

rows = list(csv.reader(io.StringIO(source_text)))
headers = rows[1]
record = dict(zip(headers, rows[evidence["csv_row_number"] - 1]))
assert record["STTI Stable ID"] == facts["stable_id"] == "STT-000001"
assert record["Program No"] == facts["program_no"] == "IRN-2026-01"
assert record["Tur Adı"] == facts["public_title"] == "Büyük İran Turu"
assert record["Ülke / Hedef"] == facts["target_country"] == "İran"
assert record["Gidiş"] == facts["departure_raw"] == "1/26/2027"
assert record["Dönüş"] == facts["return_raw"] == "2/12/2027"
assert record["Süre"] == "17 Gece 18 Gün"
assert record["Rota"] == facts["route_summary"]
assert [part.strip() for part in record["Şehirler"].split(",")] == facts["cities"]
assert int(record["2 Kişilik"]) == facts["double_room_price"] == 899
assert record["Para Birimi"] == facts["currency"] == "EUR"
assert record["Expected Checksum"] == evidence["operator_expected_checksum_before_pilot"]

# Current source dates supersede the historical 2026 example.
assert facts["start_date"] == payload["date"]["start_date"] == "2027-01-26"
assert facts["end_date"] == payload["date"]["end_date"] == "2027-02-12"
start = date.fromisoformat(payload["date"]["start_date"])
end = date.fromisoformat(payload["date"]["end_date"])
assert (end - start).days == facts["duration_nights"] == payload["date"]["duration_nights"] == 17
assert (end - start).days + 1 == facts["duration_days"] == payload["date"]["duration_days"] == 18
assert payload["date"]["start_date"] != "2026-10-16"
assert payload["date"]["end_date"] != "2026-10-24"

# Route is exact source order; no hotel or transport facts are synthesized.
stops = payload["route"]["stops"]
assert [stop["city"] for stop in stops] == facts["cities"]
assert [stop["stop_id"] for stop in stops] == ["R1", "R2", "R3", "R4", "R5"]
variant = payload["route"]["variants"][0]
assert variant["role"] == "primary" and variant["review_status"] == "confirmed"
assert variant["stop_refs"] == ["R1", "R2", "R3", "R4", "R5"]
assert variant["hotel_relation_refs"] == [] and variant["transport_segment_refs"] == []
assert payload["stays"]["hotels"] == []
assert payload["transport"]["segments"] == []

# FULL means structurally traversable, not permission to invent missing itinerary facts.
itinerary = payload["itinerary"]
assert len(itinerary) == 18
for index, day in enumerate(itinerary):
    assert day["day_number"] == index + 1
    assert day["date"] == (start + timedelta(days=index)).isoformat()
    for key in ("title", "city", "summary", "activities", "meals", "transport_ref", "hotel_ref", "media_refs"):
        assert day[key] is None

assert payload["pricing"]["amount"] == 899 and payload["pricing"]["currency"] == "EUR"
assert payload["pricing"]["basis"] == "unknown"
assert payload["requirements"]["visa_status"] == "unknown"
assert record["Vize"] == facts["visa_boolean_raw"] == "FALSE"
assert facts["visa_semantics_interpreted"] is False

# Geo is explicit, source-referenced, confirmed evidence; no browser resolver authority exists here.
geo = payload["geo"]["route_stops"]
assert len(geo) == 5
assert {item["stop_ref"] for item in geo} == {"R1", "R2", "R3", "R4", "R5"}
for item in geo:
    assert item["state"] == "resolved"
    assert item["source_type"] == "external_reference"
    assert item["source_ref"].startswith("https://www.geonames.org/")
    assert item["review_status"] == "confirmed"

assert fixture["production_approval_claimed"] is False
assert fixture["geo_evidence_policy"]["production_human_review_claimed"] is False
for lock in ("public_route", "hub_visible", "homepage_visible", "indexable", "sitemap"):
    assert payload["publication"][lock] is False

print("STTI v0.9.0 real tour source-binding fixture: PASS")
