# Phase 1 — Task Breakdown & Agents

Status: Draft
Owner: Benny Maimon
Last updated: 2025-01-20

---

> **How to use this:** Agents execute tasks in order (T1 → T21). Each task is independently measurable — you know exactly when it's done. No task starts until the previous task passes validation.

---

## Agents

| Agent | Role | Owns | Does NOT |
|---|---|---|---|
| **Config Agent** | Environment & settings | `config.php`, `.env` example, directory creation | Write business logic or HTML |
| **Rules Agent** | Design tokens & validation | `rules/design-rules.php`, `rules/seo-rules.php`, `rules/ux-rules.php`, `rules/conversion-rules.php`, `rules/quality-checklist.php` | Call APIs or render HTML |
| **Component Agent** | HTML partials | `components/*.php` | Call APIs, read config, make layout decisions |
| **Template Agent** | Full-page assembly | `templates/page.php` | Call APIs, write business logic |
| **Engine Agent** | Business logic & API integration | `engine/copy-generator.php`, `engine/image-fetcher.php`, `engine/layout-resolver.php`, `prompts/copy-system.txt` | Render HTML or handle user input |
| **UI Agent** | Chat interface & frontend | `index.php` (chat UI, JS, iframe, Contact Now button) | Call AI APIs from the frontend or generate pages |
| **Orchestrator Agent** | Pipeline wiring | `generate.php` | Build components or call APIs directly |
| **QA Agent** | Validation & testing | Validates every task's output before sign-off | Write production code |

---

## Task Sequence

Tasks are strictly sequential. Each task:

- Has one assigned agent
- Has a clear **input** (what must exist before starting)
- Has a clear **output** (files created or modified)
- Has a **validation** checklist (pass/fail gates)

---

### T1 — Directory Scaffolding & Config

