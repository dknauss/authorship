---
phase: 05-interoperability-and-compatibility-hardening-authorship
plan: 05-Build-03
type: build
wave: 3
depends_on: ["05-interoperability-and-compatibility-hardening-authorship/05-Build-02"]
files_modified:
  - "README.md"
  - "docs/manual-testing-checklist.md"
  - ".planning/known-gaps.md"
  - "docs/testing-environments.md"
  - "docs/audit/roadmap-global.md"
  - "docs/audit/roadmap-01.md"
autonomous: true
user_setup: []
must_haves:
  truths:
    - "Phase 05 should leave implementors with a usable compatibility matrix, not just raw runtime changes."
    - "Documentation must distinguish delivered compatibility surfaces from future backlog items such as classic editor support."
  artifacts:
    - path: "README.md"
      provides: "Implementor guidance for the compatibility and metadata contract"
    - path: "docs/manual-testing-checklist.md"
      provides: "Manual verification steps for theme/SEO/caching compatibility"
  key_links: []
---

<objective>
Convert the Phase 05 compatibility behavior into explicit implementor guidance and a verification matrix for themes, SEO plugins, caches, and downstream maintainers.
</objective>

<tasks>

<task type="auto">
  <name>05-03-01 Document the compatibility matrix for downstream consumers</name>
  <files>README.md, .planning/known-gaps.md</files>
  <action>
    - Document how Authorship now treats `post_author`, attributed authors, and structured metadata.
    - Clarify what standard themes/template tags get automatically versus what implementors must wire up themselves.
  </action>
  <verify>README and known-gaps docs present a coherent, non-contradictory compatibility story.</verify>
  <done>Compatibility contract is documented for implementors.</done>
</task>

<task type="auto">
  <name>05-03-02 Add manual verification guidance for theme/SEO compatibility</name>
  <files>docs/manual-testing-checklist.md</files>
  <action>
    - Add manual test prompts for primary-author sync, author archive behavior, and structured metadata inspection.
    - Include guidance for validating interactions with SEO plugins and caches where practical.
  </action>
  <verify>The manual checklist contains concrete Phase 05 verification scenarios and expected outcomes.</verify>
  <done>Manual compatibility verification guidance added.</done>
</task>

<task type="auto">
  <name>05-03-03 Reconcile residual backlog and handoff the next future lane</name>
  <files>docs/audit/roadmap-global.md, docs/audit/roadmap-01.md</files>
  <action>
    - Mark the delivered compatibility items complete and re-state what remains future scope.
    - Route classic editor support, broader interoperability runbooks, and optional non-REST network-mode work into the next backlog lane rather than Phase 05 scope.
  </action>
  <verify>Roadmap docs clearly separate completed Phase 05 work from future product features.</verify>
  <done>Phase 05 handoff leaves a clean residual backlog.</done>
</task>

</tasks>

<status>
Planned on 2026-04-08.

Execution state:
- Not started.
- Queued behind Build-02 as the documentation and rollout-hardening slice for delivered Phase 05 behavior.
</status>
