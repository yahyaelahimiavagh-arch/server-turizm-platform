#!/usr/bin/env python3
"""Dependency-free structural check for the bundled JSON Schema examples."""

import json
import re
import sys
from datetime import date, datetime
from pathlib import Path
from urllib.parse import urlparse

ROOT = Path(__file__).resolve().parents[1]
SCHEMA = json.loads((ROOT / "schema/program-batch-v1.0.0.schema.json").read_text())


def resolve_ref(ref):
    node = SCHEMA
    for token in ref.removeprefix("#/").split("/"):
        node = node[token]
    return node


def is_type(value, kind):
    if kind == "null": return value is None
    if kind == "object": return isinstance(value, dict)
    if kind == "array": return isinstance(value, list)
    if kind == "string": return isinstance(value, str)
    if kind == "boolean": return isinstance(value, bool)
    if kind == "integer": return isinstance(value, int) and not isinstance(value, bool)
    if kind == "number": return isinstance(value, (int, float)) and not isinstance(value, bool)
    return True


def validate(value, schema, path="$", errors=None):
    errors = [] if errors is None else errors
    if "$ref" in schema:
        return validate(value, resolve_ref(schema["$ref"]), path, errors)
    if "const" in schema and value != schema["const"]:
        errors.append(f"{path}: expected constant {schema['const']!r}")
    if "enum" in schema and value not in schema["enum"]:
        errors.append(f"{path}: not in enum {schema['enum']}")
    kinds = schema.get("type")
    if kinds:
        kinds = [kinds] if isinstance(kinds, str) else kinds
        if not any(is_type(value, kind) for kind in kinds):
            errors.append(f"{path}: expected {kinds}, got {type(value).__name__}")
            return errors
    if isinstance(value, dict):
        for key in schema.get("required", []):
            if key not in value: errors.append(f"{path}.{key}: required")
        props = schema.get("properties", {})
        if schema.get("additionalProperties") is False:
            for key in value:
                if key not in props: errors.append(f"{path}.{key}: additional property")
        for key, child in value.items():
            if key in props: validate(child, props[key], f"{path}.{key}", errors)
    if isinstance(value, list):
        if len(value) < schema.get("minItems", 0): errors.append(f"{path}: too few items")
        if "items" in schema:
            for i, child in enumerate(value): validate(child, schema["items"], f"{path}[{i}]", errors)
    if isinstance(value, str):
        if len(value) < schema.get("minLength", 0): errors.append(f"{path}: too short")
        if "maxLength" in schema and len(value) > schema["maxLength"]: errors.append(f"{path}: too long")
        if "pattern" in schema and not re.fullmatch(schema["pattern"], value): errors.append(f"{path}: pattern mismatch")
        try:
            if schema.get("format") == "date": date.fromisoformat(value)
            elif schema.get("format") == "date-time": datetime.fromisoformat(value.replace("Z", "+00:00"))
            elif schema.get("format") == "uri" and not urlparse(value).scheme: raise ValueError
        except ValueError: errors.append(f"{path}: invalid {schema.get('format')}")
    if isinstance(value, (int, float)) and not isinstance(value, bool):
        if "minimum" in schema and value < schema["minimum"]: errors.append(f"{path}: below minimum")
        if "maximum" in schema and value > schema["maximum"]: errors.append(f"{path}: above maximum")
    return errors


def main():
    failed = False
    for path in sorted((ROOT / "examples").glob("*.json")):
        payload = json.loads(path.read_text())
        errors = validate(payload, SCHEMA)
        print(f"{path.name}: {'PASS' if not errors else 'FAIL'}")
        for error in errors: print(f"  - {error}")
        failed = failed or bool(errors)
    return 1 if failed else 0


if __name__ == "__main__":
    sys.exit(main())
