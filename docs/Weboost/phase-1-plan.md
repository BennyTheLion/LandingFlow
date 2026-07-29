# Phase 1 — AI Demo Generator · Build Plan

Status: Draft
Owner: Benny Maimon
Last updated: 2025-01-20

---

## Objective

Build a system where a sales person types a business name + industry into an AI chat interface, watches the AI generate a professional demo homepage in under 60 seconds, sees the live preview immediately, and gets a marketing CTA button (e.g. "Contact Now") to hand off to the prospect — all in one seamless flow.

---

## Tech Stack

| Layer | Choice | Reason |
|---|---|---|
| Runtime | PHP 8.1+ | Already running on XAMPP. No new infra needed. |
| Templates | Native PHP partials | PHP is already a template engine. `<?= $var ?>` directly. |
| AI Provider | DeepSeek API | Free tier (`deepseek-chat`) for dev, paid for production. |
| AI Toggle | `config.php` switch | `AI_ENABLED = false` uses placeholder data — zero API cost during template dev. |
| Images | Unsplash API (free tier) | Stock photos. Falls back to placeholder when disabled. |
| Styling | Tailwind CSS (CDN) | Mobile-first responsive. No build step, consistent design tokens. |
| Language | Hebrew (he_IL), RTL | Default locale. Config-driven, switchable via config.php. |
| Font | Heebo (Google Fonts) | Optimized for Hebrew RTL. |
| Design | Mobile-first | All components use sm:/md:/lg: breakpoints. Base = mobile. |
| Output | Static HTML + assets folder | No server needed to view generated demo. |

---

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    index.php (Chat UI)                       │
│                                                             │
│   [AI] Hi! What business should I build a demo for?         │
│   [You] A plumbing company called "FlowPro"                │
│   [AI] Got it. What industry?                               │
│   [You] Home services                                       │
│   [AI] Generating your demo... ⏳                            │
│                                                             │
│   ┌──────────────────────────────────┐                      │
│   │     (Live Preview iframe)        │  ← generated page    │
│   │                                  │                      │
│   └──────────────────────────────────┘                      │
│                                                             │
│   [ Contact Now ]   ← marketing CTA button                  │
└─────────────────────────────────────────────────────────────┘
        │
        │  AJAX POST (business name, industry)
        ▼
┌──────────────────────────────────────────┐
│              generate.php                 │
│          (Engine Orchestrator)            │
├──────────────────────────────────────────┤
│  1. Read config.php                       │
│  2. Resolve layout (layout-resolver.php)  │
│  3. Call AI  ──→ copy (headline, CTAs...) │
│  4. Fetch images (Unsplash or placeholder)│
│  5. Render template → static HTML         │
│  6. Run quality check                     │
│  7. Save to /output/demo-{slug}/          │
│  8. Return URL to preview (JSON)          │
└──────────────────────────────────────────┘
```

### AI Call Strategy (for 60-second target)

All independent AI calls run in parallel via `curl_multi_exec`:

```
curl_multi_exec()
  ├── Copy generation (headline, subtext, CTA, features, about)
  └── Image prompts → Unsplash fetch (hero, feature icons)

