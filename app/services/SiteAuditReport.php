<?php
namespace App\Services;

use App\Core\Logger;
use App\Scanner\SpamScanner;

/**
 * The site audit behind the public /audit page — the checks, the scoring, and the
 * report as both a PDF and an email body.
 *
 * Extracted from AuditController so the Lead Engine can email the same report for a
 * prospect. One generator means the public report and the prospect report cannot
 * drift apart.
 *
 * Links are passed in rather than built here: this has no Request, and the public
 * report link carries a share_token the caller owns.
 */
class SiteAuditReport
{
    /** Fetch the page and run all six categories */
    public function run(string $url): array
    {
        $url = rtrim($url, '/');
        $ctx = stream_context_create(['http' => ['timeout' => 8, 'user_agent' => 'Mozilla/5.0']]);
        $start = microtime(true);
        $html = @file_get_contents($url, false, $ctx);
        $rt = (int) round((microtime(true) - $start) * 1000);
        if ($html === false) $html = '';
        $headers = @get_headers($url, 1);
        if (!is_array($headers)) $headers = [];

        $results = [
            'seo'           => $this->seoC($url, $html),
            'security'      => $this->secC($url, $headers, $html),
            'legal'         => $this->legC($url, $html),
            'accessibility' => $this->a11yC($html),
            'performance'   => $this->perfC($rt, $html),
            'spam'          => $this->spamC($url, $html, $headers),
        ];

        $recs = [];
        foreach ($results as $cat) {
            foreach (($cat['checks'] ?? []) as $ck) {
                if (!$ck['passed']) {
                    $recs[] = ['category' => $cat['label'], 'check' => $ck['label'], 'action' => 'יש לתקן: ' . $ck['label']];
                }
            }
        }

        return [
            'url'             => $url,
            'reachable'       => $html !== '',
            'overall'         => (int) round(array_sum(array_map(fn($c) => $c['score'], $results)) / count($results)),
            'responseTime'    => $rt,
            'results'         => $results,
            'recommendations' => $recs,
        ];
    }

    private function seoC($u,$h):array{$c=[];preg_match("/<title[^>]*>(.*?)<\/title>/si",$h,$m);$t=trim(strip_tags($m[1]??""));$c["title"]=["passed"=>!empty($t),"label"=>"כותרת (Title)","value"=>$t?mb_substr($t,0,80):"חסר","detail"=>mb_strlen($t)." תווים"];preg_match("/<meta[^>]+name=.description.[^>]+content=.([^.]*)./si",$h,$m);$d=$m[1]??"";$c["desc"]=["passed"=>!empty($d),"label"=>"Meta Description","value"=>$d?mb_substr($d,0,80):"חסר","detail"=>mb_strlen($d)." תווים"];preg_match("/<h1[^>]*>(.*?)<\/h1>/si",$h,$m);$h1=trim(strip_tags($m[1]??""));$c["h1"]=["passed"=>!empty($h1),"label"=>"כותרת H1","value"=>$h1?: "חסר","detail"=>$h1?"נמצא":"לא נמצא"];$rb=@file_get_contents($u."/robots.txt");$c["robots"]=["passed"=>$rb!==false,"label"=>"Robots.txt","value"=>$rb!==false?"קיים":"לא נמצא","detail"=>$rb!==false?substr($rb,0,120):"-"];$sm=@file_get_contents($u."/sitemap.xml");$c["sitemap"]=["passed"=>$sm!==false,"label"=>"Sitemap.xml","value"=>$sm!==false?"קיים":"לא נמצא","detail"=>$sm!==false?substr(strip_tags($sm),0,120):"-"];preg_match("/<link[^>]+rel=.canonical.[^>]+href=.[^\"\047 ]+./si",$h,$m);$can=$m[0]??"";$c["canonical"]=["passed"=>!empty($can),"label"=>"Canonical URL","value"=>$can?htmlspecialchars($can):"לא נמצא"];$c["og"]=["passed"=>strpos($h,"og:title")!==false||strpos($h,"og:description")!==false,"label"=>"Open Graph","value"=>(strpos($h,"og:title")!==false?"og:title ":"").(strpos($h,"og:description")!==false?"og:desc ":"").(strpos($h,"og:image")!==false?"og:image":""),"detail"=>(strpos($h,"og:title")!==false||strpos($h,"og:description")!==false)?"מוגדר":"לא מוגדר"];$c=$this->withTips($c);$p=count(array_filter($c,fn($x)=>$x["passed"]));return["score"=>round($p/count($c)*100),"checks"=>$c,"label"=>"SEO"];}

