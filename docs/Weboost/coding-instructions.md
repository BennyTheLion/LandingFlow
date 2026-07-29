# Agent Coding Instructions — Weboost Platform

Version: 1.0
Status: Draft
Owner: Benny Maimon

---

> **Who this is for:** AI coding agents working on the Weboost codebase.
> **When to read this:** Before writing or modifying any PHP, HTML, JS, or config file in `htdocs/landingflow/`.
> **Related docs:** `vision.md` (why), `phase-1-plan.md` (what), this file (how).

---

## 1. Environment

| Fact | Value |
|---|---|
| Runtime | PHP 8.1+ on XAMPP (Apache) |
| Root | `C:\xampp\htdocs\landingflow\` |
| Shell | PowerShell (`C:\Windows\System32\WindowsPowerShell\v1.0\powershell.exe`) |
| Browser | Any modern browser pointed at `http://localhost/landingflow/` |
| Package manager | None. No Composer, no npm. Keep it zero-dependency. |
| CSS | Tailwind CSS via CDN `<script src="https://cdn.tailwindcss.com">` |
| JS | Vanilla JS. No frameworks. `fetch()` for AJAX. |
| AI API | DeepSeek via `curl` in PHP. OpenAI-compatible shape. |
| Images | Unsplash API via `curl` in PHP. |
| Language | Hebrew (`he_IL`), RTL direction. Config-driven via `config.php`. |
| Font | Heebo (Google Fonts). Swap via `config.php` if needed. |
| Design | Mobile-first. All components use `sm:` / `md:` / `lg:` breakpoints with base = mobile. |

---

## 2. Core Principles (non-negotiable)

### 2.1 The AI is NOT the system
The Website Engine controls every generation. The AI only fills text slots. Never let the AI decide layout, colors, fonts, component order, or whether output is valid. Those decisions belong to the Engine (PHP code) and the rules files.

### 2.2 Determinism over cleverness
Business logic must never depend entirely on an AI decision. Any AI-generated content must pass through a quality gate before reaching output. If the AI fails, the system must still produce something usable (dummy data).

### 2.3 Config-first
Every toggle, key, URL, model name, timeout, and feature flag lives in `config.php`. No hardcoded credentials, endpoints, or magic strings anywhere else. The config file is an associative array returned as a PHP file — just `require` it.

### 2.4 AI_ENABLED guard
Every function that calls an external API (DeepSeek, Unsplash) must check `$config['ai']['enabled']` first. When disabled, return hardcoded placeholder data immediately. This lets the entire template system work with zero API calls.

```php
function generate_copy(string $business, string $industry, array $config): array {
    if (!$config['ai']['enabled']) {
        return get_placeholder_copy($business, $industry);
    }
    // ... real API call
}
```

---

## 3. PHP Coding Standards

### 3.1 PHP Version Features
Use PHP 8.1+ features:
- **Typed properties** in classes (if any)
- **Match expressions** instead of long switch/case
- **Named arguments** for functions with many params
- **Nullsafe operator** `?->` for optional chains
- **Array unpacking** `[...$a, ...$b]`

Avoid: attributes, enums (keep it simple for now), fibers.

### 3.2 Style
- PSR-12-ish: 4-space indent, opening brace on same line.
- **Always use `<?php` tag, never short-open `<?`**.
- **Always use `<?=` for echo in templates** — it's the only exception.
- Functions and variables: `snake_case`.
- Array keys: `snake_case`.
- Constants: `UPPER_SNAKE_CASE`.

### 3.3 File structure
Every PHP file does exactly one thing and its name says what:
- `config.php` → returns config array
- `copy-generator.php` → functions for AI copy generation
- `hero.php` → renders the hero component

No single file should contain HTML, business logic, and API calls mixed together.

### 3.4 No classes unless needed
Prefer plain functions grouped in files. A file is a module. Only introduce classes when you need state or multiple instances. For Phase 1, most files should just be a collection of functions.

### 3.5 Error handling
- Never silence errors with `@`.
- Never `die()` or `exit()` in engine code — throw or return an error array.
- The `generate.php` orchestrator is the only place that catches errors and returns them as JSON.
- API call failures must return a structured error array, never raw curl output.