Total wall-clock time = slowest single call, not sum of all calls.
```

When `AI_ENABLED = false`, all calls return hardcoded dummy data instantly — useful for building templates without API latency.

---

## UX / Interface Design

The interface is NOT a traditional form with labels and submit buttons. It's an **AI chat conversation** that happens to produce a website. This matters because prospects watching over the sales person's shoulder see the "magic" — it's part of the sales pitch.

### Chat Flow

1. AI opens the conversation: "Hi! Tell me about the business you'd like a demo for."
2. User types naturally: "A plumbing company called FlowPro, serving residential clients."
3. AI asks clarifying follow-ups only if needed (industry, keywords, style preference).
4. AI responds: "Perfect — building your demo now..." and the generation spinner begins.
5. Within 60 seconds, the generated homepage appears in a live preview pane.
6. Below the preview, a prominent **"Contact Now"** button appears — this is the marketing handoff. Clicking it opens a contact form or mailto link so the prospect can take the next step immediately.

### Key UX Rules

- The chat is the only input mechanism. No sidebars, no multi-step wizards.
- The generated page preview is embedded via `<iframe>` pointing to `/output/demo-{slug}/index.html`.
- The "Contact Now" button is NOT part of the generated page — it's part of the platform wrapper, always visible when a demo is live.
- If generation fails, the AI explains why in the chat and offers to retry.
- The chat history stays visible — prospect can scroll up and see the "conversation that built their site."

---

## File Structure

```
htdocs/landingflow/
│
├── config.php                      ← ALL settings: AI toggle, model, API keys, Unsplash key
├── index.php                       ← Chat UI + iframe preview + Contact Now button
├── generate.php                    ← Engine orchestrator (called via AJAX, returns JSON)
│
├── engine/
│   ├── copy-generator.php          ← Calls DeepSeek, returns structured copy JSON
│   ├── image-fetcher.php           ← Calls Unsplash, returns image URLs
│   ├── layout-resolver.php         ← Maps industry → component order
│   └── quality-checker.php         ← Validates output against rules
│
├── components/
│   ├── hero.php                    ← Hero section (headline, subtext, CTA, bg image)
│   ├── features.php                ← Features grid (3–6 feature cards)
│   ├── testimonials.php            ← Social proof strip
│   ├── pricing.php                 ← Pricing table
│   ├── cta.php                     ← Bottom CTA banner
│   └── footer.php                  ← Footer with links & copyright
│
├── templates/
│   └── page.php                    ← Full HTML shell (doctype → </html>)
│
├── rules/
│   ├── design-rules.php            ← Colors, typography, spacing constants
│   ├── ux-rules.php                ← Structure requirements (hero first, CTA above fold)
│   ├── seo-rules.php               ← Meta tags, OG tags, heading hierarchy
│   ├── conversion-rules.php        ← Mandatory elements per page type
│   └── quality-checklist.php       ← Pass/fail checks before output
│
├── prompts/
│   └── copy-system.txt             ← System prompt for DeepSeek (JSON mode)
│
├── assets/
│   └── placeholder/
│       ├── hero-bg.jpg
│       └── feature-icon.svg
│
├── output/
│   └── demo-{slug}/                ← Each generated demo lives in its own folder
│       ├── index.html
│       └── assets/
│
└── docs/Weboost/
    ├── vision.md                   ✅
    ├── phase-1-plan.md             ✅ (this file)
    ├── rules/                      ← Rule definitions (docs, not code)
    ├── components/                 ← Component specs
    └── prompts/                    ← Prompt documentation
```

---

## Config File Design

```php
// config.php
return [
    'ai' => [
        'enabled'  => true,                // false = use dummy data
        'provider' => 'deepseek',
        'model'    => 'deepseek-chat',      // free tier
        // 'model' => 'deepseek-reasoner', // paid tier (uncomment to switch)
        'api_key'  => getenv('DEEPSEEK_API_KEY') ?: 'your-key-here',
        'base_url' => 'https://api.deepseek.com/v1',
        'temperature' => 0.7,
        'max_tokens'  => 1500,
    ],
    'images' => [
        'provider'    => 'unsplash',
        'access_key'  => getenv('UNSPLASH_ACCESS_KEY') ?: 'your-key-here',
        'fallback'    => 'placeholder',     // 'placeholder' | 'solid_color'
    ],
    'generation' => [
        'timeout_seconds' => 55,            // hard stop, reserve 5s for render
        'output_dir'      => __DIR__ . '/output',
    ],
];
```

---

## DeepSeek API Details

| Property | Value |
|---|---|
| Endpoint | `POST https://api.deepseek.com/v1/chat/completions` |
| Auth | `Authorization: Bearer {api_key}` |
| Content-Type | `application/json` |
| JSON Mode | `response_format: { type: "json_object" }` |
| Free Model | `deepseek-chat` |
| Paid Model | `deepseek-chat` (higher limits) or `deepseek-reasoner` |

The API shape is identical to OpenAI Chat Completions. Switching to OpenAI/Anthropic later is a one-line config change — the Engine never touches raw HTTP differently.

---

## Component Specs

### Hero (`components/hero.php`)

Slots the AI fills:
- `headline` — 6–12 words, value proposition
- `subtext` — 15–30 words, supporting detail
- `cta_primary_text` — 2–4 words
- `cta_primary_link` — `#`
- `cta_secondary_text` — 2–4 words (optional)
- `cta_secondary_link` — `#` (optional)
- `hero_image_url` — background image

