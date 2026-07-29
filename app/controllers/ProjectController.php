<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Database;

class ProjectController extends Controller
{
    private function db() { return Database::getInstance()->getConnection(); }

    public function index(): string
    {
        $projects = $this->db()->query("SELECT * FROM website_projects ORDER BY created_at DESC")->fetchAll(\PDO::FETCH_ASSOC);
        return $this->render('projects/index', ['pageTitle' => 'פרויקטים — ניהול', 'projects' => $projects]);
    }

    public function create(): string
    {
        return $this->render('projects/form', ['pageTitle' => 'פרויקט חדש', 'project' => null, 'csrf' => $this->getCsrfToken()]);
    }

    public function store(): string
    {
        if (!$this->validateCsrf()) { Session::flash('error', 'שגיאת אבטחה'); $this->redirect('admin/projects/create'); }
        $data = [
            'title' => $this->request->input('title'),
            'description' => $this->request->input('description'),
            'type' => $this->request->input('type', 'business_site'),
            'package' => $this->request->input('package', 'business'),
            'url' => $this->request->input('url'),
            'staging_url' => $this->request->input('staging_url'),
            'status' => 'new_request',
            'price' => $this->request->input('price') ?: null,
            'start_date' => $this->request->input('start_date') ?: null,
            'deadline' => $this->request->input('deadline') ?: null,
            'notes' => $this->request->input('notes'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $cols = implode(', ', array_keys($data));
        $plh = implode(', ', array_fill(0, count($data), '?'));
        $this->db()->prepare("INSERT INTO website_projects ($cols) VALUES ($plh)")->execute(array_values($data));
        Session::flash('success', 'הפרויקט נוצר.');
        $this->redirect('admin/projects');
    }

    public function show(string $id): string
    {
        $stmt = $this->db()->prepare("SELECT * FROM website_projects WHERE id = ?");
        $stmt->execute([$id]);
        $project = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$project) throw new \App\Core\Exceptions\NotFoundException();
        return $this->render('projects/show', ['pageTitle' => $project['title'], 'project' => $project]);
    }

    public function update(string $id): string
    {
        if (!$this->validateCsrf()) { $this->redirect("admin/projects/$id"); }
        $fields = ['title','description','type','package','url','staging_url','price','start_date','deadline','notes','status','progress'];
        $sets = []; $vals = [];
        foreach ($fields as $f) {
            $v = $this->request->input($f);
            if ($v !== null) { $sets[] = "`$f` = ?"; $vals[] = $v; }
        }
        $vals[] = $id;
        if ($sets) {
            $sets[] = "updated_at = NOW()";
            $this->db()->prepare("UPDATE website_projects SET " . implode(', ', $sets) . " WHERE id = ?")->execute($vals);
        }
        Session::flash('success', 'הפרויקט עודכן.');
        $this->redirect("admin/projects/$id");
    }

    public function updateStatus(string $id): string
    {
        $status = $this->request->input('status');
        if ($status) {
            $this->db()->prepare("UPDATE website_projects SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$status, $id]);
        }
        $this->redirect("admin/projects/$id");
    }
}
