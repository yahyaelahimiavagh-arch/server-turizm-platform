Server Turizm Program Publishing Integration v0.4.13

- Safe 0.4.12 -> 0.4.13 replacement; all runtime gates/modes preserved.
- Accepts Program Intelligence v0.3.4.
- Adds exact post-identity-repair registry hash refresh.
- Refresh only touches the committed repair's previously public/noindex Stable IDs.
- Atomic preflight: one error means zero registry writes.
- Never opens STP-000038 or fixtures 36/37.