| Field | Value |
|---|---|
| **Agent** | Config Agent |
| **Input** | `vision.md`, `phase-1-plan.md`, `coding-instructions.md` |
| **Output** | Full directory tree created under `C:\xampp\htdocs\landingflow\`:<br>`engine/`, `components/`, `templates/`, `rules/`, `prompts/`, `assets/placeholder/`, `output/`<br>`config.php` with all keys populated (DeepSeek free tier, AI_ENABLED=true, Unsplash keys as env vars) |
| **Validation** | `config.php` returns array with all 13 required keys<br>All 7 directories exist<br>`output/` is writable |

---

### T2 — Design Rules

| Field | Value |
|---|---|
| **Agent** | Rules Agent |
| **Input** | `config.php` (T1) |
| **Output** | `rules/design-rules.php` — returns array with:<br>Color palette: primary, secondary, accent, neutral, background, text (hex values)<br>Typography: font stack, heading sizes (h1→h4), body size, line-height<br>Spacing scale: xs/sm/md/lg/xl/2xl (rem values)<br>Border radius, shadow levels, max content width |
| **Validation** | `require 'rules/design-rules.php'` returns an array<br>At least 6 colors defined as valid hex<br>At least 4 heading sizes in rem<br>At least 4 spacing values in rem |

---

### T3 — Placeholder Assets

| Field | Value |
|---|---|
| **Agent** | Config Agent |
| **Input** | Design rules (T2) |
| **Output** | `assets/placeholder/hero-bg.jpg` — 1200×800 solid-color image matching design-rules primary color<br>`assets/placeholder/feature-icon.svg` — generic icon SVG |
| **Validation** | Both files exist and are > 0 bytes<br>`hero-bg.jpg` is a valid JPEG<br>`feature-icon.svg` is valid XML |

---

### T4 — Hero Component

| Field | Value |
|---|---|
| **Agent** | Component Agent |
| **Input** | Design rules (T2), Placeholder assets (T3) |
| **Output** | `components/hero.php` with function `render_hero(array $data): string`<br>Slots: `headline`, `subtext`, `cta_primary_text`, `cta_secondary_text`, `hero_image_url`<br>Enforces: one `<h1>`, CTA contrast ≥ 4.5:1, image `loading="eager"`, all output escaped |
| **Validation** | `render_hero()` with dummy data returns non-empty HTML string<br>Output contains exactly one `<h1>`<br>Output contains `loading="eager"`<br>Missing slot keys don't crash (fallback to defaults)<br>`htmlspecialchars` used on all dynamic text |

---

### T5 — Features Component

| Field | Value |
|---|---|
| **Agent** | Component Agent |
| **Input** | Design rules (T2) |
| **Output** | `components/features.php` with function `render_features(array $data): string`<br>`$data['features']` is array of `{icon_url, title, description}`<br>Renders 3–6 cards in a responsive grid |
| **Validation** | Handles 3 cards, handles 6 cards<br>Grid is responsive (Tailwind grid classes present)<br>Empty features array shows nothing (no crash)<br>All text escaped |

---

### T6 — Testimonials Component

| Field | Value |
|---|---|
| **Agent** | Component Agent |
| **Input** | Design rules (T2) |
| **Output** | `components/testimonials.php` with function `render_testimonials(array $data): string`<br>`$data['testimonials']` is array of `{quote, name, role, company}`<br>Renders 3 cards in a row |
| **Validation** | Handles 3 testimonials with all fields<br>Handles missing `company` field gracefully<br>Quotes use blockquote or similar semantic element<br>All text escaped |

---

### T7 — Pricing Component

| Field | Value |
|---|---|
| **Agent** | Component Agent |
| **Input** | Design rules (T2) |
| **Output** | `components/pricing.php` with function `render_pricing(array $data): string`<br>`$data['plans']` is array of `{name, price, period, features, recommended, cta_text}`<br>Highlights the `recommended` plan visually |
| **Validation** | 2–4 plans render correctly<br>Recommended plan has distinct styling (e.g. border, badge)<br>Missing `recommended` key defaults to false<br>All text escaped |

---

### T8 — CTA Banner Component

| Field | Value |
|---|---|
| **Agent** | Component Agent |
| **Input** | Design rules (T2) |
| **Output** | `components/cta.php` with function `render_cta(array $data): string`<br>Full-width banner: headline, subtext, single button<br>Distinct background color from page |
| **Validation** | Output contains one `<a>` or `<button>`<br>Has a background class (Tailwind bg-*)<br>All text escaped |

---

### T9 — Footer Component

| Field | Value |
|---|---|
| **Agent** | Component Agent |
| **Input** | Design rules (T2) |
| **Output** | `components/footer.php` with function `render_footer(array $data): string`<br>Business name, about blurb, placeholder links (About, Services, Contact), copyright with current year |
| **Validation** | Output contains current year via `date('Y')`<br>Contains at least 3 placeholder links<br>All text escaped |

---

### T10 — Page Template (Full HTML Shell)

| Field | Value |
|---|---|
| **Agent** | Template Agent |
| **Input** | All 6 components (T4–T9), Design rules (T2) |
| **Output** | `templates/page.php` with function `render_page(array $components, array $seo, array $design): string`<br>Full `<!DOCTYPE html>` → `</html>`<br>Includes Tailwind CDN<br>Includes all components in the order specified by `$components`<br>Injects SEO meta tags from `$seo` array<br>Applies design tokens from rules |
| **Validation** | Output passes basic HTML validity (has doctype, html, head, body)<br>Can render a page with only hero + footer (partial component set)<br>Can render all 6 components<br>`<title>` and `<meta name="description">` are present when provided |

---

### T11 — SEO Rules

| Field | Value |
|---|---|
| **Agent** | Rules Agent |
| **Input** | Design rules (T2) |
| **Output** | `rules/seo-rules.php` with function `build_seo_meta(array $input): array`<br>Input: business_name, industry, keywords<br>Output: title (30–60 chars), description (120–160 chars), og_title, og_description, og_image<br>Also: `get_default_seo(): array` for placeholder mode |
| **Validation** | Title is always 30–60 chars (truncated if needed)<br>Description is always 120–160 chars<br>Returns valid array even with empty input<br>OG fields mirror title/description when no image provided |

---

### T12 — Conversion Rules

| Field | Value |
|---|---|
| **Agent** | Rules Agent |
| **Input** | Design rules (T2) |
| **Output** | `rules/conversion-rules.php` with:<br>`get_mandatory_elements(string $industry): array` — returns list of required components for each industry<br>`validate_cta_count(string $html): bool` — checks at least one CTA exists<br>`validate_above_fold(string $html): bool` — checks hero section exists before any other component |
| **Validation** | SaaS returns pricing as mandatory<br>Default returns testimonials as mandatory<br>CTA count validator detects zero CTA as false<br>Above-fold validator detects hero-first layout |

---

### T13 — UX Rules

| Field | Value |
|---|---|
| **Agent** | Rules Agent |
| **Input** | Design rules (T2), Conversion rules (T12) |
| **Output** | `rules/ux-rules.php` with:<br>`get_component_constraints(string $component): array` — max word counts per slot<br>`validate_responsive(string $html): array` — checks for viewport meta, max-width container |
| **Validation** | Hero headline constraint: max 12 words<br>Feature title constraint: max 5 words<br>Viewport meta validator returns true for valid HTML<br>Max-width container check detects Tailwind `max-w-*` class |

---

### T14 — Quality Checklist

| Field | Value |
|---|---|
| **Agent** | Rules Agent |
| **Input** | All rules (T2, T11, T12, T13) |
| **Output** | `rules/quality-checklist.php` with function `run_quality_checks(string $html, string $page_url): array`<br>Returns `{passed: bool, checks: [{name, passed, message}]}`<br>11 checks: no lorem ipsum, no broken images, one h1, heading hierarchy, CTA contrast, viewport meta, no horizontal overflow, title length, description length, OG tags, page weight |
| **Validation** | Returns `passed: false` for HTML with lorem ipsum<br>Returns `passed: false` for HTML with no `<title>`<br>Returns `passed: true` for a valid demo page<br>Every check has a `name` and `message` in output |

---

### T15 — Copy Generator (AI + Placeholder)

| Field | Value |
|---|---|
| **Agent** | Engine Agent |
| **Input** | Config (T1), All component specs from phase-1-plan (for slot keys) |
| **Output** | `engine/copy-generator.php` with:<br>`generate_copy(string $business, string $industry, array $keywords, array $config): array`<br>`get_placeholder_copy(string $business, string $industry): array`<br>Delegates based on `$config['ai']['enabled']`<br>Returns structured array with keys: headline, subtext, cta_primary, cta_secondary, features (3-6 items), testimonials (3 items), pricing_plans (3 items), about_blurb, footer_links |
| **Validation** | `AI_ENABLED=false`: returns data instantly with business name in headline<br>`AI_ENABLED=true`: calls DeepSeek, parses JSON response, returns same structure<br>Both paths return identical array shape<br>API failure returns `['error' => '...']` not a crash<br>Curl timeout respects `$config['generation']['timeout_seconds']` |

---

### T16 — Image Fetcher

| Field | Value |
|---|---|
| **Agent** | Engine Agent |
| **Input** | Config (T1) |
| **Output** | `engine/image-fetcher.php` with:<br>`fetch_images(string $business, string $industry, array $config): array`<br>`get_placeholder_images(): array`<br>Returns: `{hero_url, feature_icons: [...]}`<br>Unsplash: searches for business + industry, fetches 1 hero + up to 6 icons<br>Fallback chain: Unsplash → placeholder |
| **Validation** | `AI_ENABLED=false`: returns placeholder paths<br>`AI_ENABLED=true`: calls Unsplash, returns valid URLs<br>Unsplash failure falls back to placeholder (no crash)<br>Always returns at least 1 hero + 3 feature icon URLs<br>All URLs are absolute or root-relative |

---

### T17 — Layout Resolver

| Field | Value |
|---|---|
| **Agent** | Engine Agent |
| **Input** | All component files exist (T4–T9) |
| **Output** | `engine/layout-resolver.php` with function `resolve_layout(string $industry): array`<br>Returns ordered array of component names: `['hero', 'features', 'testimonials', 'cta', 'footer']`<br>Maps: SaaS→includes pricing, Restaurant→includes menu-style features, E-commerce→pricing before testimonials, Default→5 components<br>`get_supported_industries(): array` for the chat UI dropdown |
| **Validation** | `resolve_layout('SaaS')` includes `pricing`<br>`resolve_layout('Restaurant')` does not include `pricing`<br>`resolve_layout('unknown')` returns default layout<br>All returned component names match actual files in `components/` |

---

### T18 — AI System Prompt

| Field | Value |
|---|---|
| **Agent** | Engine Agent |
| **Input** | Component specs from phase-1-plan, Quality checklist (T14) |
| **Output** | `prompts/copy-system.txt` — system prompt for DeepSeek<br>Tells AI: you are a senior conversion copywriter, output only valid JSON, 8th-grade reading level, headlines under 12 words, CTAs 2-4 words, no jargon, no lorem ipsum<br>Includes the exact JSON schema the AI must output |
| **Validation** | Prompt contains the JSON schema<br>Prompt specifies `response_format: json_object`<br>Prompt mentions word count constraints<br>Prompt prohibits: jargon, lorem ipsum, placeholder text |

---

### T19 — Chat UI (index.php)

| Field | Value |
|---|---|
| **Agent** | UI Agent |
| **Input** | All engine files (T15–T18), Page template (T10), AJAX contract from coding-instructions |
| **Output** | `index.php` — full chat interface:<br>Conversation UI (AI bubble, user bubble, styled with Tailwind)<br>Message input (text field, send button)<br>Loading spinner during generation<br>Iframe preview pane (hidden until generation completes)<br>"Contact Now" button (appears after successful generation)<br>Vanilla JS: `fetch()` POST to `generate.php`, reads JSON response, loads iframe<br>Keyboard support: Enter to send |
| **Validation** | Page loads without PHP errors at `http://localhost/landingflow/`<br>Typing a message and pressing Enter shows it in the chat<br>Loading state appears during fetch<br>Iframe appears and loads a URL after successful fetch response<br>Contact Now button appears alongside the iframe<br>Error response shows error message in chat bubble<br>Works with `AI_ENABLED=false` (instant response) |

