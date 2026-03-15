# Authorship Observability Hook Contract

Last updated: 2026-03-15 (America/Edmonton)
Status: implemented (Build-08)
Scope: hook interoperability for external audit plugins

## Purpose

Define stable attribution lifecycle hooks so complementary plugins (for example Stream/WSAL) can log authorship changes without Authorship owning a first-party audit datastore.

This follows the fork policy:
- Authorship owns attribution semantics and extension hooks.
- WP Sudo owns reauthentication and request-surface gating.
- Activity log plugins own persistent audit storage/reporting.

## Non-goals

- No custom audit table in Authorship.
- No built-in Authorship audit dashboard UI.
- No replacement of WP Sudo or Stream/WSAL responsibilities.

## Existing Hook Surface (Current)

| Hook | Type | Arguments | Notes |
|---|---|---|---|
| `authorship_author_assignment_failure` | action | `(int $post_id, WP_Post $post, bool $update, int[] $author_ids, Exception $e)` | Insert/update failure signal in `InsertPostHandler`. |
| `authorship_default_author` | filter | `(array $authors, WP_Post $post)` | Default-author override before insert/update assignment. |
| `authorship_supported_post_types` | filter | `(string[] $post_types)` | Post-type support contract. |
| `authorship_migrate_batch_pause_resolved` | action | `(float $pause_seconds, string $migration, array $assoc_args, int $count)` | CLI pacing observability only. |
| `authorship_cross_site_mode` | filter | `(string $mode)` | REST user lookup scope mode (`site`/`network`). |

Gap: there is no stable success/failure hook contract for all attribution set/replace/remove write paths.

## Proposed Contract v1

### 1) `set_authors()` lifecycle hooks

### `authorship_set_authors_before`
- Type: action
- Fired: immediately before attempting to persist attribution changes
- Arguments:
  1. `WP_Post $post`
  2. `int[] $previous_author_ids`
  3. `int[] $requested_author_ids`
  4. `array $context`

### `authorship_set_authors_after`
- Type: action
- Fired: after successful term write
- Arguments:
  1. `WP_Post $post`
  2. `int[] $previous_author_ids`
  3. `int[] $new_author_ids`
  4. `array $context`

### `authorship_set_authors_failed`
- Type: action
- Fired: when attribution persistence fails and exception path is taken
- Arguments:
  1. `WP_Post $post`
  2. `int[] $previous_author_ids`
  3. `int[] $requested_author_ids`
  4. `Exception $exception`
  5. `array $context`

Backward compatibility:
- Keep existing `authorship_author_assignment_failure` unchanged.
- Emit both legacy and new failure actions when applicable.

### 2) Deleted-user sync lifecycle hooks

### `authorship_deleted_user_sync_post_updated`
- Type: action
- Fired: once per updated post during deleted-user sync
- Arguments:
  1. `int $post_id`
  2. `int[] $previous_author_ids`
  3. `int[] $updated_author_ids`
  4. `int $deleted_user_id`
  5. `int $replacement_user_id`
  6. `array $context`

### `authorship_deleted_user_sync_post_failed`
- Type: action
- Fired: when a post update fails during deleted-user sync
- Arguments:
  1. `int $post_id`
  2. `int[] $previous_author_ids`
  3. `int[] $attempted_author_ids`
  4. `int $deleted_user_id`
  5. `int $replacement_user_id`
  6. `WP_Error $error`
  7. `array $context`

### `authorship_deleted_user_sync_completed`
- Type: action
- Fired: once per sync run after traversal completes
- Arguments:
  1. `int $deleted_user_id`
  2. `int $replacement_user_id`
  3. `int $posts_scanned`
  4. `int $posts_updated`
  5. `int $posts_failed`
  6. `array $context`

## Context Schema (`$context`)

All proposed actions include a normalized context array.

Required keys:
- `source` (`string`): one of `rest`, `insert_post`, `cli`, `deleted_user_sync`, `unknown`.
- `operation` (`string`): one of `assign`, `replace`, `remove`.
- `actor_user_id` (`int`): current user ID, or `0` when unavailable (non-interactive paths).

