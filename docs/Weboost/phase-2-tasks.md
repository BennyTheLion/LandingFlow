# Phase 2 — Task Breakdown & Agents

Status: Draft
Owner: Benny Maimon
Last updated: 2025-01-20

---

> **Iron rule:** Phase 1 (`weboost/`) is read-only. No file in `weboost/` is created, edited, or deleted.
> **Work directory:** `weboost-phase2/` — isolated from Phase 1.

---

## Agents

| Agent | Role | Owns |
|---|---|---|
| **Config Agent** | Phase 2 config + directory setup | `config.php`, directory tree |
| **Shell Agent** | Page template shell + navigation | `templates/page.php`, `components/navbar.php` |
| **Page Agent** | Individual page templates | `pages/*.php` |
| **Copy Agent** | AI + placeholder copy for Phase 2 pages | `engine/copy-generator.php` |
| **Form Agent** | Contact form HTML + PHP handler | `components/contact-form.php`, `engine/form-handler.php` |
| **Blog Agent** | Blog pages + RSS | `pages/blog-index.php`, `pages/blog-post.php`, `blog/rss.xml` |
| **Schema Agent** | JSON-LD schema markup per page | `components/schema-org.php`, `rules/schema-rules.php` |
| **Asset Agent** | robots.txt, sitemap, manifest, favicon, .htaccess | `engine/asset-builder.php` |
| **A11y Agent** | Accessibility rules + ARIA + skip-link | `rules/accessibility-rules.php` |
| **Orchestrator Agent** | Multi-page generation pipeline | `generate.php` |
| **QA Agent** | End-to-end validation | Validates, does not write code |

---

## Task Sequence

---

### T22 — Directory Setup

| Field | Value |
|---|---|
| **Agent** | Config Agent |
| **Input** | Nothing |
| **Output** | All subdirectories under `weboost-phase2/`:<br>`engine/`, `pages/`, `components/`, `templates/`, `rules/`, `prompts/`, `output/`, `blog/` |
| **Validation** | All 8 directories exist<br>`output/` is writable |

### T23 — Phase 2 Config

| Field | Value |
|---|---|
| **Agent** | Config Agent |
| **Input** | T22 |
| **Output** | `config.php` with:<br>AI settings (DeepSeek), image settings (Unsplash), generation timeout, output dir,<br>NEW: SMTP settings, site base URL, multi-page flag, language/direction |
| **Validation** | `require config.php` returns array with all keys<br>SMTP keys present<br>`multi_page` flag exists |

### T24 — Page Shell Template

| Field | Value |
|---|---|
| **Agent** | Shell Agent |
| **Input** | T23 |
| **Output** | `templates/page.php` — full HTML shell with:<br>DOCTYPE, RTL, Heebo font, Tailwind CDN, meta tags, OG tags,<br>NEW: JSON-LD slot, skip-to-content link, multi-page aware |
| **Validation** | Renders complete valid HTML<br>Has `<html lang="he" dir="rtl">`<br>Has skip-to-content<br>Has JSON-LD placeholder |

### T25 — Navigation Component (Multi-Page)

| Field | Value |
|---|---|
| **Agent** | Shell Agent |
| **Input** | T24 |
| **Output** | `components/navbar.php` — multi-page navbar with:<br>Links to: index.html, about.html, services.html, contact.html, blog/index.html<br>Active page highlighting<br>Mobile hamburger |
| **Validation** | Renders all 5 page links<br>Active class applied to current page<br>Mobile menu works |

### T26 — Copy Generator (Phase 2 Extended)

| Field | Value |
|---|---|
| **Agent** | Copy Agent |
| **Input** | T23, Phase 1 copy-generator.php (read-only reference) |
| **Output** | `engine/copy-generator.php` — extended placeholder copy:<br>About page blurb, services list (6-8), blog posts (3), contact info<br>AI path: DeepSeek API with JSON mode |
| **Validation** | AI_ENABLED=false returns all page data<br>AI_ENABLED=true calls DeepSeek and parses JSON<br>Both paths return identical shape<br>Blog posts have title/date/excerpt/body |

