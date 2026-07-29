# SEO Engine

## Purpose

The SEO Engine evaluates web pages across 4 layers:

- SEO (Search Engine Optimization)
- LLMO (AI Understanding)
- AEO (Direct Answers)
- GEO (AI Generation Inclusion)

It is a multi-layer intelligence scoring system, not a simple checklist.

---

# 🔍 SEO CHECKS

## Technical SEO
- Meta tags (title, description, robots)
- Headings structure (H1–H3)
- Schema markup
- Internal links
- Broken links
- Mobile friendliness
- Indexability

## Content SEO
- Keyword relevance
- Content depth
- Duplicate detection
- Readability
- Topic coverage

---

# 🤖 LLMO CHECKS

## Goal
Ensure AI systems can understand and reuse content.

## Checks
- Clear semantic structure
- Modular sections
- One idea per paragraph
- Explicit factual statements
- No ambiguous marketing language

## Chunkability
- Content is independently extractable
- Sections remain meaningful when isolated

---

# 💬 AEO CHECKS

## Goal
Enable content to be used as direct answers.

## Checks
- Direct answers present
- FAQ structure available
- Clear definitions
- Question coverage:
  - What is it?
  - How does it work?
  - Pricing
  - Benefits

## Zero-click readiness
- User can get full answer without leaving page

---

# 🌐 GEO CHECKS

## Goal
Optimize for AI-generated responses.

## Checks
- Structured factual data
- High information density
- Neutral tone
- Reusable content blocks
- Consistent terminology

---

# 📊 SCORING

## Scores
- SEO: 0–100
- LLMO: 0–100
- AEO: 0–100
- GEO: 0–100

## Final Score

Final Score =
(SEO × 0.4) +
(LLMO × 0.25) +
(AEO × 0.2) +
(GEO × 0.15)

---

# 📤 OUTPUT

json
{
  "seo_score": 0,
  "llmo_score": 0,
  "aeo_score": 0,
  "geo_score": 0,
  "issues": [],
  "recommendations": [],
  "priority_fixes": [],
  "summary": ""
}