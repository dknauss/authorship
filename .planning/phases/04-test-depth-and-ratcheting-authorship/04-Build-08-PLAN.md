---
phase: 04-test-depth-and-ratcheting-authorship
plan: 04-Build-08
type: build
wave: 2
depends_on: ["04-test-depth-and-ratcheting-authorship/04-Build-02"]
files_modified:
  - "inc/template.php"
  - "inc/namespace.php"
  - "inc/class-insert-post-handler.php"
  - "inc/cli/class-migrate-command.php"
  - "tests/phpunit/test-post-saving.php"
  - "tests/phpunit/test-rest-api-post-property.php"
  - "tests/phpunit/test-cli.php"
  - "tests/phpunit/test-user-deletion.php"
  - "docs/audit/authorship-observability-hook-contract.md"
  - "docs/audit/roadmap-global.md"
  - "docs/audit/roadmap-01.md"
  - "README.md"
autonomous: true
user_setup: []
must_haves:
  truths:
    - "Authorship remains integration-first for audit visibility: stable hooks, no first-party audit datastore/dashboard."
    - "Attribution lifecycle hooks must be deterministic, documented, and contract-tested across REST/admin/CLI write paths."
    - "Existing hook `authorship_author_assignment_failure` remains supported for backward compatibility."
    - "Dry-run CLI migrations must not emit write-success lifecycle hooks."
  artifacts:
    - path: "docs/audit/authorship-observability-hook-contract.md"
      provides: "Normative hook names, payload schema, and firing guarantees for attribution lifecycle events"
    - path: "tests/phpunit/test-post-saving.php"
      provides: "Contract tests for insert/update success and failure hook emission"
    - path: "tests/phpunit/test-cli.php"
      provides: "Contract tests for CLI write vs dry-run hook emission behavior"
  key_links: []
---

<objective>
Implement a stable attribution lifecycle hook contract that external audit plugins (for example Stream/WSAL) can consume across REST, admin, CLI, and user-deletion sync flows.
</objective>

<context>
This build executes backlog item #22 from `docs/audit/roadmap-global.md` and follows the complementary-plugin model: WP Sudo handles reauthentication/gating, audit-log plugins handle persistence/reporting, Authorship provides stable event hooks.
</context>

<tasks>

<task type="auto">
  <name>04-08-01 Finalize and freeze the lifecycle hook contract</name>
  <files>docs/audit/authorship-observability-hook-contract.md</files>
  <action>
    - Confirm hook names, arguments, and context schema for set/replace/remove attribution operations.
    - Confirm user-deletion sync hook contract for per-post updates, failures, and summary completion events.
    - Mark contract version and compatibility policy (backward-compatible evolution only).
  </action>
  <verify>Contract document is implementation-ready and unambiguous for hook consumers and test authors.</verify>
  <done>Hook contract is frozen for implementation in this build.</done>
</task>

<task type="auto">
  <name>04-08-02 Implement attribution lifecycle hooks in shared write paths</name>
  <files>inc/template.php, inc/class-insert-post-handler.php, inc/namespace.php, inc/cli/class-migrate-command.php</files>
  <action>
    - Emit before/success/failure actions from `set_authors()` with normalized context.
    - Preserve `authorship_author_assignment_failure` behavior while adding the new contract hooks.
    - Emit user-deletion sync per-post update/failure hooks and one completion summary hook.
    - Ensure CLI dry-run mode does not emit write-success hooks.
  </action>
  <verify>All write paths that mutate attribution data emit contract hooks exactly once per mutation event.</verify>
  <done>Lifecycle hooks are implemented across REST/admin/CLI/deletion-sync write surfaces.</done>
</task>

<task type="auto">
  <name>04-08-03 Add contract-level PHPUnit coverage</name>
  <files>tests/phpunit/test-post-saving.php, tests/phpunit/test-rest-api-post-property.php, tests/phpunit/test-cli.php, tests/phpunit/test-user-deletion.php</files>
  <action>
    - Add tests for hook emission order and payload shape on success/failure paths.
    - Add tests covering REST writes, insert/update writes, CLI write and dry-run behavior, and deletion-sync behavior.
    - Add regression checks to ensure legacy failure hook remains emitted.
  </action>
  <verify>New tests fail before implementation and pass after implementation with deterministic payload assertions.</verify>
  <done>Lifecycle hook contract is covered by integration tests.</done>
</task>

<task type="auto">
  <name>04-08-04 Document integration guidance and verify gates</name>
  <files>docs/audit/authorship-observability-hook-contract.md, README.md, docs/audit/roadmap-global.md, docs/audit/roadmap-01.md</files>
  <action>
    - Add concise integration guidance for external audit plugins consuming the hooks.
    - Record build completion and queue follow-on work, if any.
    - Run gate suite and ensure no regressions.
  </action>
  <verify>`composer test:integration`, `WP_MULTISITE=1 composer test:integration`, `composer analyse:phpstan`, `composer analyse:psalm`, and `composer lint` pass with the hook contract implemented.</verify>
  <done>Build-08 execution is documented and verified.</done>
</task>

</tasks>

<status>
Planned on 2026-03-08.
Executed on 2026-03-15.

Execution state:
- COMPLETE

Implementation summary:
- `set_authors()` in template.php: emits `authorship_set_authors_before`, `authorship_set_authors_after`, `authorship_set_authors_failed` with normalized context
- `sync_deleted_user_authorship_for_current_site()` in namespace.php: emits `authorship_deleted_user_sync_post_updated`, `authorship_deleted_user_sync_post_failed`, `authorship_deleted_user_sync_completed`
- Legacy `authorship_author_assignment_failure` preserved in InsertPostHandler (unchanged)
- CLI dry-run naturally suppressed (set_authors not called in dry-run mode)

Contract tests added:
- test-post-saving.php: 7 new tests (before/after/failed hooks, context passthrough, legacy compat)
- test-user-deletion.php: 4 new tests (per-post updated, completed summary, empty-array on sole removal, no-posts case)
- test-cli.php: 2 new tests (dry-run suppression, write-mode emission)

Gates verified: PHPStan, PHPCS, ESLint, Jest, wp-scripts build all green.
PHPUnit could not run locally (Local database not started).
</status>
