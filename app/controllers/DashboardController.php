<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Services\ScanService;
use App\Repositories\AuditReportRepository;

class DashboardController extends Controller
{
    private AuditReportRepository $reportRepo;

    public function __construct(?Request $request = null)
    {
        parent::__construct($request);
        $this->reportRepo = new AuditReportRepository();
    }

    public function index(): string
    {
        $db = \App\Core\Database::getInstance()->getConnection();
        $total = (int) $db->query("SELECT COUNT(*) as c FROM audit_reports")->fetchColumn();
        $avg = round((float) $db->query("SELECT AVG(overall_score) as a FROM audit_reports")->fetchColumn(), 1);
        $recent = $db->query("SELECT * FROM audit_reports ORDER BY created_at DESC LIMIT 10")->fetchAll(\PDO::FETCH_ASSOC);

        return $this->render('dashboard/index', [
            'pageTitle' => 'Dashboard � LandingFlow',
            'total' => $total,
            'avg' => $avg,
            'recent' => $recent,
        ]);
    }

    public function report(string $id): string
    {
        $report = $this->reportRepo->findById((int) $id);
        if (!$report) throw new \App\Core\Exceptions\NotFoundException('Report not found');
        $report['full_report'] = json_decode($report['full_report'], true);
        $report['recommendations'] = json_decode($report['recommendations'], true);

        return $this->render('dashboard/report', [
            'pageTitle' => "Report #$id � LandingFlow",
            'report' => $report,
        ]);
    }

    public function leadReports(string $leadId): string
    {
        $reports = $this->reportRepo->findByLeadId((int) $leadId);
        return $this->render('dashboard/lead', [
            'pageTitle' => 'Lead Reports � LandingFlow',
            'reports' => $reports,
            'leadId' => $leadId,
        ]);
    }
}