```php
// Good
function call_deepseek(array $config, array $messages): array {
    // ... curl setup ...
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        return ['error' => 'API call failed: ' . curl_error($ch)];
    }
    return json_decode($response, true);
}
```

---

## 4. File Naming and Locations

```
engine/           ← Business logic. No HTML output.
components/       ← Renders HTML partials. Receives data, returns HTML string.
templates/        ← Full-page HTML shells.
rules/            ← Constants, arrays, validation functions. No side effects.
prompts/          ← Plain-text system prompts for AI. No PHP code.
assets/           ← Static files served directly.
output/           ← Generated demo sites. Never commit these.
docs/Weboost/     ← Planning docs, vision, instructions. Not code.
```

- Engine files: `kebab-case.php` (`copy-generator.php`, `image-fetcher.php`)
- Component files: `kebab-case.php` (`hero.php`, `features.php`)
- Rule files: `kebab-case.php` (`design-rules.php`, `seo-rules.php`)
- Prompt files: `kebab-case.txt` (`copy-system.txt`)

---

## 5. Config-Driven Pattern

Every piece of code that varies by environment or preference must read from `config.php`. Never branch on a hardcoded string.

```php
// ❌ Bad
$api_key = 'sk-abc123';

// ❌ Bad
if ($model === 'deepseek-chat') { ... }

// ✅ Good
$config = require __DIR__ . '/config.php';
$api_key = $config['ai']['api_key'];

// ✅ Good
$model = $config['ai']['model'];
```

The config array contract (keys that must exist):

```php
$config['ai']['enabled']        // bool
$config['ai']['provider']       // string
$config['ai']['model']          // string
$config['ai']['api_key']        // string
$config['ai']['base_url']       // string
$config['ai']['temperature']    // float
$config['ai']['max_tokens']     // int
$config['images']['provider']   // string
$config['images']['access_key'] // string
$config['images']['fallback']   // string
$config['generation']['timeout_seconds'] // int
$config['generation']['output_dir']      // string
```

---

## 6. Component Authoring Rules

Every component file (`components/*.php`) must follow this contract:

### 6.1 Signature
Every component is a function that takes a data array and returns an HTML string:

```php
function render_hero(array $data): string {
    // ...
    return $html;
}
```

### 6.2 Slots, not logic
Components receive fully-resolved data. They never call APIs, read config, or make decisions about what to show. They just render.

```php
// ❌ Bad — component fetches its own data
function render_hero(): string {
    $config = require __DIR__ . '/../config.php';
    $copy = call_deepseek($config, ...);
    // ...
}

// ✅ Good — data is passed in
function render_hero(array $data): string {
    extract($data); // $headline, $subtext, $cta_text, $hero_image_url
    ob_start();
    ?>
    <section class="relative ...">
        <h1><?= htmlspecialchars($headline) ?></h1>
        <!-- ... -->
    </section>
    <?php
    return ob_get_clean();
}
```

### 6.3 Escape output
Always use `htmlspecialchars()` for dynamic text content. Image URLs and link hrefs should be validated before being passed to the component — the component still escapes them.

### 6.4 Required Slots
Each component documents which keys it expects in `$data`. If a key is missing, the component should use a sensible fallback, not crash.

```php
function render_hero(array $data): string {
    $headline = $data['headline'] ?? 'Your Business Name';
    $subtext  = $data['subtext']  ?? 'Professional services you can trust.';
    // ...
}
```

---

## 7. AJAX Contract (Frontend ↔ Backend)

The chat UI (`index.php`) communicates with the engine (`generate.php`) via `fetch()` POST requests.

### 7.1 Request (UI → Engine)
```json
POST /landingflow/generate.php
Content-Type: application/json

{
    "business_name": "FlowPro Plumbing",
    "industry": "Home Services",
    "keywords": ["reliable", "affordable", "24/7"]
}
```

### 7.2 Response (Engine → UI)
**Success:**
```json
{
    "success": true,
    "preview_url": "/landingflow/output/demo-flowpro-plumbing/index.html",
    "generation_time_ms": 4200,
    "slug": "demo-flowpro-plumbing"
}
```

