# Phase 01–02 Execution Roadmap for Authorship

See also: [Global Roadmap](roadmap-global.md) for project-wide purpose, history, backlog, and next-step narrative.

## Current state
- `00-01` foundation baseline defines support matrix and gate assumptions in `docs/audit/foundation-quality-baseline.md`.
- `01-01` exists as the initial planning stub at `.planning/phases/01-audit-roadmap-authorship/01-01-PLAN.md`.
- `01-02` has a repo-grounded audit deliverable in `docs/audit/HM_WPCS_audit.md`.
- `01-Build-01` through `01-Build-04` executed on `codex/restack-audit-queue`.
- `02-Build-01` through `02-Build-12` executed with deterministic CLI migration pause coverage, multisite stabilization, pacing hook contract hardening, registration-aware post-type input hardening, a baseline coverage gate, CI parity, threshold-ratcheting policy, and PHPStan baseline elimination.
- `02-Build-13` executed on 2026-03-07 with upstream submissions:
  - Umbrella issue: `https://github.com/humanmade/authorship/issues/166`
  - PR A: `https://github.com/humanmade/authorship/pull/162`
  - PR B: `https://github.com/humanmade/authorship/pull/163`
  - PR C: `https://github.com/humanmade/authorship/pull/164`
  - PR D: `https://github.com/humanmade/authorship/pull/165`
- Phase 03 started on 2026-03-07:
  - `03-Build-01` executed on `codex/phase-03-build-01-toolchain` (tooling migration + initial JS tests)
  - `03-Build-02` executed on `codex/phase-03-build-02-dnd-migration` (DND migration from `react-sortable-hoc` to `@dnd-kit` with test coverage)
  - `03-Build-03` executed on `codex/phase-03-build-03-react-select` (`react-select` v5 migration + selection-change contract tests)
  - `03-Build-04` executed on `codex/phase-03-build-04-hooks-lodash` (`withSelect`/`withDispatch` migration to hooks + lodash removal in `AuthorsSelect`)
  - `03-Build-05` executed on `codex/phase-03-build-05-editor-import-guest-tests` (`PluginPostStatusInfo` import migration to `@wordpress/editor` + guest-author create/error JS tests)
  - `03-Build-06` executed on `codex/phase-03-build-06-manual-testing-checklist` (manual UI/REST/WP-CLI/XML-RPC verification checklist in `docs/manual-testing-checklist.md`)
  - `03-Build-07` executed on `codex/phase-03-build-07-accessibility-audit` (WCAG 2.1 AA author-selector audit and remediation backlog in `docs/audit/accessibility-author-selector.md`)
  - `03-Build-08` executed on `codex/phase-03-build-08-accessibility-remediation` (keyboard sensor support, selector ARIA metadata, and live announcements with JS test coverage)
  - `03-Build-09` executed on `codex/phase-03-build-09-runtime-a11y-validation` (runtime validation + `onDragOver` regression fix + README accessibility statement alignment)
  - `03-Build-10` executed on `codex/phase-03-build-10-at-matrix-hardening` (non-admin edit-context request-path hardening + regression test coverage + NVDA/VoiceOver matrix protocol checklist)
  - `03-Build-11` executed on `codex/phase-03-build-11-coverage-psalm-advisory` (admin PHPUnit coverage expansion + JS coverage threshold gate + Psalm advisory tooling)
  - `03-Build-12` completed on `codex/phase-03-build-12-at-matrix-evidence` (automation evidence captured; manual VoiceOver pass recorded for add/remove/reorder; optional NVDA transcript capture moved to backlog)
