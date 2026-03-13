# Current Metrics (Canonical)

This file is the single source of truth for current repository counts.

Last verified: 2026-03-13
Verification environment: local repo checkout at `/Users/danknauss/Documents/GitHub/authorship`

## Test Metrics

| Metric | Value | Verification |
|---|---:|---|
| PHPUnit tests | 207 tests | `composer test:ut` |
| PHPUnit assertions | 1,505 assertions | `composer test:ut` |
| Jest test suites | 4 suites | `npm run test:js -- --ci` |
| Jest tests | 24 tests | `npm run test:js -- --ci` |
| PHP coverage threshold | 63% | `tests/phpunit/includes/check-coverage-threshold.php` |
| JS coverage thresholds | 80% lines, 80% statements, 70% functions, 55% branches | `package.json test:js:coverage` |

## Size Metrics

| Metric | Value | Verification |
|---|---:|---|
| Production PHP lines (`inc/` + `plugin.php`) | 2,730 | `find ./inc -type f -name "*.php" -print0 \| xargs -0 wc -l \| tail -1` + `wc -l plugin.php` |
| Test PHP lines (`tests/phpunit/`) | 4,840 | `find ./tests/phpunit -type f -name "*.php" -print0 \| xargs -0 wc -l \| tail -1` |
| JS/TS source lines (`src/`) | 766 | `find ./src -type f \( -name "*.ts" -o -name "*.tsx" -o -name "*.js" -o -name "*.jsx" -o -name "*.scss" \) -print0 \| xargs -0 wc -l \| tail -1` |
| JS test lines (`tests/js/`) | 799 | `find ./tests/js -type f \( -name "*.ts" -o -name "*.tsx" -o -name "*.js" \) -print0 \| xargs -0 wc -l \| tail -1` |
| Test-to-production ratio (PHP) | 1.77:1 | `4840 / 2730` |

## Architectural Facts

Volatile counts that change when features ship. Every doc referencing these
numbers MUST point to or be verified against this table.

| Fact | Value | Verification | Last changed |
|---|---:|---|---|
| Supported post types | configurable | `apply_filters( 'authorship_supported_post_types', ... )` | v0.1.0 |
| Author taxonomy | `wp-authors` | `grep "TAXONOMY" inc/taxonomy.php` | v0.1.0 |
| REST controllers | 1 | `Users_Controller` in `inc/class-users-controller.php` | v0.1.0 |
| WP-CLI commands | 1 | `Migrate_Command` in `inc/cli/` | v0.2.0 |
| React components | 3 | `find src/components -name "*.tsx" \| wc -l` | Phase 03 |
| PHPStan level | max | `grep "level:" phpstan.neon.dist` | Phase 02 |
| Psalm baseline | committed | `psalm-baseline.xml` | Phase 02 |

### Files that reference these counts

- `README.md` — plugin description
- `.planning/README.md` — technical architecture summary
- `docs/manual-testing-checklist.md` — testing prompts
- `CLAUDE.md`, `AGENTS.md` — agent guidance
- `.planning/STATE.md` — workflow state

## CI Matrix Snapshot

Source: `.github/workflows/`

- PHP standards (stable): PHP 8.3, PHPCS + PHPStan — merge-blocking
- PHP standards (latest): PHP 8.4, nightly — advisory
- PHPUnit: WordPress 6.6 test suite, single-site + multisite
- Jest: Node 20, `@wordpress/scripts` test runner
- Build: `wp-scripts build` verification

## Verification Notes

- LOC counts verified on `develop` on 2026-03-13.
- `composer test:ut` passed on 2026-03-13 (211 tests, 1517 assertions).
- `npm run test:js -- --ci` passed on 2026-03-13 (4 suites, 24 tests).
- `composer analyse:phpstan` passed on 2026-03-09.

## Verification Script

Run after any structural edit:

```bash
cd /Users/danknauss/Documents/GitHub/authorship

echo "=== Production PHP ==="
find ./inc -type f -name "*.php" -print0 | xargs -0 wc -l | tail -1
wc -l plugin.php

echo "=== Test PHP ==="
find ./tests/phpunit -type f -name "*.php" -print0 | xargs -0 wc -l | tail -1

echo "=== JS/TS source ==="
find ./src -type f \( -name "*.ts" -o -name "*.tsx" -o -name "*.js" -o -name "*.jsx" -o -name "*.scss" \) -print0 | xargs -0 wc -l | tail -1

echo "=== JS tests ==="
find ./tests/js -type f \( -name "*.ts" -o -name "*.tsx" -o -name "*.js" \) -print0 | xargs -0 wc -l | tail -1

echo "=== Architectural ==="
echo "React components: $(find src/components -name '*.tsx' | wc -l)"
echo "PHPStan level: $(grep 'level:' phpstan.neon.dist)"
```

## Update Procedure

1. Re-run the verification script above.
2. Compare results to this table. Update any changed values.
3. Update all files listed in "Files that reference these counts."
4. Update `CHANGELOG.md` if counts changed significantly.