    private function secC($u,$hdr,$h):array{$c=[];$https=str_starts_with($u,"https://");$c["https"]=["passed"=>$https,"label"=>"HTTPS","value"=>$https?"✅ HTTPS":"❌ HTTP","detail"=>$https?"מוצפן":"לא מוצפן"];$c["ssl"]=["passed"=>$https,"label"=>"SSL","value"=>$https?"✅ תעודה":"❌ אין"];$sh=["Strict-Transport-Security"=>"HSTS","X-Frame-Options"=>"X-Frame","X-Content-Type-Options"=>"X-Content","Content-Security-Policy"=>"CSP","X-XSS-Protection"=>"XSS"];foreach($sh as $k=>$l){$v="";$f=false;if(is_array($hdr))foreach($hdr as $hk=>$hv)if(strcasecmp($hk,$k)===0){$f=true;$v=is_array($hv)?implode(", ",$hv):(string)$hv;break;}$c[strtolower($k)]=["passed"=>$f,"label"=>$l,"value"=>$f?"✅ קיים":"❌ חסר","detail"=>$f?mb_substr($v,0,100):"-","impact"=>$f?"":"יש לשפר אבטחה"];}$mx=false;if($https&&$h){$mx=preg_match("/src=.http:\/\//",$h);}$c["mixed"]=["passed"=>!$mx,"label"=>"Mixed Content","value"=>$mx?"⚠️ נמצא":"✅ תקין","detail"=>$mx?"תוכן HTTP באתר HTTPS":"אין"];$c=$this->withTips($c);$p=count(array_filter($c,fn($x)=>$x["passed"]));return["score"=>round($p/count($c)*100),"checks"=>$c,"label"=>"אבטחה"];}

    private function legC($u,$h):array{$c=[];$c["privacy"]=["passed"=>stripos($h,"privacy")!==false||stripos($h,"פרטיות")!==false,"label"=>"מדיניות פרטיות","value"=>(stripos($h,"privacy")!==false||stripos($h,"פרטיות")!==false)?"נמצא":"לא נמצא"];$c["terms"]=["passed"=>stripos($h,"terms")!==false||stripos($h,"תנאי")!==false,"label"=>"תקנון / תנאי שימוש","value"=>(stripos($h,"terms")!==false||stripos($h,"תנאי")!==false)?"נמצא":"לא נמצא"];$c["access"]=["passed"=>stripos($h,"accessibility")!==false||stripos($h,"נגישות")!==false,"label"=>"הצהרת נגישות","value"=>(stripos($h,"accessibility")!==false||stripos($h,"נגישות")!==false)?"נמצא":"לא נמצא"];$c["cookies"]=["passed"=>stripos($h,"cookie")!==false||stripos($h,"עוגיות")!==false,"label"=>"הודעת Cookies","value"=>(stripos($h,"cookie")!==false||stripos($h,"עוגיות")!==false)?"נמצא":"לא נמצא"];$c["consent"]=["passed"=>preg_match("/checkbox.*consent|consent.*checkbox|אישור.*מדיניות/i",$h)===1,"label"=>"אישור בטפסים","value"=>preg_match("/checkbox.*consent|consent.*checkbox/i",$h)===1?"נמצא":"לא נמצא"];$hp=preg_match("/0\d[-\s]?\d{6,}/",$h);$he=preg_match("/mailto:|[a-z0-9._%+-]+@[a-z0-9.-]+/",$h);$ha=stripos($h,"רחוב")!==false||stripos($h,"כתובת")!==false;$c["footer"]=["passed"=>$hp&&$he&&$ha,"label"=>"פרטי עסק (📞📧📍)","value"=>($hp?"📞":"").($he?" 📧":"").($ha?" 📍":"")];$c=$this->withTips($c);$p=count(array_filter($c,fn($x)=>$x["passed"]));return["score"=>round($p/count($c)*100),"checks"=>$c,"label"=>"משפטי ותאימות"];}