---

### T20 — Orchestrator (generate.php)

| Field | Value |
|---|---|
| **Agent** | Orchestrator Agent |
| **Input** | All engine files (T15–T18), All components (T4–T9), Page template (T10), Config (T1) |
| **Output** | `generate.php` — the pipeline:<br>1. Reads `config.php`<br>2. Receives JSON POST: `{business_name, industry, keywords}`<br>3. Calls `resolve_layout()` → gets component order<br>4. Calls `generate_copy()` and `fetch_images()` in parallel via `curl_multi_exec`<br>5. Calls `build_seo_meta()`<br>6. Renders each component in layout order with resolved data<br>7. Calls `render_page()` with all component HTML<br>8. Runs `run_quality_checks()`<br>9. Saves HTML to `output/demo-{slug}/index.html`<br>10. Copies images to `output/demo-{slug}/assets/`<br>11. Returns JSON: `{success, preview_url, generation_time_ms, slug}`<br>Times the whole run, hard-stops at 55s |
| **Validation** | POST with valid JSON → returns JSON with `success: true` and `preview_url`<br>Generated `index.html` exists at the returned path<br>HTML passes all quality checks<br>Works with `AI_ENABLED=false` (under 1 second)<br>POST with missing fields → returns JSON with `success: false` and error message<br>Non-POST request → returns JSON error<br>Generation time is measured and included in response |

