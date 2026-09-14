# ADR-0001 — Canonical private repository

**Status:** Accepted  
**Date:** 2026-09-14

## Decision

`yahyaelahimiavagh-arch/server-turizm-platform` will be the authoritative
private source repository for Server Turizm custom platform code.

`main` contains accepted runtime baselines only. Every implementation is made
on a branch, reviewed through a pull request, verified against runtime gates,
and tagged after acceptance.

Live-server custom source is the initial runtime truth, but version labels do
not establish acceptance by themselves. Live source, the authoritative Master
Plan, accepted artifacts, and runtime evidence must be reconciled explicitly.