    private function a11yC($h):array{$c=[];preg_match_all("/<img[^>]+>/si",$h,$imgs);preg_match_all("/<img[^>]+alt=.[^.]*./si",$h,$a);$tc=count($imgs[0]??[]);$ac=count($a[0]??[]);$c["alt"]=["passed"=>$tc===0||$ac>=$tc*0.8,"label"=>"Alt לתמונות","value"=>$tc>0?"$ac/$tc":"אין","detail"=>"$ac מתוך $tc"];$c["lang"]=["passed"=>(bool)preg_match("/<html[^>]+lang=./",$h),"label"=>"שפת HTML","value"=>(bool)preg_match("/<html[^>]+lang=./",$h)?"✅ מוגדר":"❌ חסר"];$c["viewport"]=["passed"=>strpos($h,"viewport")!==false,"label"=>"Viewport Meta","value"=>strpos($h,"viewport")!==false?"✅ קיים":"❌ חסר"];$c["aria"]=["passed"=>strpos($h,"aria-")!==false,"label"=>"ציוני ARIA","value"=>strpos($h,"aria-")!==false?"✅ קיימים":"❌ חסרים"];$c["headings"]=["passed"=>(bool)preg_match("/<h[1-6]/",$h),"label"=>"מבנה כותרות","value"=>(bool)preg_match("/<h[1-6]/",$h)?"✅ קיים":"❌ חסר"];$c["level"]=["passed"=>stripos($h,"wcag")!==false||stripos($h,"aa")!==false,"label"=>"רמת נגישות (WCAG)","value"=>stripos($h,"wcag")!==false?"מוצהר":"לא מוצהר"];$c["contact"]=["passed"=>preg_match("/נגישות.*0\d|accessibility.*0\d/",$h)===1,"label"=>"רכז נגישות","value"=>preg_match("/נגישות.*0\d/",$h)===1?"✅ יש":"❌ אין"];$c=$this->withTips($c);$p=count(array_filter($c,fn($x)=>$x["passed"]));return["score"=>round($p/count($c)*100),"checks"=>$c,"label"=>"נגישות"];}

    private function perfC($rt,$h):array{$c=[];$c["resp"]=["passed"=>$rt<2000,"label"=>"זמן תגובה","value"=>"$rt ms","detail"=>$rt."ms (סף: 2000ms)"];$hasGA=strpos($h,"google-analytics")!==false||strpos($h,"gtag")!==false||strpos($h,"googletagmanager")!==false;$hasPixel=strpos($h,"fbq")!==false;$hasHotjar=strpos($h,"hotjar")!==false;$hasClarity=strpos($h,"clarity")!==false;$tr=($hasGA?"GA ":"").($hasPixel?"FB ":"").($hasHotjar?"HJ ":"").($hasClarity?"MC ":"");$c["trackers"]=["passed"=>true,"label"=>"Tracking Scripts","value"=>$tr?: "לא זוהו","detail"=>$tr?"נמצאו עוקבים":"אין"];$c["cookie_ok"]=["passed"=>($hasGA||$hasPixel)?(stripos($h,"cookie")!==false||stripos($h,"עוגיות")!==false):true,"label"=>"Cookies Consent","value"=>($hasGA||$hasPixel)?(stripos($h,"cookie")!==false?"✅ יש":"❌ חסר!"):"N/A","detail"=>($hasGA||$hasPixel)?"חובה כשיש trackers":"אין trackers"];$c=$this->withTips($c);$p=count(array_filter($c,fn($x)=>$x["passed"]));return["score"=>round($p/count($c)*100),"checks"=>$c,"label"=>"ביצועים"];}

