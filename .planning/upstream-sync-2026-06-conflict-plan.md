# Upstream Sync Conflict Plan — 2026-06-14

## Goal

Integrate the currently fetched `upstream/develop` history into the fork without losing fork-local work and without sending any new commits to the Human Made repository.

## Guardrails

- Canonical delivery remote remains `origin` (`dknauss/authorship`).
- `upstream` fetch is enabled for sync work; `upstream` push remains disabled.
- A local `.git/hooks/pre-push` guard blocks any attempt to push to Human Made from this clone.
- Upstream PR publication is intentionally off-limits from this working copy.
- This sync branch is fork-local only.
- Prefer merge-based integration over rebasing the fork's divergence.
- Preserve fork-local planning/docs under `.planning/` and `docs/audit/`.

## Current divergence snapshot

As verified on 2026-06-14 from the already-fetched refs:

- `develop` = `origin/develop`
- `upstream/develop` is 70 commits ahead of the fork base
- fork `develop` is 104 commits ahead of `upstream/develop`

## Dry-run result

A local dry-run merge of `upstream/develop` into fork `develop` produced conflicts in these areas:

### 1. CI and toolchain
- `.github/workflows/build.yml`
- `.github/workflows/js-tests.yml`
- `.github/workflows/php-standards.yml`
- `.github/workflows/test-nightly.yml`
- `.github/workflows/test.yml`
- `.eslintrc.json`
- `.gitignore`
- `.nvmrc`
- `composer.json`
- `composer.lock`
- `package.json`
- `package-lock.json`
- `phpstan.neon.dist`

### 2. Runtime/plugin PHP
- `plugin.php`
- `inc/class-users-controller.php`
- `inc/cli/class-migrate-command.php`
- `inc/namespace.php`
- `inc/template.php`

### 3. Editor/frontend
- `src/components/AuthorsSelect.tsx`
- `src/components/SortableMultiValueElement.tsx`
- `src/components/SortableSelectContainer.tsx`
- `src/index.scss`

### 4. Tests and docs
- `README.md`
- `tests/phpunit/test-multisite.php`
- `tests/phpunit/test-post-saving.php`
- `tests/phpunit/test-rest-api-post-property.php`
- `tests/phpunit/test-rest-api-user-endpoint.php`
- `tests/phpunit/test-wp-query.php`

## Recommended integration order

### Pass 1 — toolchain and workflow baseline
Objective: establish one coherent PHP/Node/CI/tooling stack before resolving code-level behavior.

Resolve first:
- `composer.json`
- `composer.lock`
- `package.json`
- `package-lock.json`
- `.nvmrc`
- `phpstan.neon.dist`
- workflow files under `.github/workflows/`
- `.eslintrc.json`
- `.gitignore`

Decision bias:
- keep fork-local quality gates and Node 20 baseline unless upstream introduces a required compatibility fix
- preserve fork-local test/coverage/static-analysis commands where they are stricter
- accept upstream workflow changes only when they do not weaken the fork's verification contract

Verification after pass 1:
- `composer validate`
- `npm install --package-lock-only` only if lockfile reconciliation requires it
- inspect workflow YAML for duplicate or regressive jobs

### Pass 2 — runtime PHP reconciliation
Objective: merge upstream behavior changes into the fork's canonical plugin architecture.

Resolve next:
- `plugin.php`
- `inc/class-users-controller.php`
- `inc/cli/class-migrate-command.php`
- `inc/namespace.php`
- `inc/template.php`

Decision bias:
- preserve fork-local contracts already documented in README and `.planning/`
- accept upstream fixes that are compatible with taxonomy-first authorship semantics
- do not silently discard fork-local REST, CLI, attribution, or observability hardening

Key checks:
- bootstrap/loading order still works
- taxonomy remains source of truth
- no regression to guest-author handling or CLI migration behavior
- no accidental removal of fork-local hooks, tests, or filters

### Pass 3 — editor/frontend reconciliation
Objective: preserve fork-local modernization while absorbing upstream asset/editor changes that still matter.

Resolve next:
- `src/components/AuthorsSelect.tsx`
- `src/components/SortableMultiValueElement.tsx`
- `src/components/SortableSelectContainer.tsx`
- `src/index.scss`

Decision bias:
- preserve the fork's React/TypeScript modernization, drag/drop, and accessibility work
- accept upstream fixes that are clearly additive and compatible
- avoid reintroducing deprecated patterns or older package assumptions

Verification after pass 3:
- `npm run lint`
- `npm run test:js -- --ci`
- `npm run build`

### Pass 4 — tests and docs reconciliation
Objective: bring test suites and repo docs into line with the merged codebase.

Resolve last:
- `tests/phpunit/test-multisite.php`
- `tests/phpunit/test-post-saving.php`
- `tests/phpunit/test-rest-api-post-property.php`
- `tests/phpunit/test-rest-api-user-endpoint.php`
- `tests/phpunit/test-wp-query.php`
- `README.md`

Decision bias:
- keep the stricter fork-local regression coverage when both sides touch the same behavior
- update README to reflect the merged reality, not either side's stale wording

Verification after pass 4:
- `composer test:ut`
- `WP_MULTISITE=1 composer test:ut`
- `composer analyse:phpstan`
- `composer analyse:psalm`
- `composer lint`
- `npm run test:js -- --ci`
- `npm run build`

## Merge execution notes

Suggested execution flow from this branch:

```bash
git merge upstream/develop
# resolve pass 1
# resolve pass 2
# resolve pass 3
# resolve pass 4
```

If the merge becomes too tangled, split the work into temporary helper branches off this branch, for example:
- `codex/upstream-sync-2026-06-tooling`
- `codex/upstream-sync-2026-06-php`
- `codex/upstream-sync-2026-06-editor`
- `codex/upstream-sync-2026-06-tests-docs`

## Exit criteria

The sync branch is ready to merge back into fork `develop` only when:

- the merge commit is complete
- the repo is conflict-free
- PHP and JS verification gates pass
- README and planning docs still reflect fork-first policy
- no new commits are pushed or proposed to `upstream`
