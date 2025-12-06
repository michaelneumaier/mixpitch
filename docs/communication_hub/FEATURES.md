# Communication Hub Features Specification

## Feature Overview

| Feature | Priority | Phase | Description |
|---------|----------|-------|-------------|
| Unified Hub Component | High | 1 | Single access point for all communication |
| Read Receipts | High | 1 | Delivery and read status on messages |
| FAB Entry Point | High | 1 | Floating Action Button to access hub |
| Activity Feed | High | 1 | Chronological view of all project events |
| Message Threading | Medium | 1 | Group related messages together |
| Quick Actions/Templates | Medium | 2 | Pre-built responses for common scenarios |
| Smart Email Integration | Medium | 2 | Email replies flow into platform |
| Search & History | Medium | 2 | Find past conversations |
| Export Capability | Low | 2 | Download conversation history |
| Work Sessions | Medium | 3 | Time tracking with status broadcasting |
| Focus Mode | Medium | 3 | Producer availability controls |
| Presence Indicators | Medium | 3 | Online/active status |
| Activity Timeline | Low | 3 | Historical work activity view |
| Real-Time Updates | Low | 4 | WebSocket-based live updates |

---

## Phase 1 Features

### 1.1 Unified Hub Component

**What It Contains**:
- Messages (direct communication)
- Activity Feed (all project events)
- Notifications (items requiring action)
- Presence (who's online/active)
- Quick Actions (context-aware shortcuts)

**Tabs/Sections**:
```
[Messages (2)] [Activity] [To-Do (1)]
```

**Key Behaviors**:
- Shows unread count on FAB badge
- Remembers last viewed tab
- Scrolls to unread messages on open
- Marks messages as read when viewed

### 1.2 FAB (Floating Action Button)

**Visual Design**:
- NOT a chat bubble (too limiting)
- Custom "hub" icon suggesting dashboard/command center
- Badge for unread count
- Presence dot when other party is online

**States**:
| State | Visual | Description |
|-------|--------|-------------|
| Quiet | Subtle, muted | No new activity |
| Attention | Bright + badge | New activity |
| Urgent | Pulsing/animated | Action required |
| Active | Green presence dot | Other party online |

**Desktop Behavior**:
- Fixed position bottom-right
- Hover shows preview card
- Click opens side panel

**Mobile Behavior**:
- Fixed position bottom-right
- Tap opens full-screen sheet
- Swipe down to dismiss

### 1.3 Read Receipts

**Tracking Levels**:
1. **Delivered**: Message saved to database
2. **Read**: Recipient viewed the message

**Visual Indicators**:
```
✓   Delivered (single check)
✓✓  Read (double check or "Seen")
```

**Implementation**:
- Add `read_at` and `read_by` to PitchEvent
- Track when message enters viewport
- Batch read receipt updates to reduce requests

**Privacy Consideration**:
- Read receipts always on for project communication (professional context)
- No option to disable (unlike personal messaging apps)

### 1.4 Activity Feed

**Event Types Displayed**:
- Messages (producer/client)
- File uploads
- Status changes
- Approvals/revision requests
- System notifications

**Grouping**:
- By day ("Today", "Yesterday", "Dec 3")
- Consecutive events from same user collapsed

**Filtering** (optional):
- All activity
- Messages only
- Files only

---

## Phase 2 Features

### 2.1 Quick Actions / Templates

**When to Show**:
- After receiving feedback
- After uploading files
- When status changes

**Suggested Responses**:
```
┌─────────────────────────────────────────────────────────────────────┐
│ Sarah requested revisions:                                          │
│ "The bass is too muddy in the chorus"                               │
│                                                                     │
│ Suggested responses:                                                │
│ ┌─────────────────────────────────────────────────────────────────┐ │
│ │ ✓ "Got it, I'll clean up the low end in the chorus"            │ │
│ │ ? "Could you specify which frequencies feel muddy?"            │ │
│ │ + Write custom response...                                      │ │
│ └─────────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────┘
```

**Template Categories**:
| Scenario | Templates |
|----------|-----------|
| Acknowledge feedback | "Got it, working on it", "Thanks for the feedback" |
| Need clarification | "Could you clarify...?", "Can you give an example?" |
| Update delivered | "New version ready", "Changes made, please review" |
| Setting expectations | "I'll have this ready by...", "Working on this today" |

**NOT for Templates**:
- Detailed technical explanations
- Approval/rejection messages
- Complex revision discussions

### 2.2 Smart Email Integration

**Outbound (Platform → Email)**:
- Every message can optionally send email copy
- Email includes "View in MixPitch" button
- Email footer: "Reply to this email to respond"

**Inbound (Email → Platform)**:
- Parse email replies and create PitchEvent
- Match by reply-to address or thread ID
- Strip email signatures/quoted text

**Email Template**:
```
Subject: New message from Mike on "Album Master Mix"

Mike says:
"I've cleaned up the bass frequencies you mentioned.
The new version is ready for your review!"

[View in MixPitch]

---
Reply to this email to respond directly.
Your reply will appear in the MixPitch conversation.
```

### 2.3 Message Options

**Send Options**:
```
[Type your message here...]

───────────────────────────────────────────
☑ Also send via email (recommended for important updates)
☐ Mark as urgent (sends immediate notification)
☐ Request read confirmation
                                    [Send Message]
```

**Urgent Flag**:
- Bypasses notification batching
- Different visual treatment in hub
- Limited use encouraged (social friction)

### 2.4 Search & History

**Search Scope**:
- Message content
- File names mentioned
- Event descriptions

**Filters**:
- Date range
- Sender (producer/client)
- Event type

**Results Display**:
- Highlighted matches
- Jump to message in context

### 2.5 Export Capability

**Export Formats**:
- PDF (formatted conversation history)
- JSON (structured data)

**Included**:
- All messages
- File references
- Status changes
- Timestamps

**Use Cases**:
- Client records
- Dispute resolution
- Portfolio documentation

---

## Phase 3 Features

### 3.1 Work Sessions

**Producer Interface**:
```
┌─────────────────────────────────────────────────────────────────────┐
│ START WORK SESSION                                                  │
├─────────────────────────────────────────────────────────────────────┤
│ Project: Album Master Mix                                           │
│ ⏱ Session: 1h 23m                                                   │
│                                                                     │
│ [Pause] [End Session]                                               │
│                                                                     │
│ Session Notes (visible to client):                                  │
│ ┌─────────────────────────────────────────────────────────────────┐ │
│ │ Working on the chorus bass EQ and overall mix balance...        │ │
│ └─────────────────────────────────────────────────────────────────┘ │
│                                                                     │
│ ☑ Notify client when I upload files                                │
│ ☑ Show "working" status to client                                  │
└─────────────────────────────────────────────────────────────────────┘
```

**What Gets Tracked**:
- Start/end times
- Total duration
- Optional notes
- Associated project

**Client Visibility**:
- "Mike is working on your project"
- Session duration (if enabled)
- Session notes (if provided)

**Time Tracking Benefits**:
- Billing documentation
- Productivity insights
- Client transparency

### 3.2 Focus Mode

**Purpose**: Let producers work without interruption while maintaining client trust.

**When Active**:
- Messages from client held until session ends
- Client sees: "Mike is in a focus session, will respond soon"
- Producer sees: "2 messages waiting" (can preview)

**Urgent Override**:
- Client can mark message as urgent
- Requires confirmation ("Only for time-sensitive matters")
- Breaks through focus mode

**Settings**:
```
When in focus mode:
○ Hold all messages
● Show message count but don't notify
○ Allow messages but mute sounds
```

### 3.3 Presence Indicators

**Presence States**:
| State | Icon | Meaning |
|-------|------|---------|
| Online | ● | User has app open |
| In Project | ◐ | User is viewing this project |
| Away | ○ | No activity 5+ min |
| Offline | ◌ | Not connected |
| Working | 🎧 | In active work session |
| Focus | 🔕 | In focus mode |

**Producer Visibility Settings**:
```
When working on client projects, show:

○ Full presence
  Client sees: "Mike is working on your project now"

● Activity summary (Recommended)
  Client sees: "Mike was active today" / "Last worked: 2h ago"

○ Minimal presence
  Client sees: "Mike is handling your project"
```

### 3.4 Activity Timeline

**Purpose**: Non-intrusive transparency about work history.

**Display**:
```
PROJECT ACTIVITY

Today
├── 2:30 PM  Mike started a work session
├── 2:45 PM  Mike added session notes
└── (in progress...)

Yesterday
├── 4:00 PM  Mike uploaded Mix_V2.wav
├── 3:15 PM  Mike worked for 2h 30m
└── 1:00 PM  You sent feedback

Dec 3
├── 5:00 PM  Mike delivered initial files
└── 2:00 PM  Project started
```

**Benefits**:
- Shows progress without real-time tracking
- Builds trust through transparency
- Reduces "are they working on it?" anxiety

---

## Phase 4 Features

### 4.1 Real-Time Updates (WebSocket)

**What Becomes Real-Time**:
- New messages appear instantly
- Read receipts update live
- Presence changes reflected immediately
- Typing indicators (optional)

**Technical Approach**:
- Use Laravel Reverb (already configured)
- Private channel per project: `project.{id}`
- Events: `MessageSent`, `MessageRead`, `PresenceChanged`

**Graceful Degradation**:
- Falls back to polling if WebSocket fails
- Core functionality works without real-time

### 4.2 Typing Indicators (Optional)

**Display**:
```
Sarah is typing...
```

**Considerations**:
- Can feel intrusive for async workflows
- May encourage waiting vs. doing other things
- Should be subtle, not prominent

**Recommendation**: Make this a per-user setting, off by default.

---

## Communication State Machine

Integrate communication with workflow states:

```
FILES_DELIVERED ──────────────────────────────────────────────────────►
       │
       ▼
AWAITING_FEEDBACK ◄──── Client hasn't responded in 48h?
       │                 → Auto-nudge: "Have you had a chance..."
       │
       ▼
FEEDBACK_RECEIVED ────────────────────────────────────────────────────►
       │
       ▼
RESPONSE_NEEDED ◄───── Producer hasn't acknowledged in 24h?
       │                → Reminder to producer
       │
       ▼
REVISING ─────────────────────────────────────────────────────────────►
       │
       ▼
REVISION_READY ───────────────────────────────────────────────────────►
       │
       └──────► AWAITING_FEEDBACK (cycle)
```

**Benefits**:
- Clear expectations at each stage
- Automated reminders reduce manual follow-up
- Visual progress tracking

---

## Mobile Considerations

### Client Portal (Mobile-First)

Communication should be **prominent** for clients:
```
┌─────────────────────────┐
│ Your Project            │
│ Producer: Mike N.       │
│ ● Working on your files │
├─────────────────────────┤
│ ┌─────────────────────┐ │
│ │ 💬 1 new message    │ │  ← Primary action area
│ │ from Mike           │ │
│ │ [View & Reply]      │ │
│ └─────────────────────┘ │
├─────────────────────────┤
│ 📁 Files (3)           │
│ Status: Ready for Review│
│ [Approve] [Request Rev] │
└─────────────────────────┘
```

### Producer Dashboard (Desktop-Primary)

Hub as side panel or overlay, not full-screen takeover.

### Touch Interactions

- Swipe to dismiss hub
- Long-press for message options
- Pull to refresh