    private function spamC($url,$html,$headers=[]):array{$c=[];$s=new SpamScanner();$r=$s->scan($html,$url,$headers);$rl=$r->riskLevel();$lv=match($rl){'LOW'=>'נמוך','MEDIUM'=>'בינוני','HIGH'=>'גבוה','CRITICAL'=>'קריטי',default=>$rl};$ld=match($rl){'LOW'=>'האתר נקי','MEDIUM'=>'אותות ספאם קלים','HIGH'=>'אותות ספאם משמעותיים',default=>'סיכון קריטי — דרוש טיפול מיידי'};$c["risk"]=["passed"=>$rl==='LOW',"label"=>"רמת סיכון ספאם","value"=>$lv,"detail"=>"ציון ספאם: ".$r->spamScore()."/100 — ".$ld];$c["hidden"]=["passed"=>$r->hiddenContentScore>=90,"label"=>"תוכן מוסתר","value"=>$r->hiddenContentScore>=90?"✅ נקי":"⚠️ נמצא","detail"=>"ציון: ".$r->hiddenContentScore."/100"];$c["content"]=["passed"=>$r->contentScore>=80,"label"=>"איכות תוכן","value"=>$r->contentScore>=80?"✅ תקין":"⚠️ בעיות","detail"=>"ציון: ".$r->contentScore."/100"];$c["links"]=["passed"=>$r->linksScore>=80,"label"=>"קישורים חשודים","value"=>$r->linksScore>=80?"✅ תקין":"⚠️ חשודים","detail"=>"ציון: ".$r->linksScore."/100"];$c["domain"]=["passed"=>$r->domainScore>=80,"label"=>"אמינות דומיין","value"=>$r->domainScore>=80?"✅ אמין":"⚠️ בעיות","detail"=>"ציון: ".$r->domainScore."/100"];$c["email"]=["passed"=>$r->emailScore>=80,"label"=>"אבטחת אימייל (SPF/DKIM/DMARC)","value"=>$r->emailScore>=80?"✅ מאובטח":"⚠️ חסר","detail"=>"ציון: ".$r->emailScore."/100"];$c["form"]=["passed"=>$r->formScore>=80,"label"=>"הגנת טפסים","value"=>$r->formScore>=80?"✅ מוגן":"⚠️ פגיע","detail"=>"ציון: ".$r->formScore."/100"];if($r->formCount>0){$c["form_captcha"]=["passed"=>$r->formHasCaptcha,"label"=>"CAPTCHA בטפסים","value"=>$r->formHasCaptcha?"✅ קיים":"❌ חסר","detail"=>$r->formHasCaptcha?"מוגן מפני בוטים":"פגיע לשליחות אוטומטיות","impact"=>$r->formHasCaptcha?"":"הוסיפו reCAPTCHA/hCaptcha לטופס"];$c["form_honeypot"]=["passed"=>$r->formHasHoneypot,"label"=>"שדה מלכודת (Honeypot)","value"=>$r->formHasHoneypot?"✅ קיים":"❌ חסר","detail"=>$r->formHasHoneypot?"מלכודת בוטים מוגדרת":"בוטים עשויים למלא ולשלוח אוטומטית","impact"=>$r->formHasHoneypot?"":"הוסיפו שדה מוסתר (honeypot) לזיהוי בוטים"];$c["form_csrf"]=["passed"=>$r->formHasCsrf,"label"=>"אסימון CSRF","value"=>$r->formHasCsrf?"✅ קיים":"❌ חסר","detail"=>$r->formHasCsrf?"מוגן מפני זיוף בקשות":"פגיע להתקפות CSRF","impact"=>$r->formHasCsrf?"":"הטמיעו אסימון CSRF בכל טופס"];$c["form_validation"]=["passed"=>$r->formHasValidation,"label"=>"ולידציית שדות (HTML5)","value"=>$r->formHasValidation?"✅ קיים":"❌ חסר","detail"=>$r->formHasValidation?"required/type/pattern מוגדרים":"אין אימות שדות בסיסי","impact"=>$r->formHasValidation?"":"הוסיפו required, type=\"email\", pattern לשדות"];}$c=$this->withTips($c);$p=count(array_filter($c,fn($x)=>$x["passed"]));return["score"=>round($p/count($c)*100),"checks"=>$c,"label"=>"ספאם ואמון"];}

