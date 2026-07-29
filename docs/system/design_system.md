# 🎨 LANDINGFLOW DESIGN SYSTEM

## Version
v1.0 — Unified SaaS UI Standard (Strict Enforcement)

---

# 🎯 PURPOSE

This document defines the single source of truth for all UI in LandingFlow.

It MUST be followed by

- Admin Dashboard
- Public Website
- Scanner Reports
- Auth Pages
- CRM  Leads System
- API-rendered Views
- All future UI modules

Any deviation is considered a violation.

---

# 🧠 CORE DESIGN PRINCIPLES

The UI must always be

- Clean and minimal
- SaaS-oriented (analytics platform style)
- Data-first (not decorative)
- Consistent across all modules
- Fully responsive
- Predictable and reusable

---

# 🎨 COLOR SYSTEM

## Primary Colors

- Primary #2563EB
- Primary Hover #1E40AF
- Success #16A34A
- Warning #F59E0B
- Danger #DC2626
- Info #0EA5E9

---

## Neutral Colors

- Background #F9FAFB
- Surface #FFFFFF
- Border #E5E7EB
- Text Primary #111827
- Text Secondary #6B7280
- Muted #9CA3AF

---

## SCORE SYSTEM COLORS (CRITICAL)

Used in all scanner outputs and dashboards

- 80–100 → #16A34A (Green)
- 60–79 → #F59E0B (Amber)
- 40–59 → #F97316 (Orange)
- 0–39 → #DC2626 (Red)

---

# ✍️ TYPOGRAPHY SYSTEM

## Font Family

Inter, system-ui, -apple-system, sans-serif

---

## Type Scale

- H1 → 32px  700
- H2 → 24px  600
- H3 → 20px  600
- Body → 14–16px  400
- Small → 12px  400
- Label → 12px  500

---

## Typography Rules

- Max 2 font weights per page
- No mixed font families
- No unnecessary uppercase text
- Maintain clear hierarchy always

---

# 📏 SPACING SYSTEM (STRICT)

## Base Grid

8px system ONLY

---

## Spacing Tokens

- xs → 4px
- sm → 8px
- md → 16px
- lg → 24px
- xl → 32px
- 2xl → 48px
- 3xl → 64px

---

## Rules

- NO arbitrary spacing allowed
- NO custom pixel values
- ALL marginspadding must use tokens

---

# 🧱 LAYOUT SYSTEM

## Page Structure

Header (fixed)
Sidebar (dashboard only)
Main Content
Footer (public pages only)

---

## Grid System

- Desktop 12-column grid
- Dashboard max 4 columns per row
- Reports single-column focus layout

---

## Container Widths

- Dashboard 1200–1400px
- Public Pages 1100px
- Full Analytics Pages 100%

---

# 🔘 BUTTON SYSTEM

## Types

### Primary Button
- Background #2563EB
- Text #FFFFFF
- Use main actions (scan, submit, save)

### Secondary Button
- Background #FFFFFF
- Border #E5E7EB
- Use secondary actions

### Danger Button
- Background #DC2626
- Use destructive actions

---

## Button Rules

- Height 40px minimum
- Border radius 8px
- Must include hover state
- Must include disabled state
- No custom button styles allowed

---

# 🧩 COMPONENT SYSTEM

## Cards

Used for
- KPI metrics
- Reports
- Scanner results

Rules
- Background #FFFFFF
- Border #E5E7EB
- Soft shadow only
- Padding 16–24px
- Radius 8px

---

## Badges

Used for
- Scores
- Status indicators
- Labels

Types
- Success (green)
- Warning (amber)
- Danger (red)
- Info (blue)

---

## Tables

Rules
- Minimal design
- Light row separators only
- Hover row highlight
- Sticky headers in dashboards
- No heavy borders

---

# 📊 DASHBOARD DESIGN SYSTEM

## Philosophy

“Analytics SaaS platform, not CMS”

---

## Layout

- Left sidebar navigation
- KPI summary row on top
- Metrics cards grid
- Reports table section

---

## KPI Cards

Each KPI must include

- Title
- Value  Score
- Color indicator
- Optional trend indicator

---

## Scanner Reports UI

Must always include

- Overall Score (large)
- SEO  LLMO  AEO  GEO breakdown
- Issues list
- Recommendations list
- Priority fixes

---

# 📱 RESPONSIVE DESIGN

## Mobile Rules

- Sidebar becomes drawer
- Cards stack vertically
- Tables become scrollable
- Buttons become full width in forms

---

# ⚡ UX RULES

- Always show loading states
- Always show successerror feedback
- No silent failures allowed
- Navigation must be consistent
- Scan progress must be visible
- UI must behave predictably

---

# 🚫 FORBIDDEN UI PATTERNS

- Random colors outside palette
- Inline CSS overrides
- Arbitrary spacing values
- Multiple conflicting button styles
- Inconsistent card designs
- Over-decorated UI (gradients, neon, heavy shadows)

---

# 🤖 AGENT ENFORCEMENT RULES

Any system generating UI MUST

- Follow this design system strictly
- Never invent new colors
- Never create new spacing rules
- Never modify backend logic
- Only modify view layer (HTMLCSS)
- Reuse existing components only

---

# 🎯 FINAL GOAL

LandingFlow UI must behave like

“A production-grade SaaS analytics platform with strict design governance, consistency, and predictable UX.”

---

# 📌 RESULT

This system ensures

- Full UI consistency
- Scalable design architecture
- Agent-safe UI generation
- Predictable user experience
- Zero UI fragmentation over time

# 🤖 DESIGN SYSTEM ENFORCEMENT PROTOCOL

## RULE 1 — MANDATORY CHECK

Before generating ANY UI (HTML, PHP views, dashboard pages, forms):

The agent MUST:

1. Load DESIGN_SYSTEM.md
2. Validate all UI decisions against it
3. Use only approved tokens, colors, spacing, components

If this step is skipped → output is INVALID.

---

## RULE 2 — HIERARCHY OF TRUTH

If conflict exists between:

- User request
- Existing UI code
- Design System

👉 DESIGN_SYSTEM.md ALWAYS WINS

No exceptions.

---

## RULE 3 — COMPONENT REUSE ONLY

Agents are NOT allowed to invent new UI patterns.

Allowed:

- Button (Primary / Secondary / Danger)
- Card
- Badge
- Table
- Form input
- KPI block

Forbidden:

- New button styles
- New spacing systems
- New color palettes
- Custom UI frameworks inside views

---

## RULE 4 — UI VALIDATION GATE

After ANY UI is generated, it MUST be verified against:

ui_testing_agent.md

Validation must confirm:

- Colors match system
- Spacing uses tokens
- Buttons follow rules
- Layout matches SaaS structure
- No forbidden patterns exist

If validation fails → UI is rejected.

---

## RULE 5 — NO DESIGN IN BACKEND

UI rules apply ONLY to:

- app/views/*
- frontend-rendered HTML
- dashboard templates

Backend logic MUST NOT contain styling logic.

---

## RULE 6 — CONSISTENCY FIRST

Every page MUST look like part of the same SaaS system.

Even if functionality differs, UI must feel identical in:

- spacing
- typography
- cards
- buttons
- layout rhythm

---

## RULE 7 — FINAL APPROVAL REQUIREMENT

No UI module is considered COMPLETE until:

- Design System compliance passed
- UI Testing Agent passed
- Navigation verified
- No style deviations detected