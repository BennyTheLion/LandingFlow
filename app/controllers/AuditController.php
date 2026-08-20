<?php
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Logger;
use App\Services\Mailer;
use App\Core\Session;
use App\Services\LeadService;
use App\Services\SiteAuditReport;

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
        
        // The checks, scoring and report rendering live in SiteAuditReport so the Lead
        // Engine can email the identical report for a prospect
        $audit   = (new SiteAuditReport())->run($url);
        $url     = $audit['url'];
        $rt      = $audit['responseTime'];
        $results = $audit['results'];
        $ovr     = $audit['overall'];
        $recs    = $audit['recommendations'];
        $rid = 0;
        // Authorizes the report link in the email, which has to open outside this
        // session — see database/migrations/2026_08_19_audit_report_share_token.sql
        $shareToken = bin2hex(random_bytes(32));
        try {
            $db = Database::getInstance()->getConnection();
            $db->prepare("INSERT INTO audit_reports (url,overall_score,seo_score,security_score,legal_score,accessibility_score,performance_score,spam_score,total_checks,passed_checks,failed_checks,full_report,recommendations,status,share_token,ip_address,user_agent,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,'completed',?,?,?,NOW())")
               ->execute([$url, $ovr, $results['seo']['score'], $results['security']['score'], $results['legal']['score'], $results['accessibility']['score'], $results['performance']['score'], $results['spam']['score'], 30, (int)round($ovr/100*30), 30-(int)round($ovr/100*30), json_encode($results, JSON_UNESCAPED_UNICODE), json_encode($recs, JSON_UNESCAPED_UNICODE), $shareToken, $_SERVER['REMOTE_ADDR']??'', $_SERVER['HTTP_USER_AGENT']??'']);
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
        $reportRow = [
            'id' => (int)$rid,
            'url' => $url,
            'overall_score' => $ovr,
            'created_at' => date('Y-m-d H:i:s'),
            'share_token' => $shareToken,
        ];
        $emailed = (new SiteAuditReport())->sendReportEmail(
            $reportRow,
            $results,
            $recs,
            $email,
            $rid > 0 ? $this->reportLink($reportRow) : null
        );

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
            (new SiteAuditReport())->generateReportPdf($report, $data, $pdfPath);
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

        if (!(new SiteAuditReport())->sendReportEmail($report, $data, $recs, $email, $this->reportLink($report))) {
            http_response_code(500);
            die(json_encode(['success' => false, 'error' => 'שליחת הדוח נכשלה'], JSON_UNESCAPED_UNICODE));
        }

        die(json_encode(['success' => true, 'message' => 'הדוח נשלח לאימייל ' . $email], JSON_UNESCAPED_UNICODE));
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
