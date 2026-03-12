# Phase 04 Execution Board (Canonical)

Last updated: 2026-03-12 (America/Edmonton)
Owner: fork `develop` execution (Dan + Codex)
Source-of-truth status board for Phase 04 sequencing, readiness, and gate tracking.

## 1) Branch and commit hygiene (required before continuing execution)

Current branch: `develop`

Execution hygiene state:

1. Blocker-lane work is now split into dedicated build branches and PRs.
2. No mixed-scope Phase 04 groundwork remains pending in the working tree.
3. Continue using one branch and one PR per numbered build slice.

## 2) Phase 04 build queue

| Build | Scope | Status | Planned branch | Owner | Target date | PR status | Notes |
|---|---|---|---|---|---|---|---|
| `04-Build-03` | Deterministic `wp-authors` batching | executed | `codex/phase-04-build-03-wp-authors-batching` | Codex (exec), Dan (review) | 2026-03-09 | merged | Blocker lane start |
| `04-Build-04` | Stale PPA linked-user hardening | executed | `codex/phase-04-build-04-ppa-linked-user-hardening` | Codex, Dan | 2026-03-10 | merged | Depends on Build-03 |
| `04-Build-05` | Implicit author-query post-type semantics | executed | `codex/phase-04-build-05-author-query-post-type-clean` | Codex, Dan | 2026-03-12 | open | Depends on Build-04 |
| `04-Build-06` | Query callback lifecycle cleanup | executed | `codex/phase-04-build-06-query-callback-lifecycle` | Codex, Dan | 2026-03-12 | open | Depends on Build-05 |
| `04-Build-07` | User deletion sync verification/hardening | executed | `codex/phase-04-build-07-user-deletion-sync` | Codex, Dan | 2026-03-12 | pending | Closes blocker lane |
| `04-Build-01` | Multisite + hook/filter coverage expansion | queued (next) | `codex/phase-04-build-01-multisite-hook-coverage` | Codex, Dan | 2026-03-13 | not opened | Quality lane; blocker lane closed |
| `04-Build-02` | Coverage + Psalm ratcheting | queued | `codex/phase-04-build-02-coverage-psalm-ratchet` | Codex, Dan | 2026-03-15 | not opened | Quality lane |
| `04-Build-08` | Observability hook contract implementation | queued | `codex/phase-04-build-08-observability-hooks` | Codex, Dan | 2026-03-16 | not opened | Execute after blocker + quality lanes |

## 3) Phase completion criteria (explicit)

Phase 04 is complete only when all are true:

1. Builds `04-Build-03` through `04-Build-08` are completed and recorded.
2. Required gate suite is green for each execution slice:
   - `composer test:integration`
   - `WP_MULTISITE=1 composer test:integration`
   - `composer analyse:phpstan`
   - `composer analyse:psalm`
   - `composer lint`
3. Roadmap status is updated in:
   - `docs/audit/roadmap-global.md`
   - `docs/audit/roadmap-01.md`
4. No open contradiction remains between plan docs, roadmap docs, and implemented behavior contracts.
5. Upstream packaging decision (offer vs defer) is recorded but is non-gating for fork-local completion.

## 4) Gate baseline snapshot

Latest local baseline run (2026-03-12) on `codex/phase-04-build-07-user-deletion-sync`:

| Command | Result | Date | Notes |
|---|---|---|---|
| `composer test:integration` | pass | 2026-03-12 | single-site run green after Build-07 coverage expansion |
| `WP_MULTISITE=1 composer test:integration` | pass | 2026-03-12 | multisite run green after Build-07 coverage expansion |
| `composer analyse:phpstan` | pass | 2026-03-12 | No errors |
| `composer analyse:psalm` | pass | 2026-03-12 | No errors (info baseline unchanged) |
| `composer lint` | pass | 2026-03-12 | PHPCS deprecation warnings only |

## 5) Blocker escalation rule

Raise execution escalation when either condition is met:

1. A build is blocked for more than 1 business day with no passing gate path.
2. The same gate fails twice after independent remediation attempts.

Escalation action:
- Record blocker in roadmap notes with failing command and error signature.
- Freeze downstream build starts until blocker disposition is decided.
- If scope expansion is required, add a new numbered build slice rather than widening the active one.

## 6) Build-08 acceptance mapping pointer

Normative hook/event contract and planned test mapping live in:
- `docs/audit/authorship-observability-hook-contract.md`
- `.planning/phases/04-test-depth-and-ratcheting-authorship/04-Build-08-PLAN.md`