- Phase 04 started on 2026-03-08 via groundwork commit `380ba2c`, then had its queue formalized in plans:
  - `04-01` documents the actual started state and the explicit-build execution boundary at `.planning/phases/04-test-depth-and-ratcheting-authorship/04-01-PLAN.md`
  - `04-Build-01` and `04-Build-02` plans created and queued for multisite/test-depth and ratchet work
  - `04-Build-03` executed on `codex/phase-04-build-03-wp-authors-batching`:
    - fixed write-mode `wp-authors` skip bug with deterministic snapshot-ID batching
    - added multi-batch regression coverage for full pending migration and mixed existing-authorship fixtures
    - re-verified `composer test:integration`, `WP_MULTISITE=1 composer test:integration`, `composer analyse:phpstan`, `composer analyse:psalm`, `composer lint`
  - `04-Build-04` executed on `codex/phase-04-build-04-ppa-linked-user-hardening`:
    - added stale linked-user regression for nonexistent PublishPress mapped user IDs
    - validated mapped linked-user IDs before reuse and preserved login/slug fallback behavior
    - re-verified `composer test:integration`, `WP_MULTISITE=1 composer test:integration`, `composer analyse:phpstan`, `composer analyse:psalm`, `composer lint`
  - `04-Build-05` executed on `codex/phase-04-build-05-author-query-post-type-clean`:
    - added omitted-`post_type` regression coverage for `author` and `author_name` queries including supported `post` and `page` content
    - added archive regression coverage confirming supported `page` content appears in author archives
    - re-verified `composer test:integration`, `WP_MULTISITE=1 composer test:integration`, `composer analyse:phpstan`, `composer analyse:psalm`, `composer lint`
  - `04-Build-06` executed on `codex/phase-04-build-06-query-callback-lifecycle`:
    - added callback-lifecycle regression coverage for repeated author-filtered queries
    - replaced accumulating `posts_pre_query` callback behavior with a self-removing one-shot callback
    - re-verified `composer test:integration`, `WP_MULTISITE=1 composer test:integration`, `composer analyse:phpstan`, `composer analyse:psalm`, `composer lint`
  - `04-Build-07` executed on `codex/phase-04-build-07-user-deletion-sync`:
    - added sole-author, guest-author, invalid/self reassignment, and multisite deletion coverage
    - verified groundwork deletion-sync implementation without production-code changes
    - re-verified `composer test:integration`, `WP_MULTISITE=1 composer test:integration`, `composer analyse:phpstan`, `composer analyse:psalm`, `composer lint`
  - `04-Build-08` plan created and queued for attribution lifecycle observability hooks using `docs/audit/authorship-observability-hook-contract.md`
  - Canonical execution board is `.planning/phases/04-test-depth-and-ratcheting-authorship/04-execution-board.md`
  - Cross-site attribution contract for Phase 04 is REST-only and default site-local; explicit network mode remains filter-controlled (`authorship_cross_site_mode`)
  - Widening cross-site mode beyond REST is tracked as backlog follow-up, not in current Phase 04 execution scope
  - Security and audit model is complementary-plugin-first: WP Sudo handles reauthentication/gating and Stream/WSAL-style plugins handle persistent audit trails; Authorship focuses on emitting stable hooks
  - Next explicit execution slice is `04-Build-01`
  - Phase 04 execution priority is `04-Build-01` and `04-Build-02`, then `04-Build-08`
  - Further Phase 04 work should continue only through explicit build-scoped branches/PRs
- Strict fork-first upstream policy centralized at `docs/fork-first-policy.md`.
- Upstream PR hygiene completed on 2026-03-08: superseded HM PRs `#160`, `#161`, and `#167`-`#172` closed; maintained open packaging set is `#162`-`#165`.

## What Phase 01 established
- Root standards configuration already exists and is not missing.
- Build queue sequencing (tooling -> hardening -> observability -> performance) is implemented in code.
- Current fork-local test/lint gates are green on the integration branch.

## Phase 01 Build queue status
- `01-Build-01`: completed
- `01-Build-02`: completed
- `01-Build-03`: completed
- `01-Build-04`: completed

## Phase 02 Build queue status
- `02-Build-01`: completed
- `02-Build-02`: completed
- `02-Build-03`: completed
- `02-Build-04`: completed
- `02-Build-05`: completed
- `02-Build-06`: completed
- `02-Build-07`: completed
- `02-Build-08`: completed
- `02-Build-09`: completed
- `02-Build-10`: completed
- `02-Build-11`: completed — coverage threshold ratcheting policy documented, threshold raised to 63%
- `02-Build-12`: completed — baseline reduced to zero ignored errors with annotation/type-guard-only fixes
- `02-Build-13`: completed — upstream PR preparation and submission (4 PRs + umbrella issue)

## Phase 02 completion criteria

Phase 02 is done when these fork-local outcomes are all true:
1. `02-Build-11` executed: threshold ratcheted, policy documented.
2. `02-Build-12` executed: baseline reduced to justified entries only.
3. `02-Build-13` executed: 4 upstream PRs submitted.
4. All quality gates pass.
5. Global roadmap updated, Phase 02 marked complete.

Phase closes on PR *submission*, not on upstream response. Existing #160/#161 may be closed as housekeeping if no active review signal, but this is not a gate.

Residual risk notes:
- PR C (CLI migration improvements) remains the broadest Build-13 PR. Split into C1/C2 if upstream review or CI signal indicates excessive scope.
- Build-12 no-behavior-change type hardening remains fork-local and verified on `codex/restack-audit-queue`.
- Coverage ratchet is intentionally conservative at 63% pending Phase 04 incremental raises.

## Next step
- Continue Phase 04 only through explicit build-scoped branches/PRs; `04-Build-01` is the next slice.
- Blocker-remediation lane is closed through `04-Build-07`; proceed with `04-Build-01`, then `04-Build-02`.
- Keep `04-Build-08` queued as the next interoperability slice after blocker and quality lanes complete.
- Keep explicit network mode scoped to REST in Phase 04; treat non-REST widening as backlog unless reprioritized.
- Keep audit/reauth strategy integration-first: prioritize hook contract coverage for Stream/WSAL compatibility and avoid planning a first-party audit datastore in Authorship.
- Keep NVDA transcript capture in backlog as optional `UI-06` evidence work (non-blocking).
