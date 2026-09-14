# Repository Structure

```text
server-turizm-platform/
├─ docs/                 authoritative plans, decisions, runbooks, evidence
├─ wordpress/            deployable custom WordPress source and server config
│  ├─ plugins/           one stable directory per custom plugin
│  ├─ mu-plugins/        live must-use plugins
│  ├─ theme-overrides/   Porto child theme only
│  └─ server-config/     reviewed runtime configuration snapshots
├─ integrations/         Google Sheets and future shared integration source
├─ schemas/              canonical contract copies for cross-module review
├─ examples/             non-canonical operator snapshots and fixtures
├─ tests/                repository-level current-baseline tests
├─ scripts/              local/CI verification helpers
└─ releases/historical/  retained non-deployable custom history
```

Version numbers are kept in source headers and release documentation, not in
canonical deployable directory names. Original live-server directory names are
recorded in the runtime inventory where they differ from plugin headers.

Hotel facts remain owned by Hotel Intelligence; Program and Tour relations
refer to stable Hotel IDs. The repository layout must not become a second
business-data source.
