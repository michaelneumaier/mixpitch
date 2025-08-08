### Client Management Roadmap (Q3–Q4 2025)

This is an actionable, prioritized roadmap to take MixPitch client management to a best-in-class level. Use it as a living checklist. Priorities: P0 (now), P1 (next), P2 (later). Effort: S/M/L.

#### Legend
- P0: must-have foundation and quick wins
- P1: strong differentiators soon after
- P2: longer-term scale features

---

### P0 — Foundational Quick Wins

- [x] Add `client_id` to `projects` and backfill
  - Priority: P0 • Effort: M • Owner: TBD • Dependencies: none
  - Outcome: First-class `Client` relations; consistency and integrity
  - Success: 100% client-management projects have non-null `client_id`

- [x] White-label client portal (branding + configurable email templates)
  - Priority: P0 • Effort: M • Owner: TBD • Dependencies: none
  - Outcome: Producer-configurable logo, brand colors, copy; invite email templating
  - Success: At least 50% of active producers apply branding
  - Progress
    - [x] Branding fields on `users` and resolver service
    - [x] Invite email signature shows producer branding name
    - [x] Portal header supports producer logo (fallback icon)
    - [x] Settings UI to manage branding (logo/colors)
    - [x] Apply brand colors to portal accents (headers/borders); email subject/body customizable
    - [ ] Live preview toggle in settings (optional enhancement)

- [ ] Milestone/partial payments and per-file/whole-project approvals
  - Priority: P0 • Effort: M/L • Owner: TBD • Dependencies: Stripe Connect in place
  - Outcome: Flexible approvals, staged payouts
  - Success: 30% of paid projects use milestones within 60 days
  - Plan / Tasks
    - Data model
      - [x] Add `pitch_milestones` table: `pitch_id`, `name`, `description`, `amount`, `sort_order`, `status`, `payment_status`, `stripe_invoice_id`, `approved_at`, `payment_completed_at`
      - [x] Add per-file approval fields on `pitch_files`: `client_approval_status`, `client_approved_at`, `client_approval_note`
      - [x] Add `Pitch->milestones()` relation
    - Client Portal (client-facing)
      - [x] List milestones with amount and status; allow Approve & Pay where applicable
      - [x] Per-file approval actions (approve individual deliverables)
      - [x] Approve all files (bulk) without page refresh
      - [x] Whole-project approval flow remains (Approve & Pay full amount)
    - Routes & Controllers
      - [x] POST route to approve milestone and initiate payment session
      - [x] POST route to approve individual file
      - [x] POST route to approve all files (bulk)
      - [x] Webhook handling to mark milestone payments as paid (extend existing billing webhooks)
    - Services & Payments
      - [x] Reuse existing `InvoiceService`/Cashier or Checkout for milestone payments
      - [x] On payment success, mark milestone `payment_status=paid`, store `stripe_invoice_id`, timestamp
      - [x] Ensure producer Stripe Connect readiness check paths
    - Producer UX
      - [x] Minimal manage page to add/edit/delete milestones per pitch (inline form)
      - [x] Show file approval statuses; aggregate progress
    - Analytics
      - [x] Update revenue metrics to include sum of milestone payments
      - [x] Add milestone adoption KPI hook (basic)
    - Safeguards
      - [x] Only allow downloads of originals after milestone(s) or full payment as configured
      - [x] Validate pitch ↔ milestone relationships in all actions

- [x] Delivery Kanban (client‑management projects)
  - Priority: P0 • Effort: M • Owner: TBD • Dependencies: `client_id` migration, pitch events in place
  - Outcome: Producers see every in-flight client-management project, current stage, and next actions at a glance
  - Success: ≥60% of active producers use the board weekly; median cycle time from Submitted → Approved drops by 20%
  - Plan / Tasks
    - Stages (derived from existing data; no new tables)
      - [x] Condense to 3 high-level groups for clarity: Make • Review • Wrap Up
      - [x] Mapping logic (kept granular statuses, grouped in UI)
        - Make: Setup + In Progress
        - Review: Submitted + Client Feedback
        - Wrap Up: Approved + Completed – Payment Pending + Completed & Paid
    - Signals & badges surfaced on cards
      - [x] New client comments (recent `pitch_events.event_type = client_comment`)
      - [x] Revisions requested (status change events)
      - [x] Per-file approvals progress (approved/total from `pitch_files`)
      - [x] Milestone payments (paid/unpaid from `pitch_milestones`)
      - [x] Pitch payment status (paid/processing/pending)
      - [x] Client uploads (`project_files` with `uploaded_by_client`)
      - [x] Upcoming/overdue reminders (`client_reminders` for the project’s client)
    - UI/UX
      - [x] Livewire component `DeliveryPipelineBoard` with 3 columns (Make/Review/Wrap Up)
      - [x] SortableJS: drag within group to reorder; cross-group transitions only Make ↔ Review
      - [x] Card quick actions: Submit for Review, Add Reminder (modal), Manage
      - [x] Removed: View Milestones, Client Portal buttons from cards (keep cards focused)
      - [x] Column headers show counts and Wrap Up outstanding payment total
      - [x] Visual refresh to match site (gradient heading, simplified layout)
      - [x] Column color accents; client comment badges (unresolved/total) + latest excerpt/time
      - [x] Reminder chip inline on cards with Done action
    - Data loading & performance
      - [x] Eager load: `project.pitches.user, files, events, milestones`; recent `pitch_events`; `projectFiles` (client uploads)
      - [x] Pull `ClientReminder` for related `client_id` (due soon/overdue)
      - [x] Paginate/virtualize long columns; cache light aggregates (Load more per column; simple)
      - [x] Persist within-column ordering (simple `pitches.delivery_sort_order`)
    - Routes & navigation
      - [x] Link entry point from Producer Client Management dashboard
      - [x] Gate via producer policy (project ownership)
    - Implementation tasks
      - [x] Stage derivation helper (pure function from project/pitch state → column)
      - [x] Grouping mapper (7 statuses → 3 groups)
      - [x] Badge builders for comments, approvals, payments, uploads, reminders
      - [x] Allowed drag/drop handlers: `submitForReview` (Make → Review), `returnToInProgress` (Review → Make), reorder within group
      - [ ] Tests: unit (derivation, grouping, badges), feature (transitions, visibility), browser (DnD happy path)
      - [x] Client comments filter includes recent pitch events or any/unresolved file-level client comments; recent window selector (3/7/14/30d/All)
    - Analytics
      - [x] Simple aggregates per column (counts, total outstanding payments)
      - [x] Time-in-stage badge (based on latest status_change event)

