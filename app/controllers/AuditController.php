<?php
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Logger;
use App\Services\Mailer;
use App\Core\Session;
use App\Services\LeadService;
use App\Scanner\SpamScanner;

class AuditController extends Controller
{
    public function index(): string
    {
        return $this->render('public/audit', ['pageTitle' => 'בדיקת אתר חינם — LandingFlow']);
    }

    public function check()
    {
        // Route to sendCode if action parameter set
        if (($_POST['action'] ?? '') === 'sendCode') {
            $this->sendCode();
            return;
        }
        $url = $_POST['url'] ?? '';
        $email = $_POST['email'] ?? '';
        $code = $_POST['code'] ?? '';
        
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            http_response_code(422);
            die(json_encode(['success' => false, 'error' => 'URL לא תקין']));
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            die(json_encode(['success' => false, 'error' => 'נא להזין אימייל לקבלת הדוח']));
        }
        
        // Verify email code
        $storedCode = Session::get('audit_verify_code');
        $storedEmail = Session::get('audit_verify_email');
        if (!$storedCode || !$storedEmail || $storedEmail !== $email || (string)$code !== (string)$storedCode) {
            http_response_code(422);
            die(json_encode(['success' => false, 'error' => 'קוד אימות שגוי. לחץ על "שלח קוד" ונסה שוב.']));
        }
        Session::remove('audit_verify_code');
        Session::remove('audit_verify_email');
        
        $url = rtrim($url, '/');
        $ctx = stream_context_create(['http' => ['timeout' => 8, 'user_agent' => 'Mozilla/5.0']]);
        // Time the page fetch itself — $start used to be set after it, so every site
        // measured 0ms and passed the response-time check for free
        $start = microtime(true);
        $html = @file_get_contents($url, false, $ctx);
        $rt = round((microtime(true) - $start) * 1000);
        if ($html === false) $html = '';
        $headers = @get_headers($url, 1);
        if (!is_array($headers)) $headers = [];
        $seo = $this->seoC($url, $html);
        $sec = $this->secC($url, $headers, $html);
        $leg = $this->legC($url, $html);
        $a11y = $this->a11yC($html);
        $perf = $this->perfC($rt, $html);
        $spam = $this->spamC($url, $html, $headers);
        $all = [$seo['score'], $sec['score'], $leg['score'], $a11y['score'], $perf['score'], $spam['score']];
        $ovr = round(array_sum($all) / count($all));
        $results = ['seo'=>$seo,'security'=>$sec,'legal'=>$leg,'accessibility'=>$a11y,'performance'=>$perf,'spam'=>$spam];
        // Built outside the DB block — the email body and the JSON response both
        // need it even when the insert fails.
        $recs = [];
        foreach ([$seo,$sec,$leg,$a11y,$perf,$spam] as $cat)
            foreach (($cat['checks']??[]) as $c)
                if (!$c['passed']) $recs[] = ['category'=>$cat['label'],'check'=>$c['label'],'action'=>'יש לתקן: '.$c['label']];
        $rid = 0;
        // Authorizes the report link in the email, which has to open outside this
        // session — see database/migrations/2026_08_19_audit_report_share_token.sql
        $shareToken = bin2hex(random_bytes(32));
        try {
            $db = Database::getInstance()->getConnection();
            $db->prepare("INSERT INTO audit_reports (url,overall_score,seo_score,security_score,legal_score,accessibility_score,performance_score,spam_score,total_checks,passed_checks,failed_checks,full_report,recommendations,status,share_token,ip_address,user_agent,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,'completed',?,?,?,NOW())")
               ->execute([$url, $ovr, $seo['score'], $sec['score'], $leg['score'], $a11y['score'], $perf['score'], $spam['score'], 30, (int)round($ovr/100*30), 30-(int)round($ovr/100*30), json_encode($results, JSON_UNESCAPED_UNICODE), json_encode($recs, JSON_UNESCAPED_UNICODE), $shareToken, $_SERVER['REMOTE_ADDR']??'', $_SERVER['HTTP_USER_AGENT']??'']);
            $rid = $db->lastInsertId();
            $this->rememberReportId((int)$rid);
        } catch (\Throwable $e) {
            Logger::error('audit: failed to save audit_reports row', ['message' => $e->getMessage()]);
        }
        
        // Create lead from audit — proper instantiation, email verified by controller
        try {
            $leadSvc = new \App\Services\LeadService(new \App\Repositories\LeadRepository());
            $leadSvc->captureFromWebsite(
                'בדיקת אתר - ' . parse_url($url, PHP_URL_HOST),
                '',
                $email,
                'audit',
                'בדיקת אתר',
                $url . ' | ציון: ' . $ovr . '/100'
            );
        } catch (\Throwable $e) {
            Logger::error('audit: failed to capture lead', ['message' => $e->getMessage()]);
        }