---

### T21 — End-to-End QA

| Field | Value |
|---|---|
| **Agent** | QA Agent |
| **Input** | Complete system (T1–T20) |
| **Output** | QA report: pass/fail for each success criterion from phase-1-plan<br>Does NOT write code — only reads, runs, and validates |
| **Validation** | Full flow works: open `index.php` → type business + industry → click send → loading spinner → iframe loads generated page → Contact Now button visible<br>AI_ENABLED=false: ≤ 3 seconds end-to-end<br>AI_ENABLED=true: ≤ 60 seconds end-to-end (DeepSeek free tier)<br>Generated page looks professional at 1440px and 375px<br>No console errors in browser<br>No PHP errors or warnings in Apache log<br>All 11 quality checks pass on generated output<br>H1 is exactly one, CTA text is 2-4 words, copy is 8th-grade reading level<br>Contact Now button links to a working contact action |

---

## Dependency Graph

```
T1 (config)
 │
 T2 (design rules)
 │
 T3 (placeholder assets)
 │
 ├── T4 (hero) ──────┐
 ├── T5 (features) ──┤
 ├── T6 (testimonials)┤
 ├── T7 (pricing) ───┤
 ├── T8 (cta) ───────┤
 └── T9 (footer) ────┤
                     │
              T10 (page template)
                     │
              ┌──────┤
              │      │
         T11 (SEO)   T12 (conversion)
              │      │
              │   T13 (UX)
              │      │
              └── T14 (quality checklist)
                     │
              ┌──────┤
              │      │
         T15 (copy)  T16 (images)  T17 (layout)  T18 (prompt)
              │      │              │             │
              └──────┴──────────────┴─────────────┘
                     │
              T19 (chat UI)
                     │
              T20 (orchestrator)
                     │
              T21 (QA)
```

Tasks on the same indentation level at the bottom (T15–T18) can theoretically run in parallel since they don't depend on each other — but per the sync-first rule, execute them sequentially.

---

## Agent Assignment Summary

| Agent | Tasks |
|---|---|
| Config Agent | T1, T3 |
| Rules Agent | T2, T11, T12, T13, T14 |
| Component Agent | T4, T5, T6, T7, T8, T9 |
| Template Agent | T10 |
| Engine Agent | T15, T16, T17, T18 |
| UI Agent | T19 |
| Orchestrator Agent | T20 |
| QA Agent | T21 |