- [ ] CRM segments, saved views, reminders, CSV import + de-duplication (in progress)
  - Priority: P0 • Effort: M • Owner: TBD • Dependencies: `client_id` migration
  - Outcome: Basic CRM productivity; quick onboarding via CSV
  - Success: >70% producers create at least one segment/view; CSV import NPS ≥ 8
  - Progress
    - [x] Saved views on client management dashboard (name + default)
    - [x] Basic reminders model and “Upcoming Reminders” widget (complete/snooze)
    - [x] Add “Create reminder” UX from client modal and project rows
    - [x] CSV import scaffolding + upload UI; background job processes rows
    - [x] Mapping/preview step before import; sample CSV download; error samples in results
    - [ ] Batching and large-file performance improvements
    - [ ] De-duplication rules and merge suggestions per producer

- [ ] Analytics: Client LTV and funnel basics on dashboard (in progress)
  - Priority: P0 • Effort: S/M • Owner: TBD • Dependencies: `client_id` migration
  - Outcome: Visibility on conversion and value; actionable insights
  - Success: Dashboard adoption > 60% weekly active producers
  - Progress
    - [x] Pivoted dashboard/producer analytics to `client_id` for unique client counts
    - [x] LTV metrics computed and displayed (total and average per client)
    - [x] Funnel metrics computed and displayed (created → submitted → review → approved/completed)

---

### P1 — Differentiators

- [ ] Organizations and contacts model
  - Priority: P1 • Effort: M • Owner: TBD • Dependencies: `client_id` complete
  - Outcome: Multi-contact orgs; primary contact; role-based access

- [ ] Deal pipeline (Kanban), next-action reminders, sequences
  - Priority: P1 • Effort: M • Owner: TBD • Dependencies: segments/reminders
  - Outcome: Track leads and deals; automate follow-ups

- [ ] Portal UX: per-file approval checklists, change summaries, A/B compare snapshots
  - Priority: P1 • Effort: M/L • Owner: TBD • Dependencies: approvals
  - Outcome: Clearer reviews; faster approvals

- [ ] E-signature with license templates in approval flow
  - Priority: P1 • Effort: M • Owner: TBD • Dependencies: license templates
  - Outcome: Signed licenses on completion; PDF archive

- [ ] Automations and multi-channel notifications (email, in-app, Slack/Discord, SMS)
  - Priority: P1 • Effort: M • Owner: TBD • Dependencies: event hooks
  - Outcome: Right-time comms; reduced churn

---

### P2 — Scale and Extensibility

- [ ] Public API + OAuth; Zapier/Make connectors
  - Priority: P2 • Effort: L • Owner: TBD • Dependencies: stable data model

- [ ] Advanced payments: deposits, discounts/taxes, multi-currency, credit notes
  - Priority: P2 • Effort: L • Owner: TBD • Dependencies: payments

- [ ] Escrow-like flow for high-value deals; payout rules per `PayoutHoldSetting`
  - Priority: P2 • Effort: L • Owner: TBD • Dependencies: payouts

- [ ] Advanced portal ops: offline bundles, resumable uploads, antivirus, download limits
  - Priority: P2 • Effort: M/L • Owner: TBD • Dependencies: file service

- [ ] Security & compliance: granular team roles, internal notes, URL rotation/revoke, audit log, GDPR tooling
  - Priority: P2 • Effort: M/L • Owner: TBD • Dependencies: policy layer

---

### Cross-cutting Analytics and KPIs

- **Acquisition**: conversion rate (invite → approval), time-to-first-response
- **Delivery**: revisions per project, cycle time, on-time delivery rate
- **Monetization**: client LTV, ARPP (avg revenue per project), milestone adoption
- **Retention**: repeat-client rate, churn predictors (idle > N days)

---

### Dependencies Map

- `client_id` in `projects` → unlocks CRM, analytics, org/contacts, API quality
- Approvals/Milestones → enables e-signature, staged payouts, analytics depth
- Automations → needs event hooks and stable notification preferences

---

### Tracking & Rituals

- Weekly triage: update statuses, unblock dependencies
- Monthly review: KPIs progress, move P1/P2 up if demand warrants
- Maintain this file as the single source of truth for client-management roadmap