        // The form labels this field "אימייל לקבלת הדוח" and requires it, so send the
        // results without waiting for the user to press the button.
        $emailed = $this->sendReportEmail([
            'id' => (int)$rid,
            'url' => $url,
            'overall_score' => $ovr,
            'created_at' => date('Y-m-d H:i:s'),
            'share_token' => $shareToken,
        ], $results, $recs, $email);

        $parsed = parse_url($url);
        $urlInfo = ['url' => $url, 'protocol' => $parsed['scheme'] ?? '', 'domain' => $parsed['host'] ?? '', 'tld' => substr((string)strrchr($parsed['host'] ?? '', '.'), 1) ?: '', 'has_www' => str_starts_with($parsed['host'] ?? '', 'www.'), 'path' => $parsed['path'] ?? ''];
        die(json_encode(['success'=>true,'overall'=>$ovr,'reportId'=>$rid,'responseTime'=>$rt,'emailed'=>$emailed,'urlInfo'=>$urlInfo,'results'=>$results,'recommendations'=>$recs], JSON_UNESCAPED_UNICODE));
    }

    /**
     * Report IDs this session produced.
     *
     * audit_reports.id is a sequential integer on unauthenticated routes, so
     * without this anyone could post a guessed id and have someone else's report
     * emailed to an address of their choosing — a data leak and a way to send mail
     * from our SMTP account. Ownership is per-session: you can act on a report if
     * your browser is the one that ran the scan.
     */
    private function ownedReportIds(): array
    {
        $ids = Session::get('audit_report_ids', []);
        return is_array($ids) ? array_map('intval', $ids) : [];
    }

    private function rememberReportId(int $id): void
    {
        if ($id <= 0) return;
        $ids = $this->ownedReportIds();
        if (in_array($id, $ids, true)) return;
        $ids[] = $id;
        // Bounded — a session has no reason to hoard scans
        Session::set('audit_report_ids', array_slice($ids, -20));
    }

    private function ownsReport(int $id): bool
    {
        return $id > 0 && in_array($id, $this->ownedReportIds(), true);
    }

    /**
     * May the current request read this report?
     *
     * Either the session produced it, or the request carries the row's share_token
     * — the case that keeps the link in the report email working on another device.
     * Rows predating the migration have no token and are session-only.
     */
    private function canReadReport(array $report): bool
    {
        if ($this->ownsReport((int)($report['id'] ?? 0))) {
            return true;
        }
        $token = (string)($report['share_token'] ?? '');
        $given = (string)($_GET['t'] ?? '');
        return $token !== '' && $given !== '' && hash_equals($token, $given);
    }

    /** Report link for emails: carries the token so it opens outside this session */
    private function reportLink(array $report): string
    {
        $link = $this->request->getBaseUrl() . '/audit/pdf/' . (int)($report['id'] ?? 0);
        $token = (string)($report['share_token'] ?? '');
        return $token !== '' ? $link . '?t=' . urlencode($token) : $link;
    }

    /** Send 6-digit verification code to email */
    public function sendCode(): void
    {
        $email = $_POST['email'] ?? '';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            die(json_encode(['success' => false, 'error' => 'נא להזין אימייל תקין']));
        }
        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Session::set('audit_verify_code', $code);
        Session::set('audit_verify_email', $email);
        
        try {
            Mailer::send($email, 
                'קוד אימות — LandingFlow בדיקת אתר',
                "קוד האימות שלך הוא: $code\n\nהקוד תקף למשך 10 דקות.\n\nבברכה,\nצוות LandingFlow"
            );
        } catch (\Throwable $e) {}
        
        die(json_encode(['success' => true, 'message' => 'קוד אימות נשלח לאימייל שלך']));
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

    public function pdf(string $id): string
    {
        $db = Database::getInstance()->getConnection();
        $r = $db->prepare("SELECT * FROM audit_reports WHERE id = ?"); $r->execute([$id]);
        $report = $r->fetch(\PDO::FETCH_ASSOC);
        if (!$report) throw new \App\Core\Exceptions\NotFoundException();
        // Same 404 for "no such report" and "not yours" — do not confirm which ids exist
        if (!$this->canReadReport($report)) throw new \App\Core\Exceptions\NotFoundException();
        $data = json_decode($report['full_report'] ?? '{}', true);
        header("Content-Type: text/html; charset=utf-8");
        ob_start();
        ?><!DOCTYPE html><html dir="rtl"><head><meta charset="UTF-8"><title>דוח ביקורת — <?= htmlspecialchars($report['url']) ?></title>
        <style>body{font-family:Arial,sans-serif;direction:rtl;padding:20px;max-width:800px;margin:0 auto;color:#222}h1{color:#5B47E0;font-size:1.8rem;border-bottom:2px solid #5B47E0;padding-bottom:8px}h2{font-size:1.2rem;margin-top:24px;color:#4634B8}.score{font-size:3rem;font-weight:bold;color:#5B47E0;text-align:center;margin:20px 0}table{width:100%;border-collapse:collapse;margin:12px 0}td,th{padding:8px;border:1px solid #ddd;text-align:right}th{background:#F4F5FA;font-weight:bold}.pass{color:#1FA15C}.fail{color:#FF4D8D}@media print{body{padding:0}}</style></head><body>
        <h1>🔍 דוח ביקורת — <?= htmlspecialchars($report['url']) ?></h1>
        <p style="color:#666">תאריך: <?= $report['created_at'] ?></p>
        <div class="score"><?= $report['overall_score'] ?>/100</div>
        <?php foreach ($data as $cat => $d): ?><h2><?= htmlspecialchars($d['label'] ?? $cat) ?> (<?= $d['score'] ?>/100)</h2><table><tr><th>בדיקה</th><th>תוצאה</th><th>סטטוס</th></tr>
        <?php foreach (($d['checks'] ?? []) as $ck): ?><tr><td><?= htmlspecialchars($ck['label']) ?></td><td><?= htmlspecialchars($ck['value'] ?? '-') ?></td><td class="<?= $ck['passed'] ? 'pass' : 'fail' ?>"><?= $ck['passed'] ? '✔' : '✘' ?></td></tr><?php endforeach; ?></table><?php endforeach; ?>
        <p style="margin-top:40px;color:#999">דוח זה הופק על ידי LandingFlow — landingflow.co.il</p>
        </body></html><?php
        return ob_get_clean();
    }

    /**
     * Stream the report as a downloadable PDF — the same document the email
     * attaches, so the two can never disagree.
     *
     * /audit/pdf/{id} stays as the printable HTML view; this is the file download.
     */
    public function download(string $id): void
    {
        $db = Database::getInstance()->getConnection();
        $r = $db->prepare("SELECT * FROM audit_reports WHERE id = ?");
        $r->execute([(int)$id]);
        $report = $r->fetch(\PDO::FETCH_ASSOC);
        if (!$report) throw new \App\Core\Exceptions\NotFoundException();
        // 404 rather than 403 — no reason to confirm which ids exist
        if (!$this->canReadReport($report)) throw new \App\Core\Exceptions\NotFoundException();
        $data = json_decode($report['full_report'] ?? '{}', true) ?: [];

        $tmpDir = STORAGE_PATH . '/tmp';
        if (!is_dir($tmpDir)) mkdir($tmpDir, 0755, true);
        $pdfPath = $tmpDir . '/audit_dl_' . (int)$report['id'] . '_' . bin2hex(random_bytes(4)) . '.pdf';

        try {
            $this->generateReportPdf($report, $data, $pdfPath);
        } catch (\Throwable $e) {
            Logger::error('audit: download PDF generation failed', ['message' => $e->getMessage()]);
            if (file_exists($pdfPath)) @unlink($pdfPath);
            // Better the printable view than a dead end — carry the token through,
            // or a link-based caller lands on a 404
            $given = (string)($_GET['t'] ?? '');
            $this->redirect('audit/pdf/' . (int)$report['id'] . ($given !== '' ? '?t=' . urlencode($given) : ''));
            return;
        }

        $host = preg_replace('/[^A-Za-z0-9.-]/', '', (string)parse_url((string)$report['url'], PHP_URL_HOST)) ?: 'report';
        $date = date('Y-m-d', strtotime((string)$report['created_at']) ?: time());
        $filename = "landingflow-audit-{$host}-{$date}.pdf";

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($pdfPath));
        header('Cache-Control: private, max-age=0, must-revalidate');
        readfile($pdfPath);
        @unlink($pdfPath);
        exit;
    }

    /** Generate the report as a PDF and email it to the address the user entered */
    public function report(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $id = (int)($_POST['reportId'] ?? 0);
        $email = trim($_POST['email'] ?? '');
        if ($id <= 0) {
            http_response_code(422);
            die(json_encode(['success' => false, 'error' => 'דוח לא תקין']));
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            die(json_encode(['success' => false, 'error' => 'נא להזין אימייל תקין']));
        }
        // The recipient is caller-supplied by design, so the report must be one this
        // browser actually produced — otherwise this endpoint mails arbitrary reports
        // to arbitrary addresses.
        if (!$this->ownsReport($id)) {
            http_response_code(403);
            die(json_encode(['success' => false, 'error' => 'ניתן לשלוח רק דוח שהופק בדפדפן הזה. הריצו את הבדיקה מחדש.'], JSON_UNESCAPED_UNICODE));
        }

        $db = Database::getInstance()->getConnection();
        $r = $db->prepare("SELECT * FROM audit_reports WHERE id = ?"); $r->execute([$id]);
        $report = $r->fetch(\PDO::FETCH_ASSOC);
        if (!$report) {
            http_response_code(404);
            die(json_encode(['success' => false, 'error' => 'דוח לא נמצא']));
        }
        $data = json_decode($report['full_report'] ?? '{}', true) ?: [];
        $recs = json_decode($report['recommendations'] ?? '[]', true) ?: [];

        if (!$this->sendReportEmail($report, $data, $recs, $email)) {
            http_response_code(500);
            die(json_encode(['success' => false, 'error' => 'שליחת הדוח נכשלה'], JSON_UNESCAPED_UNICODE));
        }

        die(json_encode(['success' => true, 'message' => 'הדוח נשלח לאימייל ' . $email], JSON_UNESCAPED_UNICODE));
    }

    /**
     * Email the audit results: the full findings inline in the body, plus the same
     * report as a PDF attachment. Shared by the automatic send at the end of a scan
     * and by the "send to email" button.
     *
     * A failed PDF is not fatal — the body already carries every check, so the mail
     * still goes out with the results in it.
     */
    private function sendReportEmail(array $report, array $data, array $recs, string $email): bool
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
                $this->buildReportEmailBody($report, $data, $recs, $pdfPath !== ''),
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
    private function buildReportEmailBody(array $report, array $data, array $recs, bool $hasPdf = true): string
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
        if (!empty($report['id'])) {
            $lines[] = 'לצפייה בדוח בדפדפן: ' . $this->reportLink($report);
        }
        $lines[] = '';
        $lines[] = 'בברכה,';
        $lines[] = 'צוות LandingFlow';

        return implode("\n", $lines);
    }

    private function generateReportPdf(array $report, array $data, string $outputPath): void
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

    public function adminIndex(): string
    {
        $db = Database::getInstance()->getConnection();
        $search = $_GET['q'] ?? '';
        $params = [];
        $where = '';
        if (!empty($search)) {
            $where = " WHERE (url LIKE :q OR CAST(id AS CHAR) LIKE :q2)";
            $params['q'] = "%$search%";
            $params['q2'] = "%$search%";
        }
        $stmt = $db->prepare("SELECT id, url, overall_score, created_at FROM audit_reports $where ORDER BY created_at DESC LIMIT 100");
        $stmt->execute($params);
        $reports = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $this->render('admin/audit-reports', [
            'pageTitle' => 'ביקורות — LandingFlow',
            'reports' => $reports,
            'search' => $search,
        ]);
    }

    public function adminShow(string $id): string
    {
        $db = Database::getInstance()->getConnection();
        $r = $db->prepare("SELECT * FROM audit_reports WHERE id = ?");
        $r->execute([$id]);
        $report = $r->fetch(\PDO::FETCH_ASSOC);
        if (!$report) throw new \App\Core\Exceptions\NotFoundException();
        $report['full_report_decoded'] = json_decode($report['full_report'] ?? '{}', true);
        $report['recommendations'] = json_decode($report['recommendations'] ?? '[]', true);
        return $this->render('admin/audit-report-detail', [
            'pageTitle' => 'ביקורת #' . $report['id'] . ' — LandingFlow',
            'report' => $report,
        ]);
    }

    /** JSON endpoint for inline detail panel */
    public function adminDetail(string $id): void
    {
        $db = Database::getInstance()->getConnection();
        $r = $db->prepare("SELECT * FROM audit_reports WHERE id = ?");
        $r->execute([$id]);
        $report = $r->fetch(\PDO::FETCH_ASSOC);
        if (!$report) { http_response_code(404); echo json_encode(['error' => 'Not found']); exit; }
        $report['full_report_decoded'] = json_decode($report['full_report'] ?? '{}', true);
        $report['recommendations'] = json_decode($report['recommendations'] ?? '[]', true);
        header('Content-Type: application/json');
        echo json_encode($report, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Delete a report */
    public function adminDelete(string $id): void
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM audit_reports WHERE id = ?");
        $stmt->execute([(int)$id]);
        Session::flash('flash', ['type' => 'success', 'message' => 'הדוח נמחק בהצלחה.']);
        $this->redirect('admin/audit-reports');
    }
}
