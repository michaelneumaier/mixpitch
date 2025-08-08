### Client Management Quick Wins — Implementation Plan

Scope covers the P0 items from the roadmap to deliver tangible value quickly. Each section includes design, data changes, UI, services, tests, risks, and rollout.

---

## 1) Add `client_id` to `projects` and backfill

Goal: Link projects to `clients.id` (owned by the producer) instead of `client_email`; keep `client_email` for display and dedupe.

- Design
  - Schema: `projects.client_id` nullable → populate → set not-null; FK to `clients(id)` with `onDelete: set null` or `cascade` depending on policy
  - Uniqueness: `clients` already enforces `user_id + email` unique
  - Backfill rule: For each project with `workflow_type = client_management`, find/create client for `project.user_id + project.client_email`

- Data changes
  - Migration 1: add `client_id` (nullable, indexed, FK deferred)
  - Backfill command: create/find `Client` records; update `projects.client_id`
  - Migration 2: enforce FK and optional not-null for client-management projects (check constraint or app-level validation)

- App changes
  - Update Eloquent relations: `Project belongsTo Client`; keep accessors for `client_email`
  - Replace joins and sums in dashboard/analytics to use `client_id` relations
  - `Client->projects()` should be `hasMany(Project::class, 'client_id')`
  - Client Portal access: add middleware to allow BOTH signed guest access and authenticated registered client access (email/user match). Update routes to use `signed_or_client` instead of `signed` where appropriate.

- UI
  - Project create form: look up existing client by email for the current producer; show “link to existing client?” with preview of tags/notes
  - Dashboard and Manage pages: rely on relation; no UX change needed

- Services
  - NotificationService and PitchWorkflowService: no change, but confirm queries

- Tests
  - Migration/backfill tests on sample data
  - Dashboard stats (revenue, project counts) use `client_id`
  - Client delete prevention: still blocked if `client.projects()->count()>0`

- Risks
  - Email collisions across producers are safe; across same producer handled by unique index
  - Ensure idempotent backfill for re-runs

- Rollout
  - Phase A: add field + backfill; run in maintenance window; logs and dry-run option
  - Phase B: cutover code to new relation; retain fallback reads for one release
  - Phase C: enforce constraints; remove legacy code paths

---

## 2) White-label client portal and email templates

Goal: Producer-level branding (logo, colors, text blocks) across portal and invites.

- Design
  - Config storage under `users` (producer) or `teams`: `branding_logo_url`, `brand_primary`, `brand_secondary`, `brand_text`, `email_template_ids`
  - Theme resolver service; blade components read from resolver; defaults maintained

- UI
  - Settings page for producers to upload logo, pick colors, edit copy (with preview)
  - Client invite email template: handlebars-like variables (`{{ project.title }}`, `{{ client.first_name }}`)

- Services
  - NotificationService to render with chosen template; fallback to default

- Tests
  - Snapshot tests for rendered emails (markdown-to-HTML); feature tests for portal CSS variables applied

- Risks
  - Contrast/accessibility; provide auto-contrast safeguard

- Rollout
  - Soft launch behind feature flag `branding.enabled` with default OFF; enable for pilot users

---

## 3) Milestones/partial payments and per-file/whole-project approvals

Goal: Flexible delivery and payment acceptance.

- Design
  - Data: `project_milestones` table: id, project_id, title, amount, status (pending/ready/approved/paid), files_scope (IDs), order_index
  - Approvals: add `approval_scope` (project vs milestone vs file-set)
  - Payments: Stripe Checkout per milestone; webhook sets `paid` and triggers deliverable unlock

- UI
  - Manage page: create/edit milestones, assign deliverables; track status
  - Client portal: approve per milestone; show pending/paid badges; unlock downloads post-payment

- Services
  - PaymentService wrapper for milestone checkout; integrate with existing Stripe Connect flow

- Tests
  - Feature tests: approve-with-payment, approve-without-payment; file unlock rules

- Risks
  - Scope mapping between files and milestones; ensure clean UX for mixed audio/non-audio

- Rollout
  - Start with project-level milestones (no per-file gate) → layer per-file later

---

## 4) CRM segments, saved views, reminders, CSV import + de-duplication

- Design
  - Data: `client_views` (serialized filters), `client_reminders` (client_id, due_at, note, snooze_until, status), basic `client_import_jobs`
  - Dedupe: per-producer using normalized email; optional domain-suggested merging

- UI
  - Dashboard filters → “Save view”; list of saved views
  - Client details: reminders panel with snooze and complete
  - CSV import wizard: map columns → preview → import → results; show merges

- Services
  - Import pipeline job with row validators; dedupe service; reminder scheduler job emitting notifications

- Tests
  - Import fixtures; dedupe rules; reminders scheduling and notifications; permissions

- Risks
  - Large CSV performance; add streaming and batching; background jobs

- Rollout
  - Ship views + reminders first; CSV import next; dedupe suggestions after

---

## 5) Analytics: Client LTV and funnel basics

- Design
  - Metrics: LTV = sum of paid amounts over all client projects; funnel = created → submitted → ready_for_review → approved/completed
  - Precompute nightly into `analytics_client_daily` for speed; real-time lightweight aggregates on dashboard

- UI
  - Cards on client dashboard; per-client panel with sparkline

- Services
  - Analytics service to aggregate over `client_id`; cache with tags (client, producer)

- Tests
  - Unit tests for aggregator; feature tests verifying dashboard numbers with seed data

- Risks
  - Double counting paid events; ensure single payment event per milestone; reconciliation with Stripe

- Rollout
  - Start with synchronous queries with indexes; add background precompute once load increases

---

### Project Management & Execution

- Tracking: Create GitHub issues per bullet, label `client-mgmt`, add `effort` and `priority`
- Branching: Feature branches per module (`feature/client-id-migration`, `feature/branding-portal`, ...)
- CI: Add smoke tests for portal routes and core workflows
- Flags: Use config or database flags for gradual rollout

---

### Acceptance Criteria Summary

- `client_id` exists, backfilled, and all queries use it for client-management projects
- Producers can brand the portal and invites; preview and save settings
- Milestones can be created and approved; paid milestones unlock deliverables
- CRM views and reminders are usable; CSV import supports basic mapping and dedupe
- Dashboard shows LTV and stage funnel accurately on seeded datasets


