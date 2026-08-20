# Ready-to-Use Prompts — LandingFlow Homepage with 3D Hero

> **Before you start:** fill in the fields in the "Colors & Fonts" section below.
> Everything else is already filled in with the real content from your site.
> **Send stage by stage** — never all at once.
>
> **Note:** all instructions are in English, but every piece of site copy stays in Hebrew, and the site itself is Hebrew RTL, mobile-first.

---

## Colors & Fonts — fill this in

| Field | My value |
|---|---|
| Primary brand color | `[ #______ ]` |
| Accent / CTA color | `[ #______ ]` |
| Background | `[ #0B0F14 dark / #FFFFFF light ]` |
| Heading font | `[ Heebo / Assistant / Rubik ]` |
| Body font | `[ Heebo / Assistant ]` |

---

# Stage 1 — The 3D Hero

**Copy and send this first:**

```
Build the hero section of the homepage for LandingFlow — an Israeli
business providing landing pages, web development, AI automations,
24/7 monitoring and CRM, delivered personally by one person (not an agency).

## Stack
React + Vite, React Three Fiber, @react-three/drei,
GSAP + ScrollTrigger, Tailwind CSS.

## Language & direction — critical
The entire site is in HEBREW with full RTL.
Set dir="rtl" and lang="he" on the root, use Tailwind's RTL-aware
utilities (ps/pe, ms/me — not pl/pr, ml/mr), and make sure all
animation directions are mirrored correctly for RTL.
All UI copy below must be used EXACTLY as written in Hebrew.
Do not translate it.

## Mobile-first — critical
Design and write the CSS mobile-first: base styles target a 375px
viewport, then scale up with min-width breakpoints (sm/md/lg).
Do not design for desktop and shrink down. Every layout decision
starts from the phone.

## The 3D scene
"Exploded website layers" — 4 transparent rectangular planes
(PlaneGeometry with MeshPhysicalMaterial, transmission/opacity),
floating one above the other in depth, at a slight isometric angle.

Bottom to top:
1. Data layer — a grid of glowing dots
2. Code layer — horizontal lines of varying lengths (mimicking code)
3. UI layer — rounded rectangles suggesting cards and buttons
4. Content layer — a clean top frame

Behavior:
- Each layer floats gently (small amplitude, different phase per layer)
- The whole group rotates slowly on the Y axis — one full turn in ~40s
- Thin glowing lines connect the layers
- Subtle mouse parallax — max 4 degrees, smoothed with lerp

## Lighting
Soft ambient light plus one point light in the accent color,
positioned above and behind, creating a rim light on the layer edges.
Subtle bloom (postprocessing) on the glowing lines only.

## Content above the canvas — keep this Hebrew, verbatim
Headline: "פתרונות טכנולוגיים לעסק שלך, בליווי אישי צמוד."
Subheadline: "דפי נחיתה, אתרים, אוטומציות AI וניטור —
הכל מאדם אחד שמבין טכנולוגיה ומכיר אותך אישית."
Primary button: "🚀 צור אתר דמו עכשיו" → /demo
Secondary button: "🔍 בדיקת אתר חינם" → /audit
Line below: "💬 וואטסאפ ישיר — לא כרטיס תמיכה, קשר אישי"

## Very important — this is a conversion page
- Hero height 85vh, not 100vh — the user must see there's more below
- Text and buttons fully in front, maximum contrast. Add a subtle
  gradient layer behind the text if readability needs it
- The 3D is a background, not the star. It must never steal focus
  from the CTA
- Subtle scroll indicator at the bottom
- On mobile, buttons are full-width and stacked, with a large tap target

## Colors & fonts
Primary [#____], accent [#____], background [#____].
Headings [____], body [____].
Clean and technical, not generic, no templated drop shadows.

## Performance — mobile is the priority
- dpr={[1, 1.5]}
- Lazy load the canvas
- On mobile (below 768px): render a static image/gradient instead
  of the canvas entirely — do not run WebGL on phones
- Respect prefers-reduced-motion — stop all motion

Single file, clean code, comments in English.
```

---

# Stage 2 — Smooth Scroll + Linking the Hero to Scroll

```
Great. Now:

1. Add Lenis for smooth scrolling across the page and wire it
   correctly into ScrollTrigger (lenis.on scroll → ScrollTrigger.update).
   Make sure Lenis is disabled or set to native on touch devices
   so mobile scrolling stays responsive.

2. Link the hero scene to scroll with scrub:
   - As the user scrolls out of the hero, the 4 layers converge
     into one complete layer (moving together on the Z axis)
   - The camera moves slightly closer at the same time
   - Text and buttons fade out and drift slightly upward

That's the narrative: all the pieces come together — exactly
the business's message.

Change nothing else in the existing code.
```

---

# Stage 3 — Services Section

```
Add the services section below the hero.
All copy stays in Hebrew, verbatim.

Heading: "פתרונות טכנולוגיים — הכל מאדם אחד"
Subheading: "דפי נחיתה, אתרים, אוטומציות מבוססות AI,
ניטור וניהול לידים — אין צורך במספר ספקים."

6 cards. Mobile-first grid: 1 column on mobile,
2 from md, 3 from lg.

📄 דפי נחיתה — דפים מהירים להמרה, טפסים חכמים,
   A/B טסטינג ואנליטיקס.
💻 פיתוח אתרים — אתרי תדמית, חנויות אונליין ומערכות,
   קוד נקי, SEO מובנה.
🤖 אוטומציות AI — צ'אטבוטים, מענה אוטומטי ללידים,
   אינטגרציות חכמות.
📡 ניטור 24/7 — בדיקה כל 60 שניות ממספר מוקדים,
   התראות מיידיות בוואטסאפ.
🔍 ביקורות אוטומטיות — SEO, אבטחה, נגישות וביצועים,
   דוח שבועי מסודר.
📊 CRM ולידים — כל פנייה נכנסת אוטומטית,
   עם ניתוב ומעקב מכירה.

Animation:
- Cards enter from (y: 60, opacity: 0, scale: 0.96)
- stagger 0.12s, duration 0.8, easing power3.out
- ScrollTrigger start "top 80%", once: true
- On hover: gentle lift plus a soft glow in the accent color
  (hover styles must not break touch — no hover-only content)

Design: cards with a subtle background and a thin border,
not a generic drop shadow.
```

