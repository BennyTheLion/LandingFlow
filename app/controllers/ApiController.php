<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\ScanService;
use App\Repositories\AuditReportRepository;

class ApiController extends Controller
{
    private ScanService $scanService;
    private AuditReportRepository $reportRepo;

    public function __construct(?Request $request = null)
    {
        parent::__construct($request);
        $this->scanService = new ScanService();
        $this->reportRepo = new AuditReportRepository();
    }

    /**
     * POST /api/scan
     * Body: { "url": "https://...", "lead_id": null }
     */
    public function scan(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $url = $input['url'] ?? '';
        $leadId = $input['lead_id'] ?? null;

        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            $this->json(['success' => false, 'error' => 'Valid URL required'], 422);
        }

        $result = $this->scanService->runFullScan(
            $url,
            null,
            [],
            $leadId ? (int) $leadId : null,
            $this->request->getClientIp()
        );

        $this->json(['success' => true, 'data' => $result]);
    }

    /**
     * GET /api/report/{id}
     */
    public function report(string $id): void
    {
        $report = $this->reportRepo->findById((int) $id);
        if (!$report) {
            $this->json(['success' => false, 'error' => 'Report not found'], 404);
        }
        $report['full_report'] = json_decode($report['full_report'], true);
        $report['recommendations'] = json_decode($report['recommendations'], true);
        $this->json(['success' => true, 'data' => $report]);
    }

    /**
     * GET /api/leads/{id}/reports
     */
    public function leadReports(string $id): void
    {
        $reports = $this->reportRepo->findByLeadId((int) $id);
        $this->json(['success' => true, 'data' => $reports, 'count' => count($reports)]);
    }

    /**
     * GET /api/dashboard/summary
     */
    public function dashboard(): void
    {
        $db = \App\Core\Database::getInstance()->getConnection();
        $total = $db->query("SELECT COUNT(*) as c FROM audit_reports")->fetchColumn();
        $avg = $db->query("SELECT AVG(overall_score) as a FROM audit_reports")->fetchColumn();
        $recent = $db->query("SELECT * FROM audit_reports ORDER BY created_at DESC LIMIT 5")->fetchAll(\PDO::FETCH_ASSOC);

        $this->json([
            'success' => true,
            'data' => [
                'total_scans' => (int) $total,
                'average_score' => round((float) $avg, 1),
                'recent_scans' => $recent,
            ]
        ]);
    }
}