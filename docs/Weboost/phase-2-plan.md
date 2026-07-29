# Phase 2 — AI Website Builder · Build Plan

Status: Draft
Owner: Benny Maimon
Last updated: 2025-01-20

---

## Objective

Extend the Phase 1 demo generator into a complete multi-page website builder. A single generation run now produces a full site — not just a homepage — with blog, contact forms, schema markup, and production-ready assets.

---

## Scope

| Deliverable | Details |
|---|---|
| **Multi-page engine** | Home, About, Services, Contact, Blog index, Blog post |
| **Schema markup** | JSON-LD: Organization, LocalBusiness, Article, BreadcrumbList, WebSite |
| **Accessibility** | ARIA labels, skip-to-content, semantic landmarks, focus management |
| **Performance** | Image srcset, lazy loading, resource hints, minified output |
| **Blog engine** | Blog index with pagination, individual post pages, RSS feed |
| **Contact forms** | Working PHP form handler, validation, honeypot anti-spam, email |
| **Production assets** | Favicon set, web manifest, robots.txt, sitemap.xml |

---

## Architecture

```
User Input (business name, industry, tone, goal)
        │
        ▼
┌──────────────────────────────────────────┐
│           generate.php                    │
│        (Phase 2 Orchestrator)             │
├──────────────────────────────────────────┤
│  1. Generate all page types               │
│     ├── Homepage (Phase 1 pipeline)       │
│     ├── About page                        │
│     ├── Services page                     │
│     ├── Contact page (with form)          │
│     ├── Blog index (3 posts)             │
│     └── Blog post template                │
│  2. Generate schema markup (JSON-LD)      │
│  3. Build navigation (all pages)          │
│  4. Generate production assets            │
│     ├── robots.txt, sitemap.xml           │
│     ├── manifest.json, favicon            │
│     └── .htaccess                         │
│  5. Quality check all pages               │
│  6. Save to /output/demo-{slug}/          │
└──────────────────────────────────────────┘
```

### Output Structure

```
output/demo-{slug}/
├── index.html            ← Homepage (Phase 1 pipeline)
├── about.html            ← About page
├── services.html         ← Services page
├── contact.html          ← Contact page with form
├── blog/
│   ├── index.html        ← Blog listing
│   └── post-1.html       ← Sample blog post
├── robots.txt
├── sitemap.xml
├── manifest.json
├── favicon.ico
├── assets/
│   └── (images, icons)
└── .htaccess
```

---

## File Structure (Phase 2)

```
weboost-phase2/
├── config.php                  ← Extended config (SMTP, site URL, etc.)
├── generate.php                ← Multi-page orchestrator
│
├── engine/
│   ├── page-generator.php      ← Generates one page type from template
│   ├── schema-builder.php      ← JSON-LD schema markup generator
│   ├── asset-builder.php       ← robots.txt, sitemap.xml, manifest
│   └── form-handler.php        ← Contact form processing
│
├── pages/                      ← NEW: page-type templates
│   ├── about.php               ← About page template
│   ├── services.php            ← Services listing page
│   ├── contact.php             ← Contact form page
│   ├── blog-index.php          ← Blog listing page
│   └── blog-post.php           ← Single blog post
│
├── components/                 ← Reused from Phase 1 + new
│   ├── navbar.php              ← Updated: links to all pages
│   ├── schema-org.php          ← JSON-LD output
│   └── contact-form.php        ← Form HTML + validation
│
├── rules/                      ← New rules
│   ├── accessibility-rules.php ← ARIA, landmarks, contrast checks
│   └── schema-rules.php        ← Schema type mapping per industry
│
├── templates/
│   └── page.php                ← Updated: multi-page aware shell
│
└── output/                     ← Full generated sites
```

---

## Build Order (Milestones)

### M1 — Multi-Page Engine
Build the core generator that creates multiple HTML pages in one run. Each page type has its own template in `pages/`. The navbar updates to link between pages.

### M2 — Schema Markup
Build JSON-LD schema generator. Organization, LocalBusiness, Article, BreadcrumbList, WebSite. Injected into `<head>` of every page.

### M3 — Blog Engine
Blog index page with 3 AI-generated posts. Individual post pages. RSS feed. Basic pagination styling.

### M4 — Contact Form + Email
Working contact form with validation. Honeypot anti-spam. PHP mail() or SMTP integration. Success/error states.

### M5 — Accessibility + Performance
ARIA labels, semantic landmarks, skip-to-content link. Image srcset generation. Resource hints (preconnect, prefetch). Minified HTML output.

### M6 — Production Assets
robots.txt, sitemap.xml, manifest.json, favicon generation, .htaccess with caching rules.

### M7 — Integration + QA
Wire all pieces into generate.php. End-to-end test. Quality checklist for every page type.

---

## Key Innovations Over Phase 1

| Area | Phase 1 | Phase 2 |
|---|---|---|
| Pages | 1 homepage | 6+ pages |
| Navigation | Anchor links (#) | Multi-page links |
| Schema | None | JSON-LD on every page |
| Forms | None | Working contact form |
| Blog | None | 3 posts + RSS |
| Accessibility | Basic | ARIA, landmarks, skip-link |
| Performance | CDN Tailwind | Image srcset, hints, minification |
| Output | Single HTML | Full site directory |
