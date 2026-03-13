# Project State

## Current Position

Phase: 04 (Test Depth and Ratcheting)
Build: 07 of 08 complete — Build-01 partially addressed, Build-02 and Build-08 queued
Status: In progress
Last activity: 2026-03-13

## Project Reference

See: `README.md`, `.planning/README.md`

**Core value:** Reliable multi-author and guest-author attribution for WordPress — every post can have multiple attributed authors, including guest authors backed by real user accounts.
**Current focus:** Phase 04 — close test coverage gaps, ratchet quality thresholds, harden edge cases discovered in prior phases.

## Accumulated Context

### Completed Phases

- **Phase 01 (Audit & Standards):** Foundation quality baseline, HM WPCS audit, tooling patch scaffolds. Complete.
- **Phase 02 (Fork Delivery):** 13 builds covering fork governance, CI pipeline, WPCS alignment, PHPStan integration, Psalm baseline, security hardening, WP-CLI reliability. Complete.
- **Phase 03 (Frontend Modernization):** 12 builds — React/TypeScript component hardening, dnd-kit drag-and-drop, VoiceOver accessibility audit passed. Complete.
- **Phase 04 Builds 03–07:** Blocker lane closed — deterministic batching, PPA linked-user hardening, author-query post-type semantics, query callback lifecycle cleanup, user deletion sync. Complete.

### Phase 04 Remaining Builds

- **Build-01:** Multisite + hook/filter coverage expansion — partially addressed (8 multisite workflow tests landed 2026-03-13, hook/filter contract coverage still needed)
- **Build-02:** Coverage + Psalm ratcheting (queued)
- **Build-08:** Observability hook contract implementation (queued)

### Recent Work (2026-03-13)

- **Editor UX:** Compact author chip/pill design replaced oversized avatar cards in block editor sidebar (`82b6506`).
- **Atom feed fix:** `filter_the_author_for_rss` now handles all feed formats, not just RSS2 (`beb12d3`, then preserved through byline-feed extraction).
- **Byline-feed separation:** `inc/byline-feed.php` implemented then extracted to standalone `byline-feed` branch (`35ca484`). Fork focuses on core multi-author data; structured feed output moves to companion plugin.
- **Test coverage expansion:** 34 new tests — arrayMove.ts (8 Jest), InsertPostHandler (9 PHPUnit), multisite workflows (8 PHPUnit) (`1a288ad`).
- **Branch cleanup:** 25 stale local branches deleted; 3 active upstream PR branches retained.
- **Upstream PR review:** PRs #175 (useEffect fix), #176 (query callback), #177 (guest author collision) reviewed — all solid.

### Key Decisions

- Fork-first workflow: `dknauss/authorship` `develop` is canonical. Upstream PRs minimized per `docs/fork-first-policy.md`.
- **Byline-feed is a companion plugin**, not part of the fork. Fork provides public API (`get_authors()`, `get_author_ids()`, role constants); companion consumes through adapter pattern.
- PHP 8.3 is the stable CI gate; PHP 8.4 is advisory/nightly.
- PHPStan level max with committed baseline.
- Psalm advisory baseline (not blocking).
- Coverage thresholds: PHP 63%, JS 80% lines/statements, 70% functions, 55% branches.
- `tests/wordpress/` contains a full WordPress install for integration testing — exclude from line counts.
- React frontend uses `@dnd-kit` for drag-and-drop and `react-select` for user selection.

### Blockers/Concerns

None.

## Session Continuity

Last session: 2026-03-13
Stopped at: Byline-feed extracted; doc updates in progress; Build-01 partially addressed (multisite tests landed, hook coverage remaining)
Current metrics: See `docs/current-metrics.md` (207 PHP tests, 24 JS tests, 2,730 prod PHP LOC)