    /** Short explanation of what each check verifies and why it matters, shown as a tooltip in the report */
    private const CHECK_TIPS = [
        // SEO
        'title'    => 'תגית ה-Title מוצגת בכרטיסיית הדפדפן ובתוצאות החיפוש בגוגל — היא אחד מגורמי הדירוג החשובים ביותר.',
        'desc'     => 'תיאור מטא (Meta Description) מופיע מתחת לכותרת בתוצאות החיפוש ומשפיע על שיעור ההקלקה (CTR).',
        'h1'       => 'כותרת H1 מסמנת לגוגל ולמשתמשים מהו הנושא המרכזי של הדף. חשוב שיהיה H1 אחד וברור בכל דף.',
        'robots'   => 'קובץ robots.txt מנחה מנועי חיפוש אילו דפים לסרוק ואילו לא. חסרונו עלול לגרום לסריקה לא יעילה.',
        'sitemap'  => 'מפת אתר (sitemap.xml) עוזרת למנועי חיפוש לגלות ולאנדקס את כל דפי האתר במהירות.',
        'canonical'=> 'תגית Canonical מונעת בעיות תוכן כפול על ידי הצבעה על כתובת ה-URL המועדפת לאינדוקס.',
        'og'       => 'תגיות Open Graph קובעות איך הדף ייראה כשמשתפים אותו בפייסבוק, ווטסאפ ורשתות חברתיות אחרות.',
        // Security
        'https'    => 'HTTPS מצפין את התקשורת בין הדפדפן לשרת ומגן על פרטי המשתמשים — גם גוגל מדרג אתרי HTTPS גבוה יותר.',
        'ssl'      => 'תעודת SSL תקפה נדרשת כדי שהדפדפן יציג את האתר כ"מאובטח" ולא יזהיר משתמשים מפניו.',
        'strict-transport-security' => 'כותרת HSTS מכריחה את הדפדפן להשתמש תמיד ב-HTTPS, ומונעת התקפות שמנסות להוריד את החיבור ל-HTTP.',
        'x-frame-options' => 'כותרת זו מונעת הטמעת האתר בתוך iframe באתר זר — הגנה בסיסית מפני התקפות clickjacking.',
        'x-content-type-options' => 'מונעת מהדפדפן "לנחש" סוגי קבצים (MIME sniffing), מה שמצמצם סיכוני הרצת קוד זדוני.',
        'content-security-policy' => 'מדיניות CSP מגבילה אילו סקריפטים ומשאבים מותר לטעון בדף — הגנה מרכזית מפני התקפות XSS.',
        'x-xss-protection' => 'כותרת ישנה יותר שמפעילה סינון XSS מובנה בדפדפן. תוספת קלה שלא מזיקה גם אם מיושנת חלקית.',
        'mixed'    => 'תוכן מעורב (HTTP בתוך דף HTTPS) שובר את מנעול האבטחה בדפדפן וחושף חלק מהתעבורה.',
        // Legal
        'privacy'  => 'מדיניות פרטיות מפרטת אילו נתונים נאספים ואיך משתמשים בהם — נדרשת על פי חוק הגנת הפרטיות בישראל ותקנות דומות בעולם.',
        'terms'    => 'תקנון / תנאי שימוש מגדירים את הזכויות והחובות של המשתמשים והבעלים ומצמצמים חשיפה משפטית.',
        'access'   => 'הצהרת נגישות נדרשת בישראל על פי חוק שוויון זכויות לאנשים עם מוגבלות עבור אתרים עסקיים.',
        'cookies'  => 'הודעת Cookies מיידעת גולשים על שימוש בעוגיות מעקב, כנדרש בתקנות פרטיות שונות (כגון GDPR).',
        'consent'  => 'תיבת אישור בטפסים מוודאת הסכמה מפורשת של המשתמש לתנאים או לאיסוף המידע לפני שליחה.',
        'footer'   => 'פרטי יצירת קשר גלויים (טלפון, אימייל, כתובת) בונים אמון ולעיתים נדרשים לצורך שקיפות עסקית.',
        // Accessibility
        'alt'      => 'טקסט חלופי (alt) לתמונות מאפשר לקוראי מסך לתאר תמונות לגולשים עיוורים ומשפר גם SEO.',
        'lang'     => 'תכונת lang בתגית ה-HTML מודיעה לדפדפן ולקוראי מסך באיזו שפה הדף כתוב, לצורך הגייה ותרגום נכונים.',
        'viewport' => 'תגית Viewport קובעת איך הדף מתאים את עצמו למסכי מובייל — ללא הוא עלול להיראות שבור בטלפון.',
        'aria'     => 'תכונות ARIA מוסיפות מידע נגישות לרכיבים מורכבים (תפריטים, טפסים) עבור טכנולוגיות מסייעות.',
        'headings' => 'מבנה כותרות היררכי (H1-H6) מסייע לגולשים ולקוראי מסך לנווט בדף ולהבין את המבנה שלו.',
        'level'    => 'הצהרה על רמת תאימות לתקן הנגישות (WCAG) מראה שהאתר נבדק ועומד בסטנדרט מוכר.',
        'contact'  => 'פרטי רכז נגישות נדרשים בישראל באתרים עסקיים כדי שגולשים יוכלו לדווח על בעיות נגישות.',
        // Performance
        'resp'     => 'זמן תגובה מהיר משפר את חוויית המשתמש ומפחית נטישה — גוגל גם משקלל מהירות בדירוג.',
        'trackers' => 'כלי מעקב (אנליטיקס, פיקסלים) מאפשרים למדוד ביצועים והמרות באתר.',
        'cookie_ok'=> 'כאשר יש כלי מעקב באתר, חובה להציג הודעת עוגיות בהתאם לתקנות הפרטיות.',
        // Spam / Trust
        'risk'     => 'ציון סיכון הספאם הכולל משקלל אבטחה, תוכן מוסתר, קישורים חשודים, אמינות דומיין וכו׳.',
        'hidden'   => 'טקסט מוסתר (display:none, opacity:0 וכדומה) הוא טכניקת ספאם ישנה שמנועי חיפוש מענישים עליה.',
        'content'  => 'תוכן דליל, חזרתי או עתיר מילות מפתח (keyword stuffing) עלול להיראות כספאם בעיני מנועי חיפוש.',
        'links'    => 'קישורים רבים לדומיינים חשודים או קטגוריות ספאם (הימורים, תרופות וכו׳) פוגעים באמינות הדומיין.',
        'domain'   => 'תבנית הדומיין (סיומת חשודה, מקפים רבים, מילות ספאם) משפיעה על אמון גולשים ומנועי חיפוש.',
        'email'    => 'רשומות SPF/DKIM/DMARC מוודאות שאימיילים מהדומיין שלכם לא יזויפו ולא ייחסמו כספאם.',
        'form'     => 'ציון כולל להגנת הטפסים באתר מפני שליחות ספאם אוטומטיות — משוקלל מכל הבדיקות הפרטניות למטה.',
        'form_captcha' => 'CAPTCHA/reCAPTCHA מונע מבוטים אוטומטיים למלא ולשלוח טפסים בהמוניהם.',
        'form_honeypot' => 'שדה honeypot הוא שדה מוסתר שבני אדם לא ימלאו — אם הוא מתמלא, מדובר כמעט תמיד בבוט.',
        'form_csrf' => 'אסימון CSRF מוודא שהבקשה נשלחה מהטופס האמיתי באתר ולא מאתר זר זדוני בשם המשתמש.',
        'form_validation' => 'ולידציה בסיסית (required, type=email, pattern) מסננת קלט שגוי לפני שהוא מגיע לשרת.',
    ];

