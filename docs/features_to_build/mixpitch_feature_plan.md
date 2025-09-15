# MixPitch Feature Ideas & Implementation Plan (from Survey Feedback)

## Link Catcher & Auto-Mirror
**UX/positioning**
- In Client Portal “Upload” panel, add a secondary tab: **Upload** | **Import From Link**.  
- Microcopy:  
  - “**Best:** upload files here for fastest transfers and automatic versioning.”  
  - “**Already sent a link?** Paste a WeTransfer/Drive/Dropbox link and we’ll **mirror** it safely into your project (no more expired links).”

**Implementation**
- Allowlist common file-sharing domains.  
- Probe URL, enqueue fetch job, stream to R2, compute checksum, dedupe.  
- Support OAuth for private Drive/Dropbox later.  
- Show progress in UI.  

**Abuse controls**
- File-type allowlist, ClamAV scan, size caps per plan.  
- Rate-limit per project/user.  
- Audit log with source + checksum.

---

## Project Maildrop
**UX**
- Each project has a unique email address.  
- Forwarding client mails automatically threads them in the project.  
- Attachments are auto-saved, timecodes auto-parsed.

**Implementation**
- Use inbound email service (Postmark, Mailgun, SendGrid).  
- Thread by Message-ID.  
- Regex parse timecodes, make them clickable in player.  
- Store attachments in R2.  
- Rotate address tokens as needed.

**Security**
- Sender whitelist (per project).  
- DKIM/SPF/DMARC verification.  
- Attachment scanning.  
- Rate limiting + moderation for unknown senders.

---

## Auto Versioning
**UX**
- Auto-append V01, V02, etc.  
- Normalize filenames on download.  
- Version timeline in player with A/B compare.

**Implementation**
- New `versions` table.  
- Assign next vnum per track on upload.  
- Compute LUFS for level-match.  
- Rewrite filename with V## on download.

---

## After Approval Guardrail
**UX**
- Project setting: e.g., “2 revisions included, extra $X each.”  
- Approve → lock comments.  
- “Request More Changes” button → opens new paid round.

**Implementation**
- `revision_rounds` table.  
- Approval locks round.  
- Request → create payment intent, unlock comments.  
- Optionally webhook to accounting export.

---

## Large Transfer Ingest
- Add resumable uploads (tus or S3 multipart).  
- UI: progress bar, retry, folder upload.  
- Uppy for frontend.

---

## Light Intake
**High priority fields**
- Client contacts, deadlines, references, deliverables, revision policy, project notes, payment terms.

**Medium/Low priority**
- File naming conventions, sample rate, credits, usage rights, NDA.  
- Optional: time zone, pronouns, language.

**Automation**
- Auto-create project folders.  
- Set revision policy, deadlines.  
- Pre-load reference track slot.  
- Invite collaborators, configure Maildrop whitelist.

---

## Language-Aware Feedback Helpers
**UX**
- Preferred Language per participant.  
- Auto-translate toggle in comment threads.  
- “Translated from X • Show original” pill.

**Implementation**
- Detect language per message.  
- Translate via DeepL / Google Translate / AWS Translate.  
- Cache translations.  
- Privacy: redact emails/URLs before sending.

---

## Accounting-Friendly Exports
**MVP**
- CSVs: payout reconciliation, invoice line items, project ledger.  
- Covers imports into QuickBooks, Xero, Wave.

**Next steps**
- Webhooks (Zapier/Make).  
- Direct integrations (QuickBooks Online API).  
- UBL/Peppol export for EU compliance.

---

## Build Order
1. Auto Versioning + Fair Compare  
2. Project Maildrop  
3. Link Catcher & Auto-Mirror  
4. After Approval Guardrail  
5. Resumable uploads  
6. Light Intake  
7. Language helpers  
8. Accounting exports
