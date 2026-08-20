# LandingFlow — Business Summary

## What it is

LandingFlow is an Israeli, Hebrew-first technology business that provides
landing pages, website development, AI automations, 24/7 uptime monitoring,
and CRM/lead management — delivered personally by **one person**, not an
agency. The entire brand is built around that distinction: a direct
relationship with a single technologist, not a company with layers of
account managers and support tickets.

- Domain: landingflow.co.il
- Contact: 052-8529448 · info@landingflow.co.il
- Social: LinkedIn, Instagram, Facebook
- Language/market: Hebrew, RTL, Israeli small/medium businesses

## Core positioning

The tagline across the site: **"פתרונות טכנולוגיים לעסק שלך, בליווי אישי
צמוד"** ("Technology solutions for your business, with close personal
guidance"). Every piece of marketing copy reinforces the same six reasons
to work with one person instead of a company:

1. **קשר ישיר** (Direct contact) — you talk directly to the person building
   your solution, no intermediaries or call-center reps.
2. **החלטות מהירות** (Fast decisions) — no bureaucracy or management
   approvals; changes happen immediately.
3. **מחיר הוגן** (Fair pricing) — no office/staff overhead, you pay for the
   work only.
4. **מחויבות מלאה** (Full commitment) — your project stays with the same
   person start to finish.
5. **גמישות מקסימלית** (Maximum flexibility) — small changes, urgent
   additions, new ideas, no rigid contracts.
6. **זמינות אמיתית** (Real availability) — a direct WhatsApp line, not a
   support ticket queue.

That last point — **direct WhatsApp contact** — is the single most repeated
differentiator in the brand's own copy, and is the reason the site's accent
color (a WhatsApp-adjacent green) was chosen during the visual redesign: it
represents "always reachable, always live."

## Products / services

| Service | What it covers |
|---|---|
| **Landing pages** (דפי נחיתה) | Fast-converting pages, smart forms, A/B testing, analytics |
| **Website development** (פיתוח אתרים) | Business sites, online stores, custom systems — clean code, built-in SEO |
| **AI automations** (אוטומציות AI) | Chatbots, automatic lead response, smart integrations |
| **24/7 monitoring** (ניטור 24/7) | Uptime checks every 60 seconds from multiple locations, instant WhatsApp alerts |
| **Free website audit** (בדיקת אתר חינם) | Automated report scoring SEO, security, accessibility, performance, and legal/compliance requirements — the site's main lead-generation tool |
| **CRM & leads** (CRM ולידים) | Every inquiry captured automatically, with routing and sales tracking |
| **AI demo generator** | Instant AI-built landing-page prototype from a short questionnaire — the "צור אתר דמו עכשיו" CTA |
| **Receipts** | Admin-side receipt generation/sending to customers |

Illustrative pricing tiers referenced in the site's own chat assistant copy:
Starter ₪199/mo (landing page + hosting), Business ₪499/mo (3 pages +
monitoring + CRM), Premium ₪999/mo (everything + VIP support).

## How the product actually works (what's built)

- **Public marketing site**: home, about, pricing, portfolio, services,
  blog, contact, legal pages.
- **Free audit tool** (`/audit`): visitor enters a URL + email (verified via
  a 6-digit code sent by email), gets a scored report across SEO, security,
  legal/compliance, accessibility, performance, and spam/trust signals —
  each category built from real checks (HTTPS, security headers, alt text,
  robots.txt/sitemap, response time, etc.), not placeholder content.
- **Demo generator** (`/demo`): a short conversational flow that produces an
  AI-generated landing-page prototype for the visitor's business, capturing
  their contact info as a lead in the process.
- **Admin dashboard**: leads/CRM, hosting accounts, monitoring targets,
  audit report history, receipts — gated behind login, one owner account.
- **Monitoring**: tracked websites checked periodically, with an
  admin-facing dashboard and alerting.

## Technical context (for anyone picking this up)

- Backend: a custom PHP MVC-style framework (no external framework),
  MySQL/MariaDB, PHPMailer for email, TCPDF for receipt PDFs.
- Hosted on Hostinger shared hosting; local development runs on XAMPP.
- The visual identity was reworked around a warm ink/paper palette with a
  single signature accent green (`#22C55E`/`#1FAA6D` family) tied directly
  to the "always reachable" WhatsApp positioning, paired with Frank Ruhl
  Libre (headlines) and Heebo (body) for a Hebrew-first typographic voice
  distinct from generic template defaults.
- A separate experimental build (`landing-3d/`, React + Vite + React Three
  Fiber) explores a 3D hero section whose visual layers map directly to the
  three product pillars — live monitoring (pulse/uptime), site audits
  (score gauge), and website building (animated code + page wireframe) —
  merging periodically into one unified moment as a visual metaphor for the
  business's core pitch: everything, from one person.