Enforced by the component:
- Headline is an `<h1>` (exactly one per page)
- CTA is a `<button>` or `<a>` with contrast ratio > 4.5:1
- Image has `loading="eager"` (above fold)

### Features (`components/features.php`)

- 3–6 feature cards
- Each: icon + title + description
- Titles under 5 words
- Descriptions under 20 words

### Testimonials (`components/testimonials.php`)

- AI generates 3 believable testimonials
- Each: quote + name + role + optional company
- When `AI_ENABLED = false`, uses placeholder testimonials

### Pricing (`components/pricing.php`)

- 2–4 plan cards
- Highlight one as "recommended"
- AI generates plan names, prices, feature lists

### CTA (`components/cta.php`)

- Full-width banner above footer
- One headline, one subtext, one button
- Distinct background color from the rest of the page

### Footer (`components/footer.php`)

- Business name
- AI-generated short about blurb
- Placeholder links (About, Services, Contact)
- Copyright line with current year

---

## Layout Resolver Logic

Maps industry → component order. Stored as a simple associative array in `layout-resolver.php`:

```
SaaS / Tech     → hero → features → testimonials → pricing → cta → footer
Restaurant      → hero → features(menu highlights) → testimonials → cta → footer
Agency/Marketing→ hero → features → testimonials → cta → footer
E-commerce      → hero → features → pricing → testimonials → cta → footer
Default         → hero → features → testimonials → cta → footer
```

The AI does NOT pick the layout. The Engine picks it deterministically based on industry. This keeps output predictable and fast.

---

## Quality Checklist (Gate Before Output)

Every generated page must pass these checks. Failures block output and log the reason:

- [ ] No lorem ipsum or placeholder text in final output (when AI enabled)
- [ ] No broken or 404 images
- [ ] Exactly one `<h1>`
- [ ] Heading hierarchy valid (h1 → h2 → h3, no skips)
- [ ] All CTA buttons have `color` contrast ≥ 4.5:1 against background
- [ ] `<meta name="viewport">` present
- [ ] No horizontal overflow at 375px viewport
- [ ] `<title>` tag is 30–60 characters
- [ ] `<meta name="description">` is 120–160 characters
- [ ] OG tags present (title, description, image)
- [ ] Total page weight ≤ 3MB (including images)

---

## Build Order (Milestones)

### M1 — Static Skeleton (no AI yet)
Set `AI_ENABLED = false`. Build the chat UI (`index.php`) with the conversation look-and-feel. Build all 6 components with dummy data. Build `page.php` shell. Build `generate.php` that renders a complete homepage from hardcoded copy and returns the preview URL via AJAX. Target: a beautiful chat interface that produces a static page and shows it in the iframe — all working end-to-end with no AI calls.

### M2 — Config + Engine Wiring
Build `config.php`. Build `layout-resolver.php`. Refactor `generate.php` to read config and wire the orchestrator. Still with dummy data — the engine should produce different layouts based on industry input.

### M3 — DeepSeek Integration
Build `copy-generator.php` with `curl_multi_exec`. Wire DeepSeek API with JSON mode. Parse structured copy response and distribute into component slots. Test with `AI_ENABLED = true`.

### M4 — Image Integration
Build `image-fetcher.php`. Fetch from Unsplash. Fall back to placeholders when disabled or rate-limited. Add image optimization (resize/crop parameters in Unsplash URL).

### M5 — Quality Gate
Build `quality-checker.php`. Run all checks. Log failures. Only save output when all checks pass.

### M6 — Polish
CSS refinements. Loading state on the chat UI. Timing display (show generation time in chat). Error handling (API down, timeout). Start caching common patterns if nearing 60s.

---

## Success Criteria for Phase 1

- [ ] Chat UI looks like a real AI conversation, not a form
- [ ] Valid input → complete homepage HTML in ≤ 60 seconds
- [ ] Output looks professionally designed (not obviously AI-generated)
- [ ] Generated copy reads naturally (8th-grade level, no jargon)
- [ ] All quality checklist items pass
- [ ] Live preview renders correctly in the iframe
- [ ] "Contact Now" button appears after generation and links to a contact action
- [ ] Works with `AI_ENABLED = false` (template dev mode)
- [ ] Works with DeepSeek free tier (`deepseek-chat`)
- [ ] Works with DeepSeek paid tier (`deepseek-reasoner`) — just a config flip
- [ ] Switching to a different AI provider requires changing only `config.php`