### T27 — About Page

| Field | Value |
|---|---|
| **Agent** | Page Agent |
| **Input** | T24, T25, T26 |
| **Output** | `pages/about.php` — full about page:<br>Hero heading, mission statement, values grid (4 items), team section placeholder |
| **Validation** | Renders complete page via shell<br>Has h1, mission text, 4 values |

### T28 — Services Page

| Field | Value |
|---|---|
| **Agent** | Page Agent |
| **Input** | T24, T25, T26 |
| **Output** | `pages/services.php` — detailed services page:<br>Service cards with icons, descriptions, CTA to contact |
| **Validation** | Renders 6-8 service cards<br>Each has icon/title/desc<br>Has CTA link to contact |

### T29 — Contact Page Template

| Field | Value |
|---|---|
| **Agent** | Page Agent |
| **Input** | T24, T25, T26 |
| **Output** | `pages/contact.php` — contact page structure:<br>Heading, intro text, contact info sidebar (phone/email/address), form slot |
| **Validation** | Renders complete page<br>Has contact info section<br>Has form placeholder area |

### T30 — Contact Form Component

| Field | Value |
|---|---|
| **Agent** | Form Agent |
| **Input** | T29 |
| **Output** | `components/contact-form.php` — form HTML:<br>Fields: name, phone, email, message<br>Honeypot anti-spam field<br>Client-side validation (required, email format)<br>Success/error message areas |
| **Validation** | All 4 fields render<br>Honeypot hidden via CSS<br>Required validation fires<br>Email format check works |

### T31 — Form Handler

| Field | Value |
|---|---|
| **Agent** | Form Agent |
| **Input** | T30, T23 (SMTP config) |
| **Output** | `engine/form-handler.php` — PHP form processor:<br>Honeypot check, required validation, email sanitization,<br>PHP `mail()` or SMTP send, JSON response |
| **Validation** | Honeypot-filled submissions rejected<br>Missing required fields rejected<br>Valid submission returns success JSON<br>Email sent to configured address |

### T32 — Blog Index Page

| Field | Value |
|---|---|
| **Agent** | Blog Agent |
| **Input** | T24, T25, T26 |
| **Output** | `pages/blog-index.php` — blog listing:<br>Hero heading, 3 post cards (image, title, date, excerpt, read-more link) |
| **Validation** | Renders 3 post cards<br>Each has link to post page<br>Dates formatted in Hebrew |

### T33 — Blog Post Template

| Field | Value |
|---|---|
| **Agent** | Blog Agent |
| **Input** | T24, T25, T26 |
| **Output** | `pages/blog-post.php` — single post template:<br>Title, date, author, featured image, body content, back-to-blog link |
| **Validation** | Renders full post<br>Has back link to blog index<br>Has proper article semantics |

### T34 — Blog Post Generation

| Field | Value |
|---|---|
| **Agent** | Blog Agent |
| **Input** | T32, T33, T26 |
| **Output** | `output/{slug}/blog/` with 3 post HTML files generated<br>Blog index links to actual posts |
| **Validation** | 3 post files exist in output<br>Index links work<br>Post URLs are correct |

### T35 — Schema Markup Rules

| Field | Value |
|---|---|
| **Agent** | Schema Agent |
| **Input** | T23 |
| **Output** | `rules/schema-rules.php` — maps industry → schema type<br>Supported: Organization, LocalBusiness, Restaurant, ProfessionalService, RealEstateAgent, AutoDealer, etc. |
| **Validation** | Each industry maps to correct schema type<br>Default fallback exists |

### T36 — Schema Markup Component