Optional keys:
- `trigger` (`string`): hook/entrypoint identifier (for example `rest_field_update`, `wp_insert_post`, `wp_authors_cli`, `ppa_cli`).
- `request_route` (`string`): REST route when `source=rest`.
- `cli_command` (`string`): migration command name when `source=cli`.
- `dry_run` (`bool`): CLI dry-run flag when relevant.

## Firing Guarantees

- Hooks are deterministic and fire at most once per mutation attempt.
- `*_after` only fires on successful persistence.
- `*_failed` only fires on failure paths.
- Dry-run operations must not emit write-success hooks.
- Hook payloads must avoid secret/sensitive values (no raw credentials, cookies, or request bodies).

## Testing Contract (Required)

Minimum coverage to accept implementation:
- REST write path: success and failure hook emission/payload.
- Insert/update path (`wp_insert_post`): success and failure emission/payload.
- CLI write path: success emission; dry-run emits no write-success hook.
- Deleted-user sync path: per-post updated/failed and completion summary hooks.
- Legacy `authorship_author_assignment_failure` remains emitted on matching failure paths.

Gate suite:
- `composer test:integration`
- `WP_MULTISITE=1 composer test:integration`
- `composer analyse:phpstan`
- `composer analyse:psalm`
- `composer lint`

## Build-08 Acceptance Mapping (Hook -> Tests)

The following test methods are required by contract for Build-08 implementation:

| Hook/event | Required assertions | Planned test location(s) |
|---|---|---|
| `authorship_set_authors_before` | Fires once before write; includes previous/requested IDs and normalized context | `tests/phpunit/test-post-saving.php` (`testSetAuthorsBeforeHookFiresOnInsert`, `testSetAuthorsBeforeHookFiresOnUpdate`), `tests/phpunit/test-rest-api-post-property.php` (`testSetAuthorsBeforeHookFiresOnRestUpdate`) |
| `authorship_set_authors_after` | Fires once after successful write; includes previous/new IDs and normalized context | `tests/phpunit/test-post-saving.php` (`testSetAuthorsAfterHookFiresOnInsert`, `testSetAuthorsAfterHookFiresOnUpdate`), `tests/phpunit/test-cli.php` (`testSetAuthorsAfterHookFiresDuringWpAuthorsWrite`) |
| `authorship_set_authors_failed` | Fires on failed write with exception and context; no `*_after` counterpart in same attempt | `tests/phpunit/test-post-saving.php` (`testSetAuthorsFailedHookFiresOnInvalidAuthorIds`), `tests/phpunit/test-rest-api-post-property.php` (`testSetAuthorsFailedHookFiresOnRestValidationFailure`) |
| `authorship_author_assignment_failure` (legacy) | Still emitted on matching insert/update failure paths with unchanged payload order | `tests/phpunit/test-post-saving.php` (`testLegacyAuthorAssignmentFailureHookStillFires`) |
| `authorship_deleted_user_sync_post_updated` | Fires once per updated post during delete-user sync with before/after IDs | `tests/phpunit/test-user-deletion.php` (`testDeletedUserSyncUpdatedHookFiresPerUpdatedPost`), `tests/phpunit/test-multisite.php` (`testDeletedUserSyncUpdatedHookFiresAcrossSites`) |
| `authorship_deleted_user_sync_post_failed` | Fires when per-post term update fails with WP_Error payload | `tests/phpunit/test-user-deletion.php` (`testDeletedUserSyncFailedHookFiresOnTermWriteError`) |
| `authorship_deleted_user_sync_completed` | Fires once per sync run with scanned/updated/failed counters | `tests/phpunit/test-user-deletion.php` (`testDeletedUserSyncCompletedHookFiresWithSummary`) |
| CLI dry-run behavior | No write-success lifecycle hooks emitted in dry-run mode | `tests/phpunit/test-cli.php` (`testDryRunDoesNotEmitSetAuthorsAfterHook`) |

Naming note:
- Method names above are normative targets for Build-08 planning. Equivalent names are acceptable if coverage intent and payload assertions are unchanged.

## Backlog Placement

- Roadmap backlog item: `docs/audit/roadmap-global.md` item #22.
- Planned execution slice: `.planning/phases/04-test-depth-and-ratcheting-authorship/04-Build-08-PLAN.md`.
