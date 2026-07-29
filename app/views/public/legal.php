<?php ob_start();
$pages = [
  "privacy" => ["title" => "מדיניות פרטיות", "content" => "<h2>1. מבוא</h2><p>ברוכים הבאים למדיניות הפרטיות של LandingFlow. אנו מכבדים את פרטיותך ומחויבים להגן על המידע האישי שלך.</p><h2>2. איזה מידע אנו אוספים</h2><p>אנו אוספים מידע שאתה מספק: שם, אימייל, טלפון, וכתובת האתר.</p><h2>3. כיצד אנו משתמשים</h2><p>המידע משמש לאספקת השירותים, שיפור המערכת, עדכונים ותמיכה. איננו מוכרים מידע.</p><h2>4. זכויותיך</h2><p>תוכל לבקש לעיין, לתקן או למחוק מידע בפנייה ל-<a href='mailto:hello@landingflow.co.il'>hello@landingflow.co.il</a>.</p>"],
  "terms" => ["title" => "תנאי שימוש", "content" => "<h2>1. כללי</h2><p>השימוש באתר ובשירותי LandingFlow כפוף לתנאים המפורטים. עצם השימוש מהווה הסכמה.</p><h2>2. השירותים</h2><p>LandingFlow מספקת שירותי ניטור, CRM, אחסון ותחזוקה. אנו שומרים לעצמנו הזכות לשנות שירותים.</p><h2>3. חשבון</h2><p>הנך אחראי לשמירת סודיות הסיסמה. עליך להודיע מיד על שימוש לא מורשה.</p>"],
  "cookies" => ["title" => "מדיניות עוגיות", "content" => "<h2>מהן עוגיות</h2><p>עוגיות הן קבצים קטנים הנשמרים במכשירך. אנו משתמשים בעוגיות הכרחיות, אנליטיות ושיווק.</p><h2>ניהול</h2><p>תוכל לנהל עוגיות דרך הגדרות הדפדפן.</p>"],
  "accessibility" => ["title" => "הצהרת נגישות", "content" => "<h2>מחויבות</h2><p>LandingFlow מחויבת להנגשת האתר לכולם, בהתאם לתקנות.</p><h2>התאמות</h2><ul><li>ניווט מקלדת</li><li>תמיכה בקוראי מסך</li><li>ניגודיות גבוהה</li><li>הגדלת טקסט</li></ul>"],
  "disclosure" => ["title" => "גילוי נאות", "content" => "<p>LandingFlow אינה מייצגת את החברות המוצגות בדוגמאות.</p>"],
  "deletion" => ["title" => "מחיקת מידע", "content" => "<h2>בקשה</h2><p>שלח בקשה ל-<a href='mailto:hello@landingflow.co.il'>hello@landingflow.co.il</a>. טיפול תוך 30 ימי עסקים.</p>"],
  "retention" => ["title" => "שמירת מידע", "content" => "<h2>תקופות</h2><ul><li>חשבון: פעיל + 90 יום</li><li>תשלומים: 7 שנים</li><li>לוגים: 12 חודשים</li></ul>"],
  "service" => ["title" => "הסכם שירות", "content" => "<h2>התחייבויות</h2><p>אנו מתחייבים לספק שירות מקצועי. הלקוח מתחייב לספק חומרים במועד.</p>"],
  "maintenance" => ["title" => "הסכם תחזוקה", "content" => "<h2>שירותים</h2><p>עדכוני אבטחה, גיבויים, ניטור, תמיכה. זמני תגובה: קריטי-4ש, בינוני-24ש.</p>"],
];
$p = $pages[$page] ?? ["title" => $title ?? "", "content" => ""];
$h1Class = "font-size:2rem;font-weight:800;margin-bottom:30px";
$h2Class = "font-size:1.2rem;font-weight:700;margin-top:30px;margin-bottom:12px;color:var(--primary-dark)";
?>
<section style="padding-top:140px;padding-bottom:80px"><div class="container" style="max-width:860px"><h1 style="<?= $h1Class ?>"><?= htmlspecialchars($p["title"]) ?></h1><div style="color:var(--ink-soft);line-height:1.8"><?= $p["content"] ?></div></div></section>
<?php $content = ob_get_clean(); include __DIR__ . "/../partials/layout.php"; ?>