| Field | Value |
|---|---|
| **Agent** | Schema Agent |
| **Input** | T35, T26 |
| **Output** | `components/schema-org.php` — generates JSON-LD `<script>` block:<br>Organization schema on all pages<br>LocalBusiness on contact page<br>Article on blog posts<br>BreadcrumbList on all pages<br>WebSite on homepage |
| **Validation** | Output is valid JSON<br>No syntax errors<br>Schema types match page context |

### T37 — Accessibility Improvements

| Field | Value |
|---|---|
| **Agent** | A11y Agent |
| **Input** | T24 (page shell) |
| **Output** | `rules/accessibility-rules.php`<br>Added to shell: skip-to-content link, ARIA landmarks (nav, main, footer), semantic HTML, focus-visible styles |
| **Validation** | Skip link visible on focus<br>All sections have ARIA role<br>Focus styles visible<br>alt text on all images |

### T38 — robots.txt + sitemap.xml

| Field | Value |
|---|---|
| **Agent** | Asset Agent |
| **Input** | T23 |
| **Output** | `engine/asset-builder.php` with robots.txt + sitemap.xml generation |
| **Validation** | robots.txt is valid<br>sitemap.xml is valid XML<br>Both include correct URLs |

### T39 — manifest.json + favicon + .htaccess

| Field | Value |
|---|---|
| **Agent** | Asset Agent |
| **Input** | T23, T26 |
| **Output** | manifest.json (PWA-ready), favicon placeholder, .htaccess with caching + redirects |
| **Validation** | manifest.json is valid JSON<br>.htaccess has caching rules<br>Favicon referenced in HTML |

### T40 — Multi-Page Orchestrator

| Field | Value |
|---|---|
| **Agent** | Orchestrator Agent |
| **Input** | T22-T39 |
| **Output** | `generate.php` — multi-page pipeline:<br>1. Generate copy (AI or placeholder)<br>2. Render all 6 page types<br>3. Generate blog posts<br>4. Inject schema into each page<br>5. Build assets (robots,sitemap,manifest,htaccess)<br>6. Save full site to output/{slug}/ |
| **Validation** | One POST → complete site directory<br>All pages link to each other<br>Schema present on every page<br>Assets generated |

### T41 — End-to-End QA

| Field | Value |
|---|---|
| **Agent** | QA Agent |
| **Input** | Complete system (T22-T40) |
| **Output** | QA report: all pages accessible, forms work, schema validates, a11y passes, Phase 1 untouched |
| **Validation** | Full site generated in under 5s (AI disabled)<br>6 pages + 3 blog posts = 9 HTML files<br>All internal links work<br>Contact form processes<br>Schema validates in Google tool<br>Phase 1 `weboost/` directory unchanged |

---

## Dependency Graph

```
T22 (dirs) → T23 (config)
                │
     ┌──────────┼──────────┐
     │          │          │
  T24 (shell) T26 (copy) T35 (schema)
     │          │          │
  T25 (nav)     │       T36 (schema comp)
     │          │
  ┌──┼──┬──┬───┤
  │  │  │  │   │
 T27 T28 T29 T32 (blog idx)
  │  │  │   │   │
  │  │  T30 │  T33 (blog post)
  │  │  │   │   │
  │  │  T31 │  T34 (blog gen)
  │  │      │
  └──┴──────┴── T37 (a11y) → T38 (robots/sitemap) → T39 (manifest/htaccess)
                       │
                    T40 (orchestrator)
                       │
                    T41 (QA)
```

---

## Agent Assignment Summary

| Agent | Tasks |
|---|---|
| Config Agent | T22, T23 |
| Shell Agent | T24, T25 |
| Page Agent | T27, T28, T29 |
| Copy Agent | T26 |
| Form Agent | T30, T31 |
| Blog Agent | T32, T33, T34 |
| Schema Agent | T35, T36 |
| A11y Agent | T37 |
| Asset Agent | T38, T39 |
| Orchestrator Agent | T40 |
| QA Agent | T41 |
