<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class AdminController extends Controller
{
    public function dashboard(): string
    {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        $stats = [
            'totalLeads' => (int) $conn->query("SELECT COUNT(*) FROM leads")->fetchColumn(),
            'activeProjects' => (int) $conn->query("SELECT COUNT(*) FROM website_projects WHERE status NOT IN ('delivered','cancelled')")->fetchColumn(),
            'monitoredSites' => (int) $conn->query("SELECT COUNT(*) FROM monitoring_websites WHERE status IN ('online','issues')")->fetchColumn(),
            'hostingAccounts' => (int) $conn->query("SELECT COUNT(*) FROM hosting_accounts WHERE status = 'active'")->fetchColumn(),
            'recentLeads' => $conn->query("SELECT name, company, status FROM leads ORDER BY created_at DESC LIMIT 5")->fetchAll(\PDO::FETCH_ASSOC),
            'recentProjects' => $conn->query("SELECT title, status FROM website_projects ORDER BY updated_at DESC LIMIT 5")->fetchAll(\PDO::FETCH_ASSOC),
        ];

        return $this->render('admin/dashboard', [
            'pageTitle' => 'דשבורד — LandingFlow',
            'stats' => $stats,
        ]);
    }
}
