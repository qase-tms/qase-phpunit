#!/usr/bin/env python3
"""Clean up expected YAML files by removing dynamic and empty fields."""

import sys
import yaml
from pathlib import Path


def clean_result(result: dict) -> dict:
    """Remove dynamic, empty, and default fields from a result."""
    out = {}

    if result.get("title"):
        out["title"] = result["title"]
    if result.get("signature"):
        out["signature"] = result["signature"]
    if result.get("testops_ids"):
        out["testops_ids"] = result["testops_ids"]

    status = result.get("status") or (result.get("execution", {}) or {}).get("status")
    if status:
        out["status"] = status

    fields = result.get("fields")
    if fields and fields != {}:
        out["fields"] = fields

    params = result.get("params")
    if params and params != {}:
        out["params"] = params

    relations = result.get("relations")
    if relations and relations.get("suite", {}).get("data"):
        out["relations"] = relations

    steps = result.get("steps")
    if steps and steps != []:
        out["steps"] = [clean_step(s) for s in steps]

    attachments = result.get("attachments")
    if attachments and attachments != []:
        out["attachments"] = [clean_attachment(a) for a in attachments]

    if result.get("muted"):
        out["muted"] = True

    return out


def clean_step(step: dict) -> dict:
    """Clean a step, removing IDs and timestamps."""
    out = {}

    data = step.get("data")
    if data:
        clean_data = {}
        if data.get("action"):
            clean_data["action"] = data["action"]
        if data.get("expected_result"):
            clean_data["expected_result"] = data["expected_result"]
        if clean_data:
            out["data"] = clean_data

    exec_block = step.get("execution", {}) or {}
    if exec_block.get("status"):
        out["execution"] = {"status": exec_block["status"]}

    child_steps = step.get("steps")
    if child_steps and child_steps != []:
        out["steps"] = [clean_step(s) for s in child_steps]

    return out


def clean_attachment(att: dict) -> dict:
    """Keep only file_name and mime_type."""
    out = {}
    if att.get("file_name"):
        out["file_name"] = att["file_name"]
    if att.get("mime_type"):
        out["mime_type"] = att["mime_type"]
    return out


def clean_expected(data: dict) -> dict:
    """Clean an entire expected file."""
    out = {}

    if data.get("run"):
        out["run"] = data["run"]

    if data.get("results"):
        out["results"] = [clean_result(r) for r in data["results"]]

    return out


def main():
    if len(sys.argv) < 2:
        print("Usage: clean_expected.py <file.yaml> [file2.yaml ...]")
        sys.exit(1)

    for path_str in sys.argv[1:]:
        path = Path(path_str)
        with open(path, "r") as f:
            data = yaml.safe_load(f)

        cleaned = clean_expected(data)

        with open(path, "w") as f:
            yaml.dump(cleaned, f, default_flow_style=False, allow_unicode=True, sort_keys=False)

        print(f"Cleaned: {path}")


if __name__ == "__main__":
    main()
