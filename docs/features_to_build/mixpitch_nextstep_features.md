# MixPitch Next-Step Feature Set (Phase 2)

## 1. DAW Uploader Scripts (Solo Pro focus)
**UX/positioning**
- “Upload directly from Reaper/Pro Tools/Logic to your MixPitch project in one click.”  
- DAW export → “MixPitch Uploader” script → auto-attach file to correct project/version.  

**Implementation**
- Lightweight Python/Lua/AppleScript snippets.  
- Scripts hit Upload API with project token, stream file to R2.  
- Return upload confirmation + version assignment.  

**Guardrails**
- Token-based auth per project.  
- Scripts open-source/user-installable.  

---

## 2. Simple Time Tracking Overlay (Solo Pro focus)
**UX/positioning**
- “Start session” toggle → log time to project.  
- Export CSV: project, date, total hours.  

**Implementation**
- Frontend timer (web/tray app).  
- Table: `time_logs(user_id, project_id, start, end, duration)`.  
- Aggregate totals per project.  

**Guardrails**
- Manual edit.  
- Idle timeout detection.  

---

## 3. Client Feedback Coach (Solo Pro focus)
**UX/positioning**
- Before submitting comments, show tips on clear notes.  
- Example: “Boost vocal 1dB at 2:15.”  
- Toggle “Don’t show again.”  

**Implementation**
- Static UI component.  
- Extend later with AI-driven rephrasing.  

**Guardrails**
- First release static; avoid pushy AI corrections.  

---

## 4. One-Click Archive + Invoice (Solo Pro focus)
**UX/positioning**
- “Finish Project” button → auto-zip deliverables, archive, generate invoice.  

**Implementation**
- Background job: zip files → R2 Archive tier.  
- Generate invoice CSV line item.  
- Email summary to studio + client.  

**Guardrails**
- Only if balance $0/invoice generated.  
- Configurable deliverables.  

---

## 5. Team Roles & Permissions Matrix (Large Studio focus)
**UX/positioning**
- Roles: Admin, Engineer, Assistant, Client, Reviewer.  
- Matrix: upload, comment, approve, download.  

**Implementation**
- `project_roles(user_id, project_id, role, permissions)`.  
- Enforce in controllers.  
- Role dropdown in invite modal.  

**Guardrails**
- Default safe roles.  
- Audit logs.  

---

## 6. Multi-Project Dashboard (Large Studio focus)
**UX/positioning**
- Table of projects: client, engineer, deadline, revision status, % complete.  
- Filters: engineer, deadline, status.  

**Implementation**
- Aggregate queries.  
- Livewire filters.  
- CSV/print export.  

**Guardrails**
- Role-based visibility.  

---

## 7. Custom Client Portals (Large Studio focus)
**UX/positioning**
- Configure branded portals (logo, color, domain).  
- Clients only see their department’s branding.  

**Implementation**
- `portal_brands` table: `id, name, logo_url, color_theme, domain`.  
- Projects link to brand_id.  
- Theming with Tailwind overrides.  

**Guardrails**
- Limit brands per plan.  
- DNS validation for domains.  

---

## 8. Royalty & Rights Notes (Large Studio focus)
**UX/positioning**
- Add royalty splits/ownership notes per project.  
- Table: Contributor, %, Notes.  
- Export with deliverables.  

**Implementation**
- `royalty_splits(project_id, contributor, percent, notes)`.  
- Export JSON/CSV in archive.  

**Guardrails**
- Informational only.  
- Totals ≤100%.  

---

## 9. Analytics Dashboard (Large Studio focus)
**UX/positioning**
- Charts: client listens, engineer turnaround, project duration.  

**Implementation**
- Log events: listens, seeks, loops, revisions.  
- Aggregate via nightly job.  
- Recharts frontend.  

**Guardrails**
- Respect privacy (aggregate/anonymize).  

---

# Build Order (Phase 2)
1. DAW Uploader Scripts  
2. Roles & Permissions + Dashboard  
3. Client Feedback Coach  
4. One-Click Archive + Invoice  
5. Custom Client Portals  
6. Royalty Notes  
7. Analytics Dashboard  
8. Time Tracking Overlay
