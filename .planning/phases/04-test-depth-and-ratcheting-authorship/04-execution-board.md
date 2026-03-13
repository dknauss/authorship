# Phase 04 Execution Board (Canonical)

Last updated: 2026-03-13 (America/Edmonton)
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
| `04-Build-05` | Implicit author-query post-type semantics | executed | `codex/phase-04-build-05-author-query-post-type-clean` | Codex, Dan | 2026-03-12 | merged (#21) | Depends on Build-04 |
| `04-Build-06` | Query callback lifecycle cleanup | executed | `codex/phase-04-build-06-query-callback-lifecycle` | Codex, Dan | 2026-03-12 | merged (#20) | Depends on Build-05 |
| `04-Build-07` | User deletion sync verification/hardening | executed | `codex/phase-04-build-07-user-deletion-sync` | Codex, Dan | 2026-03-12 | merged (#22) | Closes blocker lane |
| `04-Build-01` | Multisite + hook/filter coverage expansion | partial | direct to `develop` | Codex, Dan | 2026-03-13 | landed (`1a288ad`) | 8 multisite workflow tests landed; hook/filter contract coverage remaining |
| `04-Build-02` | Coverage + Psalm ratcheting | queued | `codex/phase-04-build-02-coverage-psalm-ratchet` | Codex, Dan | TBD | not opened | Quality lane |
| `04-Build-08` | Observability hook contract implementation | queued | `codex/phase-04-build-08-observability-hooks` | Codex, Dan | TBD | not opened | Execute after quality lane |

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

Latest local baseline run (2026-03-13) on `develop` after byline-feed extraction:

| Command | Result | Date | Notes |
|---|---|---|---|
| `composer test:ut` | pass (207 tests, 1505 assertions) | 2026-03-13 | Post byline-feed extraction |
| `composer analyse:phpstan` | pass | 2026-03-13 | No errors |
| `npm run test:js -- --ci` | pass (4 suites, 24 tests) | 2026-03-13 | Includes arrayMove tests |

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
