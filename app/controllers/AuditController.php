<?php
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Database;
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
        $html = @file_get_contents($url, false, $ctx);
        if ($html === false) $html = '';
        $headers = @get_headers($url, 1);
        if (!is_array($headers)) $headers = [];
        $start = microtime(true);
        $rt = round((microtime(true) - $start) * 1000);
        $seo = $this->seoC($url, $html);
        $sec = $this->secC($url, $headers, $html);
        $leg = $this->legC($url, $html);
        $a11y = $this->a11yC($html);
        $perf = $this->perfC($rt, $html);
        $spam = $this->spamC($url, $html, $headers);
        $all = [$seo['score'], $sec['score'], $leg['score'], $a11y['score'], $perf['score'], $spam['score']];
        $ovr = round(array_sum($all) / count($all));
        $rid = 0;
        try {
            $db = Database::getInstance()->getConnection();
            $recs = [];
            foreach ([$seo,$sec,$leg,$a11y,$perf,$spam] as $cat)
                foreach (($cat['checks']??[]) as $c)
                    if (!$c['passed']) $recs[] = ['category'=>$cat['label'],'check'=>$c['label'],'action'=>'יש לתקן: '.$c['label']];
            $db->prepare("INSERT INTO audit_reports (url,overall_score,seo_score,security_score,legal_score,accessibility_score,performance_score,spam_score,total_checks,passed_checks,failed_checks,full_report,recommendations,status,ip_address,user_agent,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,'completed',?,?,NOW())")
               ->execute([$url, $ovr, $seo['score'], $sec['score'], $leg['score'], $a11y['score'], $perf['score'], $spam['score'], 30, (int)round($ovr/100*30), 30-(int)round($ovr/100*30), json_encode(['seo'=>$seo,'security'=>$sec,'legal'=>$leg,'accessibility'=>$a11y,'performance'=>$perf,'spam'=>$spam], JSON_UNESCAPED_UNICODE), json_encode($recs, JSON_UNESCAPED_UNICODE), $_SERVER['REMOTE_ADDR']??'', $_SERVER['HTTP_USER_AGENT']??'']);
            $rid = $db->lastInsertId();
        } catch (\Throwable $e) {}
        
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
        } catch (\Throwable $e) {}

        $parsed = parse_url($url);
        $urlInfo = ['url' => $url, 'protocol' => $parsed['scheme'] ?? '', 'domain' => $parsed['host'] ?? '', 'tld' => substr((string)strrchr($parsed['host'] ?? '', '.'), 1) ?: '', 'has_www' => str_starts_with($parsed['host'] ?? '', 'www.'), 'path' => $parsed['path'] ?? ''];
        die(json_encode(['success'=>true,'overall'=>$ovr,'reportId'=>$rid,'responseTime'=>$rt,'urlInfo'=>$urlInfo,'results'=>['seo'=>$seo,'security'=>$sec,'legal'=>$leg,'accessibility'=>$a11y,'performance'=>$perf,'spam'=>$spam],'recommendations'=>$recs], JSON_UNESCAPED_UNICODE));
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
    private function seoC($u,$h):array{$c=[];preg_match("/<title[^>]*>(.*?)<\/title>/si",$h,$m);$t=trim(strip_tags($m[1]??""));$c["title"]=["passed"=>!empty($t),"label"=>"כותרת (Title)","value"=>$t?mb_substr($t,0,80):"חסר","detail"=>mb_strlen($t)." תווים"];preg_match("/<meta[^>]+name=.description.[^>]+content=.([^.]*)./si",$h,$m);$d=$m[1]??"";$c["desc"]=["passed"=>!empty($d),"label"=>"Meta Description","value"=>$d?mb_substr($d,0,80):"חסר","detail"=>mb_strlen($d)." תווים"];preg_match("/<h1[^>]*>(.*?)<\/h1>/si",$h,$m);$h1=trim(strip_tags($m[1]??""));$c["h1"]=["passed"=>!empty($h1),"label"=>"כותרת H1","value"=>$h1?: "חסר","detail"=>$h1?"נמצא":"לא נמצא"];$rb=@file_get_contents($u."/robots.txt");$c["robots"]=["passed"=>$rb!==false,"label"=>"Robots.txt","value"=>$rb!==false?"קיים":"לא נמצא","detail"=>$rb!==false?substr($rb,0,120):"-"];$sm=@file_get_contents($u."/sitemap.xml");$c["sitemap"]=["passed"=>$sm!==false,"label"=>"Sitemap.xml","value"=>$sm!==false?"קיים":"לא נמצא","detail"=>$sm!==false?substr(strip_tags($sm),0,120):"-"];preg_match("/<link[^>]+rel=.canonical.[^>]+href=.[^\"\047 ]+./si",$h,$m);$can=$m[0]??"";$c["canonical"]=["passed"=>!empty($can),"label"=>"Canonical URL","value"=>$can?htmlspecialchars($can):"לא נמצא"];$c["og"]=["passed"=>strpos($h,"og:title")!==false||strpos($h,"og:description")!==false,"label"=>"Open Graph","value"=>(strpos($h,"og:title")!==false?"og:title ":"").(strpos($h,"og:description")!==false?"og:desc ":"").(strpos($h,"og:image")!==false?"og:image":""),"detail"=>(strpos($h,"og:title")!==false||strpos($h,"og:description")!==false)?"מוגדר":"לא מוגדר"];$p=count(array_filter($c,fn($x)=>$x["passed"]));return["score"=>round($p/count($c)*100),"checks"=>$c,"label"=>"SEO"];}
    private function secC($u,$hdr,$h):array{$c=[];$https=str_starts_with($u,"https://");$c["https"]=["passed"=>$https,"label"=>"HTTPS","value"=>$https?"✅ HTTPS":"❌ HTTP","detail"=>$https?"מוצפן":"לא מוצפן"];$c["ssl"]=["passed"=>$https,"label"=>"SSL","value"=>$https?"✅ תעודה":"❌ אין"];$sh=["Strict-Transport-Security"=>"HSTS","X-Frame-Options"=>"X-Frame","X-Content-Type-Options"=>"X-Content","Content-Security-Policy"=>"CSP","X-XSS-Protection"=>"XSS"];foreach($sh as $k=>$l){$v="";$f=false;if(is_array($hdr))foreach($hdr as $hk=>$hv)if(strcasecmp($hk,$k)===0){$f=true;$v=is_array($hv)?implode(", ",$hv):(string)$hv;break;}$c[strtolower($k)]=["passed"=>$f,"label"=>$l,"value"=>$f?"✅ קיים":"❌ חסר","detail"=>$f?mb_substr($v,0,100):"-","impact"=>$f?"":"יש לשפר אבטחה"];}$mx=false;if($https&&$h){$mx=preg_match("/src=.http:\/\//",$h);}$c["mixed"]=["passed"=>!$mx,"label"=>"Mixed Content","value"=>$mx?"⚠️ נמצא":"✅ תקין","detail"=>$mx?"תוכן HTTP באתר HTTPS":"אין"];$p=count(array_filter($c,fn($x)=>$x["passed"]));return["score"=>round($p/count($c)*100),"checks"=>$c,"label"=>"אבטחה"];}
    private function legC($u,$h):array{$c=[];$c["privacy"]=["passed"=>stripos($h,"privacy")!==false||stripos($h,"פרטיות")!==false,"label"=>"מדיניות פרטיות","value"=>(stripos($h,"privacy")!==false||stripos($h,"פרטיות")!==false)?"נמצא":"לא נמצא"];$c["terms"]=["passed"=>stripos($h,"terms")!==false||stripos($h,"תנאי")!==false,"label"=>"תקנון / תנאי שימוש","value"=>(stripos($h,"terms")!==false||stripos($h,"תנאי")!==false)?"נמצא":"לא נמצא"];$c["access"]=["passed"=>stripos($h,"accessibility")!==false||stripos($h,"נגישות")!==false,"label"=>"הצהרת נגישות","value"=>(stripos($h,"accessibility")!==false||stripos($h,"נגישות")!==false)?"נמצא":"לא נמצא"];$c["cookies"]=["passed"=>stripos($h,"cookie")!==false||stripos($h,"עוגיות")!==false,"label"=>"הודעת Cookies","value"=>(stripos($h,"cookie")!==false||stripos($h,"עוגיות")!==false)?"נמצא":"לא נמצא"];$c["consent"]=["passed"=>preg_match("/checkbox.*consent|consent.*checkbox|אישור.*מדיניות/i",$h)===1,"label"=>"אישור בטפסים","value"=>preg_match("/checkbox.*consent|consent.*checkbox/i",$h)===1?"נמצא":"לא נמצא"];$hp=preg_match("/0\d[-\s]?\d{6,}/",$h);$he=preg_match("/mailto:|[a-z0-9._%+-]+@[a-z0-9.-]+/",$h);$ha=stripos($h,"רחוב")!==false||stripos($h,"כתובת")!==false;$c["footer"]=["passed"=>$hp&&$he&&$ha,"label"=>"פרטי עסק (📞📧📍)","value"=>($hp?"📞":"").($he?" 📧":"").($ha?" 📍":"")];$p=count(array_filter($c,fn($x)=>$x["passed"]));return["score"=>round($p/count($c)*100),"checks"=>$c,"label"=>"משפטי ותאימות"];}
    private function a11yC($h):array{$c=[];preg_match_all("/<img[^>]+>/si",$h,$imgs);preg_match_all("/<img[^>]+alt=.[^.]*./si",$h,$a);$tc=count($imgs[0]??[]);$ac=count($a[0]??[]);$c["alt"]=["passed"=>$tc===0||$ac>=$tc*0.8,"label"=>"Alt לתמונות","value"=>$tc>0?"$ac/$tc":"אין","detail"=>"$ac מתוך $tc"];$c["lang"]=["passed"=>(bool)preg_match("/<html[^>]+lang=./",$h),"label"=>"שפת HTML","value"=>(bool)preg_match("/<html[^>]+lang=./",$h)?"✅ מוגדר":"❌ חסר"];$c["viewport"]=["passed"=>strpos($h,"viewport")!==false,"label"=>"Viewport Meta","value"=>strpos($h,"viewport")!==false?"✅ קיים":"❌ חסר"];$c["aria"]=["passed"=>strpos($h,"aria-")!==false,"label"=>"ציוני ARIA","value"=>strpos($h,"aria-")!==false?"✅ קיימים":"❌ חסרים"];$c["headings"]=["passed"=>(bool)preg_match("/<h[1-6]/",$h),"label"=>"מבנה כותרות","value"=>(bool)preg_match("/<h[1-6]/",$h)?"✅ קיים":"❌ חסר"];$c["level"]=["passed"=>stripos($h,"wcag")!==false||stripos($h,"aa")!==false,"label"=>"רמת נגישות (WCAG)","value"=>stripos($h,"wcag")!==false?"מוצהר":"לא מוצהר"];$c["contact"]=["passed"=>preg_match("/נגישות.*0\d|accessibility.*0\d/",$h)===1,"label"=>"רכז נגישות","value"=>preg_match("/נגישות.*0\d/",$h)===1?"✅ יש":"❌ אין"];$p=count(array_filter($c,fn($x)=>$x["passed"]));return["score"=>round($p/count($c)*100),"checks"=>$c,"label"=>"נגישות"];}
    private function perfC($rt,$h):array{$c=[];$c["resp"]=["passed"=>$rt<2000,"label"=>"זמן תגובה","value"=>"$rt ms","detail"=>$rt."ms (סף: 2000ms)"];$hasGA=strpos($h,"google-analytics")!==false||strpos($h,"gtag")!==false||strpos($h,"googletagmanager")!==false;$hasPixel=strpos($h,"fbq")!==false;$hasHotjar=strpos($h,"hotjar")!==false;$hasClarity=strpos($h,"clarity")!==false;$tr=($hasGA?"GA ":"").($hasPixel?"FB ":"").($hasHotjar?"HJ ":"").($hasClarity?"MC ":"");$c["trackers"]=["passed"=>true,"label"=>"Tracking Scripts","value"=>$tr?: "לא זוהו","detail"=>$tr?"נמצאו עוקבים":"אין"];$c["cookie_ok"]=["passed"=>($hasGA||$hasPixel)?(stripos($h,"cookie")!==false||stripos($h,"עוגיות")!==false):true,"label"=>"Cookies Consent","value"=>($hasGA||$hasPixel)?(stripos($h,"cookie")!==false?"✅ יש":"❌ חסר!"):"N/A","detail"=>($hasGA||$hasPixel)?"חובה כשיש trackers":"אין trackers"];$p=count(array_filter($c,fn($x)=>$x["passed"]));return["score"=>round($p/count($c)*100),"checks"=>$c,"label"=>"ביצועים"];}
    private function spamC($url,$html,$headers=[]):array{$c=[];$s=new SpamScanner();$r=$s->scan($html,$url,$headers);$rl=$r->riskLevel();$lv=match($rl){'LOW'=>'נמוך','MEDIUM'=>'בינוני','HIGH'=>'גבוה','CRITICAL'=>'קריטי',default=>$rl};$ld=match($rl){'LOW'=>'האתר נקי','MEDIUM'=>'אותות ספאם קלים','HIGH'=>'אותות ספאם משמעותיים',default=>'סיכון קריטי — דרוש טיפול מיידי'};$c["risk"]=["passed"=>$rl==='LOW',"label"=>"רמת סיכון ספאם","value"=>$lv,"detail"=>"ציון ספאם: ".$r->spamScore()."/100 — ".$ld];$c["hidden"]=["passed"=>$r->hiddenContentScore>=90,"label"=>"תוכן מוסתר","value"=>$r->hiddenContentScore>=90?"✅ נקי":"⚠️ נמצא","detail"=>"ציון: ".$r->hiddenContentScore."/100"];$c["content"]=["passed"=>$r->contentScore>=80,"label"=>"איכות תוכן","value"=>$r->contentScore>=80?"✅ תקין":"⚠️ בעיות","detail"=>"ציון: ".$r->contentScore."/100"];$c["links"]=["passed"=>$r->linksScore>=80,"label"=>"קישורים חשודים","value"=>$r->linksScore>=80?"✅ תקין":"⚠️ חשודים","detail"=>"ציון: ".$r->linksScore."/100"];$c["domain"]=["passed"=>$r->domainScore>=80,"label"=>"אמינות דומיין","value"=>$r->domainScore>=80?"✅ אמין":"⚠️ בעיות","detail"=>"ציון: ".$r->domainScore."/100"];$c["email"]=["passed"=>$r->emailScore>=80,"label"=>"אבטחת אימייל (SPF/DKIM/DMARC)","value"=>$r->emailScore>=80?"✅ מאובטח":"⚠️ חסר","detail"=>"ציון: ".$r->emailScore."/100"];$c["form"]=["passed"=>$r->formScore>=80,"label"=>"הגנת טפסים","value"=>$r->formScore>=80?"✅ מוגן":"⚠️ פגיע","detail"=>"ציון: ".$r->formScore."/100"];$p=count(array_filter($c,fn($x)=>$x["passed"]));return["score"=>round($p/count($c)*100),"checks"=>$c,"label"=>"ספאם ואמון"];}

    public function pdf(string $id): string
    {
        $db = Database::getInstance()->getConnection();
        $r = $db->prepare("SELECT * FROM audit_reports WHERE id = ?"); $r->execute([$id]);
        $report = $r->fetch(\PDO::FETCH_ASSOC);
        if (!$report) throw new \App\Core\Exceptions\NotFoundException();
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