**Failure:**
```json
{
    "success": false,
    "error": "AI API returned HTTP 429 — rate limited. Try again in 30 seconds.",
    "generation_time_ms": 2100
}
```

### 7.3 Rules
- `generate.php` always returns JSON. Never HTML. Never a redirect.
- Set `Content-Type: application/json` header before any output.
- The `preview_url` is relative to the document root — the iframe uses it directly.
- `generation_time_ms` is always included (success or failure) for timing display in the chat.

---

## 8. The Placeholder / Dummy Data Pattern

When `$config['ai']['enabled'] === false`, every generator function must return realistic-looking placeholder data. This data should:
- Look believable (not "test test test")
- Cover all keys the components expect
- Vary slightly based on input (different business name at minimum)

```php
function get_placeholder_copy(string $business, string $industry): array {
    return [
        'headline'       => "{$business} — {$industry} Done Right",
        'subtext'        => "We help customers get the best {$industry} experience, guaranteed.",
        'cta_primary'    => 'Get Started',
        'cta_secondary'  => 'Learn More',
        'features'       => [
            ['title' => 'Quality First',   'description' => 'We never compromise on quality.'],
            ['title' => 'Fast Turnaround', 'description' => 'Projects delivered on time.'],
            ['title' => 'Expert Team',     'description' => 'Years of experience in ' . $industry . '.'],
        ],
        'testimonials'   => [
            ['quote' => "Best {$industry} service we've ever used.", 'name' => 'Jane D.', 'role' => 'Business Owner'],
            ['quote' => 'Professional, fast, and affordable.',       'name' => 'Mike R.', 'role' => 'CEO'],
            ['quote' => 'Highly recommend to anyone.',               'name' => 'Sarah L.', 'role' => 'Director'],
        ],
    ];
}
```

---

## 9. Security Rules

- **Never commit API keys.** `config.php` reads from environment variables with a fallback string for local dev only.
- **Escape all output** in components: `htmlspecialchars($var, ENT_QUOTES, 'UTF-8')`.
- **Validate `preview_url`** — the iframe must only load from `/output/` paths, never arbitrary URLs.
- **No `eval()`, no `exec()` on user input**, no `unserialize()` on untrusted data.
- **Rate limit** the `generate.php` endpoint — at minimum, check that the request is a POST with JSON content-type.

---

## 10. What NOT to Do

| Don't | Because |
|---|---|
| Don't install Composer packages | Zero-dependency goal for Phase 1 |
| Don't add a Node.js build step | Tailwind CDN is enough; no webpack/vite |
| Don't use a PHP framework (Laravel, Symfony) | Overkill for a single-page generator |
| Don't let the AI pick colors, fonts, or layouts | Engine rules decide these deterministically |
| Don't output HTML from engine files | Engine returns data; templates/components return HTML |
| Don't hardcode URLs with `localhost` | Use relative paths or config-driven base URLs |
| Don't create classes for simple data structures | Use associative arrays |
| Don't mix JS framework (React, Vue, etc.) | Vanilla JS only for the chat UI |
| Don't call `die()` or `exit()` mid-script | Return errors, let the orchestrator handle them |

---

## 11. How to Add a New Component

1. Create `components/component-name.php` with a `render_component_name(array $data): string` function.
2. Add its placeholder data in `engine/copy-generator.php` (in the `get_placeholder_copy()` function).
3. Add its AI prompt section in `prompts/copy-system.txt`.
4. Register it in `engine/layout-resolver.php` so industries can include it.
5. Include it in `templates/page.php` inside the layout.
6. Test with `AI_ENABLED = false` first (instant), then with `true`.

---

## 12. Before Committing Code

Run through this checklist mentally:

- [ ] Does this code work with `AI_ENABLED = false`?
- [ ] Does this code work with `AI_ENABLED = true` (if applicable)?
- [ ] Are all API keys read from config, not hardcoded?
- [ ] Is all user-facing output escaped with `htmlspecialchars()`?
- [ ] Does any new file follow the naming and location conventions?
- [ ] Does `generate.php` always return valid JSON?
- [ ] Are errors caught and returned as structured arrays, not raw strings?
- [ ] Is there a fallback for every slot a component expects?
