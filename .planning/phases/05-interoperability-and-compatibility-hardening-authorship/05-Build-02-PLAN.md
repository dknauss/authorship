---
phase: 05-interoperability-and-compatibility-hardening-authorship
plan: 05-Build-02
type: build
wave: 2
depends_on: ["05-interoperability-and-compatibility-hardening-authorship/05-Build-01"]
files_modified:
  - "inc/template.php"
  - "inc/namespace.php"
  - "tests/phpunit/test-template.php"
  - "README.md"
  - ".planning/known-gaps.md"
  - "docs/audit/roadmap-global.md"
  - "docs/audit/roadmap-01.md"
autonomous: true
user_setup: []
must_haves:
  truths:
    - "HTML structured metadata must build on the primary-author/compatibility contract established in Build-01."
    - "Phase 05 HTML metadata work does not re-open Byline/feed companion scope."
    - "The output contract must be explicit and developer-friendly, not an undocumented global side effect."
  artifacts:
    - path: "inc/template.php"
      provides: "Structured author metadata helper/output API"
    - path: ".planning/known-gaps.md"
      provides: "Updated gap status for HTML metadata support"
  key_links: []
---

<objective>
Add a defined HTML structured-author metadata surface so themes and SEO integrations can expose Authorship attribution via Schema.org/JSON-LD without relying on ad hoc downstream glue.
</objective>

<tasks>

<task type="auto">
  <name>05-02-01 Define the supported structured-metadata contract</name>
  <files>inc/template.php, README.md</files>
  <action>
    - Decide whether the supported surface is helper-returned data, rendered JSON-LD, or an explicitly controlled output hook.
    - Specify expected behavior for single-author, multi-author, guest-author, and unsupported-post-type cases.
  </action>
  <verify>The build notes and README state exactly how implementors consume the new metadata surface.</verify>
  <done>Structured-metadata contract is explicit and bounded.</done>
</task>

<task type="auto">
  <name>05-02-02 Add tests for HTML metadata generation</name>
  <files>tests/phpunit/test-template.php</files>
  <action>
    - Add regression coverage for the metadata payload/output for single-author and multi-author posts.
    - Cover guest authors, empty authorship state, and unsupported post types.
  </action>
  <verify>PHPUnit covers the supported metadata edge-case matrix.</verify>
  <done>Structured-metadata tests are in place.</done>
</task>

<task type="auto">
  <name>05-02-03 Implement the metadata helper/output and update gap docs</name>
  <files>inc/template.php, inc/namespace.php, .planning/known-gaps.md</files>
  <action>
    - Implement the chosen metadata surface with explicit control over when output occurs.
    - Update known-gap documentation to mark the HTML metadata gap resolved or narrowed with precise caveats.
  </action>
  <verify>Implementation matches the documented contract and known-gaps doc reflects actual delivered scope.</verify>
  <done>Structured author metadata is available for HTML consumers and documented accurately.</done>
</task>

<task type="auto">
  <name>05-02-04 Re-verify gates and record execution state</name>
  <files>docs/audit/roadmap-global.md, docs/audit/roadmap-01.md</files>
  <action>
    - Re-run relevant PHP gates after implementation.
    - Update roadmap docs with Build-02 execution results and next queued slice.
  </action>
  <verify>`composer test`, `composer analyse:phpstan`, `composer analyse:psalm`, and `composer lint` pass.</verify>
  <done>Build-02 execution state documented and green.</done>
</task>

</tasks>

<status>
Planned on 2026-04-08.

Execution state:
- Not started.
- Queued behind Build-01 because metadata output depends on the primary-author compatibility contract.
</status>
