<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Database;

class HostingController extends Controller
{
    private function db() { return Database::getInstance()->getConnection(); }

    public function index(): string
    {
        $accounts = $this->db()->query("SELECT * FROM hosting_accounts ORDER BY expiration_date ASC")->fetchAll(\PDO::FETCH_ASSOC);
        return $this->render('hosting/index', ['pageTitle' => 'אחסון — ניהול', 'accounts' => $accounts]);
    }

    public function create(): string
    {
        return $this->render('hosting/form', ['pageTitle' => 'חשבון אחסון חדש', 'account' => null, 'csrf' => $this->getCsrfToken()]);
    }

    public function store(): string
    {
        if (!$this->validateCsrf()) { Session::flash('error', 'שגיאה'); $this->redirect('admin/hosting/create'); }
        $data = [
            'domain' => $this->request->input('domain'),
            'hosting_plan' => $this->request->input('hosting_plan'),
            'hosting_provider' => $this->request->input('hosting_provider', 'Hostinger'),
            'start_date' => $this->request->input('start_date'),
            'expiration_date' => $this->request->input('expiration_date'),
            'renewal_price' => $this->request->input('renewal_price') ?: null,
            'status' => 'active',
            'notes' => $this->request->input('notes'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $cols = implode(', ', array_keys($data));
        $plh = implode(', ', array_fill(0, count($data), '?'));
        $this->db()->prepare("INSERT INTO hosting_accounts ($cols) VALUES ($plh)")->execute(array_values($data));
        Session::flash('success', 'חשבון האחסון נוסף.');
        $this->redirect('admin/hosting');
    }

    public function show(string $id): string
    {
        $stmt = $this->db()->prepare("SELECT * FROM hosting_accounts WHERE id = ?");
        $stmt->execute([$id]);
        $account = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$account) throw new \App\Core\Exceptions\NotFoundException();
        return $this->render('hosting/show', ['pageTitle' => $account['domain'], 'account' => $account]);
    }

    public function update(string $id): string
    {
        if (!$this->validateCsrf()) { $this->redirect("admin/hosting/$id"); }
        $fields = ['domain','hosting_plan','hosting_provider','start_date','expiration_date','renewal_price','status','notes'];
        $sets = []; $vals = [];
        foreach ($fields as $f) {
            $v = $this->request->input($f);
            if ($v !== null) { $sets[] = "`$f` = ?"; $vals[] = $v; }
        }
        $vals[] = $id;
        if ($sets) {
            $this->db()->prepare("UPDATE hosting_accounts SET " . implode(', ', $sets) . ", updated_at = NOW() WHERE id = ?")->execute($vals);
        }
        Session::flash('success', 'עודכן.');
        $this->redirect("admin/hosting/$id");
    }
}
