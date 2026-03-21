# Project State

## Current Position

Phase: 04 (Test Depth and Ratcheting) — COMPLETE
Build: 08 of 08 complete — all builds delivered
Status: Phase complete, ready for next phase or milestone
Last activity: 2026-03-21

## Project Reference

See: `README.md`, `.planning/README.md`

**Core value:** Reliable multi-author and guest-author attribution for WordPress — every post can have multiple attributed authors, including guest authors backed by real user accounts.
**Current focus:** Phase 04 complete. Next: plan Phase 05 or close milestone.

## Accumulated Context

### Completed Phases

- **Phase 01 (Audit & Standards):** Foundation quality baseline, HM WPCS audit, tooling patch scaffolds. Complete.
- **Phase 02 (Fork Delivery):** 13 builds covering fork governance, CI pipeline, WPCS alignment, PHPStan integration, Psalm baseline, security hardening, WP-CLI reliability. Complete.
- **Phase 03 (Frontend Modernization):** 12 builds — React/TypeScript component hardening, dnd-kit drag-and-drop, VoiceOver accessibility audit passed. Complete.
- **Phase 04 (Test Depth and Ratcheting):** 8 builds — multisite coverage, hook/filter contracts, deterministic batching, PPA hardening, query semantics, user deletion sync, coverage ratcheting (67% PHP / 73% JS functions), observability hook contract. Complete.

### Phase 04 Final Summary

- **Build-01:** Multisite + hook/filter coverage expansion (8 multisite + 15 hook/filter tests)
- **Build-02:** Coverage ratcheting (PHP 63%->67%, JS functions 70%->73%) + Psalm baseline reduction (339->328 lines)
- **Build-03–07:** Blocker lane — deterministic batching, PPA linked-user hardening, author-query post-type semantics, query callback lifecycle cleanup, user deletion sync
- **Build-08:** Attribution lifecycle hook contract — 6 new hooks (`set_authors_before/after/failed`, `deleted_user_sync_post_updated/post_failed/completed`) with 13 contract tests

### Key Decisions

- Fork-first workflow: `dknauss/authorship` `develop` is canonical. Upstream PRs minimized per `docs/fork-first-policy.md`.
- **Byline-feed is a companion plugin**, not part of the fork. Fork provides public API (`get_authors()`, `get_author_ids()`, role constants); companion consumes through adapter pattern.
- CI currently runs PHPCS/PHPStan on PHP 7.4 and 8.4, PHPUnit on PHP 7.4-8.3, and coverage on PHP 8.3.
- PHPStan level max with committed baseline.
- Psalm advisory baseline (not blocking).
- Coverage thresholds: PHP 67%, JS 80% lines/statements, 73% functions, 55% branches.
- `tests/wordpress/` contains a full WordPress install for integration testing — exclude from line counts.
- React frontend uses `@dnd-kit` for drag-and-drop and `react-select` for user selection.

### Known Issues

- Local verification on PHP 8.5 emits upstream WordPress/WP-CLI dependency deprecations; repo gates pass once a MySQL client and test database are available.

## Session Continuity

Last session: 2026-03-21
Stopped at: Repo cleanup complete — local PHP and JS verification refreshed, metrics/state docs reconciled
Current metrics: See `docs/current-metrics.md` (240 PHP tests, 24 JS tests, 2,849 prod PHP LOC, 70.09% coverage)