    /** Attach an explanatory tooltip to each check based on its array key */
    private function withTips(array $c): array
    {
        foreach ($c as $key => &$check) {
            if (!isset($check['tip']) && isset(self::CHECK_TIPS[$key])) {
                $check['tip'] = self::CHECK_TIPS[$key];
            }
        }
        unset($check);
        return $c;
    }

    /**
     * Email the audit results: the full findings inline in the body, plus the same
     * report as a PDF attachment. Shared by the automatic send at the end of a scan
     * and by the "send to email" button.
     *
     * A failed PDF is not fatal — the body already carries every check, so the mail
     * still goes out with the results in it.
     */
    public function sendReportEmail(array $report, array $data, array $recs, string $email, ?string $link = null): bool
    {
        $pdfPath = '';
        try {
            $tmpDir = STORAGE_PATH . '/tmp';
            if (!is_dir($tmpDir)) mkdir($tmpDir, 0755, true);
            $pdfPath = $tmpDir . '/audit_' . ((int)($report['id'] ?? 0) ?: 'new') . '_' . bin2hex(random_bytes(4)) . '.pdf';
            $this->generateReportPdf($report, $data, $pdfPath);
        } catch (\Throwable $e) {
            Logger::error('audit: report PDF generation failed', ['message' => $e->getMessage()]);
            if ($pdfPath !== '' && file_exists($pdfPath)) @unlink($pdfPath);
            $pdfPath = '';
        }

        try {
            return Mailer::send(
                $email,
                'דוח הביקורת שלכם — ' . parse_url((string)($report['url'] ?? ''), PHP_URL_HOST),
                $this->buildReportEmailBody($report, $data, $recs, $pdfPath !== '', $link),
                '',
                '',
                $pdfPath
            );
        } catch (\Throwable $e) {
            Logger::error('audit: failed to email report', ['message' => $e->getMessage()]);
            return false;
        } finally {
            if ($pdfPath !== '' && file_exists($pdfPath)) @unlink($pdfPath);
        }
    }

