---
phase: 05-interoperability-and-compatibility-hardening-authorship
plan: 05-Build-01
type: build
wave: 1
depends_on: ["05-interoperability-and-compatibility-hardening-authorship/05-01", "04-test-depth-and-ratcheting-authorship/04-Build-08"]
files_modified:
  - "inc/template.php"
  - "inc/namespace.php"
  - "inc/class-insert-post-handler.php"
  - "inc/cli/class-migrate-command.php"
  - "tests/phpunit/test-template.php"
  - "tests/phpunit/test-post-saving.php"
  - "tests/phpunit/test-rest-api-post-property.php"
  - "tests/phpunit/test-cli.php"
  - "tests/phpunit/test-user-deletion.php"
  - "README.md"
  - "docs/audit/roadmap-global.md"
  - "docs/audit/roadmap-01.md"
autonomous: true
user_setup: []
must_haves:
  truths:
    - "The authorship taxonomy remains canonical; `post_author` mirrors the chosen primary author for compatibility only."
    - "Primary-author resolution must be deterministic by default and overridable via an explicit extension point."
    - "Synchronization must not silently break guest-author, deletion, migration, or multi-author save flows."
  artifacts:
    - path: "inc/template.php"
      provides: "Primary-author resolution and `post_author` synchronization logic"
    - path: "tests/phpunit/test-template.php"
      provides: "Primary-author contract and compatibility regression coverage"
  key_links: []
---

<objective>
Define and implement a deterministic primary-author compatibility contract so supported mutation paths keep `post_author` aligned with Authorship attribution without changing the taxonomy-first architecture.
</objective>

<tasks>

<task type="auto">
  <name>05-01-01 Audit all authorship mutation entry points that can diverge from `post_author`</name>
  <files>inc/template.php, inc/class-insert-post-handler.php, inc/cli/class-migrate-command.php, inc/namespace.php</files>
  <action>
    - Enumerate the write paths that call `set_authors()` or otherwise affect attributed authors.
    - Identify where primary-author synchronization must occur to avoid partial behavior across REST, classic save hooks, and CLI migrations.
  </action>
  <verify>The implementation notes map each mutation path to the shared synchronization point.</verify>
  <done>Mutation-path audit complete and shared sync point identified.</done>
</task>

<task type="auto">
  <name>05-01-02 Add contract tests for primary-author selection and synchronization</name>
  <files>tests/phpunit/test-template.php, tests/phpunit/test-post-saving.php, tests/phpunit/test-rest-api-post-property.php, tests/phpunit/test-cli.php, tests/phpunit/test-user-deletion.php</files>
  <action>
    - Add regression coverage for single-author, multi-author, and guest-author attribution writes.
    - Add coverage proving the default primary-author rule is deterministic and that `post_author` follows it on successful writes.
    - Add coverage for deletion/reassignment and migration scenarios where the primary author changes.
  </action>
  <verify>`composer test:integration` and `WP_MULTISITE=1 composer test:integration` pass with new compatibility contract tests.</verify>
  <done>Contract tests cover all intended synchronization paths.</done>
</task>

<task type="auto">
  <name>05-01-03 Implement primary-author resolution and filterable synchronization behavior</name>
  <files>inc/template.php, inc/namespace.php</files>
  <action>
    - Add a helper that resolves the compatibility primary author from the attributed author list.
    - Expose an explicit filter so sites can alter primary-author selection where necessary.
    - Update successful authorship persistence to synchronize `post_author` only after author validation and term persistence succeed.
  </action>
  <verify>Runtime behavior matches tests and no path syncs `post_author` before taxonomy persistence succeeds.</verify>
  <done>Primary-author resolution and safe `post_author` synchronization are implemented.</done>
</task>

<task type="auto">
  <name>05-01-04 Document the compatibility contract and re-verify gates</name>
  <files>README.md, docs/audit/roadmap-global.md, docs/audit/roadmap-01.md</files>
  <action>
    - Document that `post_author` is compatibility state derived from the first/primary attributed author.
    - Re-run the repo's PHP quality gates after implementation.
  </action>
  <verify>`composer test`, `composer analyse:phpstan`, `composer analyse:psalm`, and `composer lint` pass.</verify>
  <done>Build-01 behavior is documented and verified.</done>
</task>

</tasks>

<status>
Planned on 2026-04-08.

Execution state:
- Not started.
- This is the first execution slice for Phase 05 because later HTML metadata work depends on a stable primary-author contract.
</status>