---

# Stage 4 — "Why One Person" Section (with overlay)

```
Add the advantages section. Hebrew copy, verbatim.

Heading: "למה לעבוד עם אדם אחד ולא עם חברה?"

6 items:
👤 קשר ישיר — אתה מדבר ישירות עם מי שבונה לך את הפתרון.
   אין מתווכים, אין מוקדנים.
⚡ החלטות מהירות — אין בירוקרטיה, אין אישורי הנהלה.
   רוצה לשנות משהו? זה קורה עכשיו.
💰 מחיר הוגן — בלי עלויות תקורה של משרד וצוות מיותר.
🎯 מחויבות מלאה — הפרויקט שלך הוא הפרויקט שלי.
🔧 גמישות מקסימלית — שינוי קטן, תוספת דחופה, רעיון חדש.
   בלי חוזים נוקשים.
💬 זמינות אמיתית — לא כרטיס תמיכה, לא תור.
   וואטסאפ ישיר.

Animation — overlay effect:
- Sticky background (dark gradient or subtle texture) that stays fixed
- Content scrolls over it
- The background dims and scales slightly during scroll (scrub: 1)
- Items reveal alternately: odd items from the right,
  even items from the left (RTL-correct — right is the "start" side)
- On mobile: drop the alternating reveal, use a single
  fade-and-rise from below for all items
```

---

# Stage 5 — Site Audit Section + Final CTA + Footer

```
Add the last sections. Hebrew copy, verbatim.

## Site audit section (the main conversion block)
Heading: "בדיקת אתר מקיפה בחינם"
Body: "הזינו כתובת URL וקבלו תוך דקות דוח מלא על SEO,
אבטחה, נגישות, ביצועים ודרישות משפטיות."

4 checkmark bullets:
- ציון כללי + פירוט לפי קטגוריה
- רשימת בעיות מדורגת לפי השפעה
- המלצות מעשיות לתיקון
- שליחה במייל + וואטסאפ

URL input + button "בדוק את האתר שלך עכשיו" → /audit
Use inputmode="url" and type="url" so mobile keyboards adapt.
This is the most important section on the page — give it strong
visual presence and a background distinct from the rest.

## Final CTA
"רוצה לראות איך האתר שלך ייראה?"
"שלח לי הודעה — אני אחזור אליך אישית עם הצעה מותאמת."
Button: "📞 דברו איתי" → /contact

## Footer
Logo + text: "דפי נחיתה, אתרים, אוטומציות AI ו-CRM —
בליווי אישי. וואטסאפ ישיר, בלי מוקדנים."

Columns (stacked on mobile, 3 across from md):
שירותים: דמו חי, תיק עבודות, בדיקת אתר, מחירים, כל השירותים
חברה: אודות, תמחור, בלוג, צרו קשר
צרו קשר: 052-8529448, info@landingflow.co.il

Social: LinkedIn, Instagram, Facebook
© 2026 LandingFlow · תנאי שימוש · פרטיות
```

---

# Stage 6 — Polish & Optimization

```
Now go over the whole page:

1. Unify all easings and durations for consistency
   (power3.out, 0.8s as the default)
2. Make sure every ScrollTrigger is properly cleaned up
3. Check there is no layout shift on load
4. Verify RTL is correct everywhere — including animation directions,
   icon mirroring, and form field alignment
5. Audit the mobile experience specifically: test at 375px,
   confirm no horizontal overflow, tap targets are at least 44px,
   and the static hero fallback loads instead of WebGL
6. Confirm prefers-reduced-motion disables everything
7. Add meta tags:
   title: "LandingFlow — פתרונות טכנולוגיים לעסק שלך"
   description: "דפי נחיתה, אתרים, אוטומציות AI וניטור —
   בליווי אישי. וואטסאפ ישיר, בלי מוקדנים."
   Include lang="he" and dir="rtl" on <html>.

Give me a short report on what you fixed and what else is worth improving.
```

---

## Quick Fix Phrases

> The hero layers are too crowded / too far apart. Change the Z-axis spacing to [___].

> The text isn't readable enough over the 3D. Strengthen the gradient layer behind it.

> The bloom is too strong and glaring. Lower the intensity to [___].

> The page stutters while scrolling. Diagnose the cause and fix it — likely too many ScrollTriggers or a React re-render inside useFrame.

> The scene rotation is dizzying. Slow it to one full rotation every [60] seconds.

> The design still looks templated. Strengthen the typographic hierarchy — much larger headline, more generous spacing, fewer borders.

> There's horizontal overflow on mobile at 375px. Find the element causing it and fix it.

> Something is off in RTL — [describe what]. Fix the direction handling without touching anything else.

> Revert to the previous version and change only [___].

---

## Once Everything Works

- Replace the procedural layers with real textures (screenshots of your actual work) — this turns the hero into a living portfolio
- Add the chatbot and WhatsApp button back
- Restore the accessibility settings module
- Restore the legal disclaimer in the footer