    /** Plain-text rendering of every category, check and recommendation */
    public function buildReportEmailBody(array $report, array $data, array $recs, bool $hasPdf = true, ?string $link = null): string
    {
        $lines = [
            'שלום,',
            '',
            'להלן תוצאות בדיקת האתר שביקשתם:',
            (string)($report['url'] ?? ''),
            '',
            'ציון כללי: ' . (int)($report['overall_score'] ?? 0) . '/100',
            '',
        ];

        foreach ($data as $cat => $d) {
            $lines[] = '── ' . ($d['label'] ?? $cat) . ' — ' . (int)($d['score'] ?? 0) . '/100';
            foreach (($d['checks'] ?? []) as $ck) {
                $line = '   ' . (!empty($ck['passed']) ? '✔' : '✘') . ' ' . ($ck['label'] ?? '');
                $value = trim((string)($ck['value'] ?? ''));
                if ($value !== '' && $value !== '-') {
                    $line .= ': ' . $value;
                }
                $lines[] = $line;
                if (empty($ck['passed']) && !empty($ck['impact'])) {
                    $lines[] = '      ⚠ ' . $ck['impact'];
                }
            }
            $lines[] = '';
        }

        if (!empty($recs)) {
            $lines[] = 'המלצות לשיפור:';
            foreach ($recs as $rec) {
                $lines[] = '   • ' . ($rec['action'] ?? '');
            }
            $lines[] = '';
        }

        if ($hasPdf) {
            $lines[] = 'הדוח המלא מצורף להודעה זו כקובץ PDF.';
        }
        if ($link !== null && $link !== '') {
            $lines[] = 'לצפייה בדוח בדפדפן: ' . $link;
        }
        $lines[] = '';
        $lines[] = 'בברכה,';
        $lines[] = 'צוות LandingFlow';

        return implode("\n", $lines);
    }

