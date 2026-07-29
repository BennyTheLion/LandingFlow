<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Database;
use App\Services\MonitoringService;

class MonitoringController extends Controller
{
    private function db() { return Database::getInstance()->getConnection(); }

    public function index(): string
    {
        $monitor = new MonitoringService();

        // Load from DB
        try {
            $dbSites = $this->db()->query(
                "SELECT * FROM monitoring_websites ORDER BY status ASC, last_checked_at DESC"
            )->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            $dbSites = [];
        }

        // Load demo sites from config
        $demoConfig = require CONFIG_PATH . '/demo.sites.php';
        $allUrls = array_column($dbSites, 'url');
        $sites = [];

        // Only run live checks on DB-monitored sites (with IDs)
        foreach ($dbSites as $s) {
            $s['source'] = 'monitored';
            $sites[] = $s;
        }
        $monitoredOnly = $sites; // sites to actually check

        // Demo sites just display static info — no live checks
        foreach ($demoConfig as $d) {
            if (!in_array($d['url'], $allUrls)) {
                $sites[] = ['id' => null, 'name' => $d['name'], 'url' => $d['url'],
                    'status' => 'online', 'check_interval' => 60, 'uptime_percentage' => 100,
                    'response_time_ms' => null, 'last_checked_at' => null, 'source' => 'demo',
                    'seo_score' => null, 'security_score' => null, 'ssl_valid' => null];
            }
        }

        // Run real checks only on monitored sites (cached 5min per site, 5s timeout)
        $checkedMonitored = $monitor->checkAll($monitoredOnly, 5);

        // Merge: monitored + demo (demo always after monitored)
        $merged = array_merge($checkedMonitored, array_slice($sites, count($monitoredOnly)));

        // Read flash messages for the view
        $flashMsg = null;
        if ($msg = \App\Core\Session::flash('success')) {
            $flashMsg = ['type' => 'success', 'message' => $msg];
        } elseif ($msg = \App\Core\Session::flash('error')) {
            $flashMsg = ['type' => 'error', 'message' => $msg];
        } elseif ($msg = \App\Core\Session::flash('flash')) {
            $flashMsg = $msg;
        }

        return $this->render('monitoring/index', [
            'pageTitle' => 'ניטור — LandingFlow',
            'sites' => $merged,
            'flashMsg' => $flashMsg,
        ]);
    }

    public function add(): never
    {
        if (!$this->validateCsrf()) { Session::flash('error', 'שגיאה'); $this->redirect('admin/monitoring'); }
        $data = [
            'url' => $this->request->input('url'),
            'name' => $this->request->input('name'),
            'check_interval' => (int)$this->request->input('check_interval', 60),
            'status' => 'online', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ];
        if (empty($data['url']) || empty($data['name'])) { Session::flash('error', 'נא למלא שם וכתובת.'); $this->redirect('admin/monitoring'); }
        $cols = implode(', ', array_keys($data));
        $plh = implode(', ', array_fill(0, count($data), '?'));
        $this->db()->prepare("INSERT INTO monitoring_websites ($cols) VALUES ($plh)")->execute(array_values($data));
        Session::flash('success', 'האתר נוסף לניטור.');
        $this->redirect('admin/monitoring');
    }

    public function show(string $id): string
    {
        $stmt = $this->db()->prepare("SELECT * FROM monitoring_websites WHERE id = ?");
        $stmt->execute([$id]); $site = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$site) throw new \App\Core\Exceptions\NotFoundException();
        $logs = $this->db()->prepare("SELECT * FROM monitoring_logs WHERE website_id = ? ORDER BY checked_at DESC LIMIT 30");
        $logs->execute([$id]);
        return $this->render('monitoring/show', [
            'pageTitle' => $site['name'] . ' — ניטור', 'site' => $site, 'logs' => $logs->fetchAll(\PDO::FETCH_ASSOC),
        ]);
    }

    /** Run check now and redirect back */
    public function checkNow(string $id): never
    {
        $stmt = $this->db()->prepare("SELECT * FROM monitoring_websites WHERE id = ?");
        $stmt->execute([$id]); $site = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$site) { $this->redirect('admin/monitoring'); }

        $monitor = new MonitoringService();
        $c = $monitor->checkSingle($site['url']);

        // Save log
        $this->db()->prepare(
            "INSERT INTO monitoring_logs (website_id, status, response_time_ms, checked_at) VALUES (?,?,?,NOW())"
        )->execute([$site['id'], $c['status'], $c['response_time_ms'] ?? 0]);

        // Update site
        $this->db()->prepare(
            "UPDATE monitoring_websites SET status=?, response_time_ms=?, last_checked_at=NOW(), updated_at=NOW() WHERE id=?"
        )->execute([$c['status'], $c['response_time_ms'] ?? 0, $id]);

        Session::flash('success', 'הבדיקה הושלמה.');
        $this->redirect("admin/monitoring");
    }

    public function update(string $id): never
    {
        if (!$this->validateCsrf()) { $this->redirect("admin/monitoring/$id"); }
        $fields = ['name','url','check_interval','status'];
        $sets = []; $vals = [];
        foreach ($fields as $f) {
            $v = $this->request->input($f);
            if ($v !== null) { $sets[] = "`$f` = ?"; $vals[] = $v; }
        }
        $vals[] = $id;
        if ($sets) $this->db()->prepare("UPDATE monitoring_websites SET ".implode(', ',$sets).", updated_at=NOW() WHERE id=?")->execute($vals);
        Session::flash('success', 'עודכן.');
        $this->redirect("admin/monitoring/$id");
    }

    /** Delete a monitored site */
    public function delete(string $id): never
    {
        $this->db()->prepare("DELETE FROM monitoring_logs WHERE website_id = ?")->execute([(int)$id]);
        $this->db()->prepare("DELETE FROM monitoring_websites WHERE id = ?")->execute([(int)$id]);
        Session::flash('success', 'האתר הוסר מניטור.');
        $this->redirect('admin/monitoring');
    }

    /** Request recommended solutions */
    public function solutions(string $id): string
    {
        $stmt = $this->db()->prepare("SELECT * FROM monitoring_websites WHERE id = ?");
        $stmt->execute([$id]); $site = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$site) { $this->redirect('admin/monitoring'); }

        $monitor = new MonitoringService();
        $c = $monitor->checkSingle($site['url']);
        $recs = $monitor->generateRecommendations($c);

        return $this->render('monitoring/solutions', [
            'pageTitle' => 'המלצות — ' . $site['name'],
            'site' => $site,
            'recommendations' => $recs,
            'current' => $c,
        ]);
    }
}