    public function generateReportPdf(array $report, array $data, string $outputPath): void
    {
        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('LandingFlow');
        $pdf->SetAuthor('LandingFlow');
        $pdf->SetTitle('דוח ביקורת — ' . $report['url']);
        $pdf->setRTL(true);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->AddPage();
        $pdf->SetFont('freesans', '', 11);
        // TCPDF emits harmless "undefined array key" notices for some Hebrew glyphs
        // missing from the font's kerning table (PHP 8 + legacy TCPDF quirk) — suppressed
        // the same way this codebase already suppresses noisy DOMDocument::loadHTML() warnings.
        @$pdf->writeHTML($this->buildReportPdfHtml($report, $data), true, false, true, false, '');
        $pdf->Output($outputPath, 'F');
    }

    private function buildReportPdfHtml(array $report, array $data): string
    {
        $html = '<style>
            body{font-family:freesans;direction:rtl;color:#1e293b}
            h1{font-size:20px;color:#5B47E0;margin:0 0 4px}
            .meta{font-size:11px;color:#64748b;margin-bottom:10px}
            .score{font-size:32px;font-weight:bold;color:#5B47E0;text-align:center;margin:14px 0}
            h2{font-size:14px;color:#4634B8;margin:16px 0 6px}
            table{width:100%;border-collapse:collapse;margin-bottom:10px}
            td,th{padding:5px 7px;border:1px solid #ddd;font-size:10px;text-align:right}
            th{background:#F4F5FA;font-weight:bold}
            .pass{color:#1FA15C;font-weight:bold}
            .fail{color:#FF4D8D;font-weight:bold}
        </style>';
        $html .= '<h1>דוח ביקורת — ' . htmlspecialchars($report['url']) . '</h1>';
        $html .= '<div class="meta">תאריך: ' . htmlspecialchars($report['created_at']) . '</div>';
        $html .= '<div class="score">' . (int)$report['overall_score'] . '/100</div>';
        foreach ($data as $cat => $d) {
            $html .= '<h2>' . htmlspecialchars($d['label'] ?? $cat) . ' (' . (int)($d['score'] ?? 0) . '/100)</h2>';
            $html .= '<table><tr><th>בדיקה</th><th>תוצאה</th><th>סטטוס</th></tr>';
            foreach (($d['checks'] ?? []) as $ck) {
                $passed = !empty($ck['passed']);
                $html .= '<tr><td>' . htmlspecialchars($ck['label'] ?? '') . '</td><td>' . htmlspecialchars($ck['value'] ?? '-') . '</td><td class="' . ($passed ? 'pass' : 'fail') . '">' . ($passed ? 'עבר' : 'נכשל') . '</td></tr>';
            }
            $html .= '</table>';
        }
        $html .= '<p style="margin-top:20px;color:#999;font-size:9px;text-align:center">דוח זה הופק על ידי LandingFlow — landingflow.co.il</p>';
        return $html;
    }

}
