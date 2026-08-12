<?php
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Repositories\LeadRepository;
use App\Services\LeadService;

class LeadController extends Controller
{
    private LeadService $service;

    public function __construct(?Request $request = null)
    {
        parent::__construct($request);
        $this->service = new LeadService(new LeadRepository());
    }

    public function index(): string
    {
        $status = $this->request->get('status', '');
        $search = $this->request->get('search', '');
        $leads = $this->service->list($status ?: null, $search ?: null);
        $leadsArray = array_map(fn($l) => $l->toArray(), $leads);
        return $this->render('crm/index', [
            'pageTitle' => 'לידים — CRM',
            'leads' => $leadsArray,
            'status' => $status,
            'search' => $search,
        ]);
    }

    public function create(): string
    {
        return $this->render('crm/form', [
            'pageTitle' => 'ליד חדש — CRM',
            'lead' => null,
            'csrf' => $this->getCsrfToken(),
        ]);
    }

    public function store(): void { if(!$this->validateCsrf()){Session::flash('error','שגיאת אבטחה. אנא נסה שוב.');$this->redirect('admin/leads/create');} $this->service->create(['name'=>$this->request->input('name'),'email'=>$this->request->input('email'),'phone'=>$this->request->input('phone'),'company'=>$this->request->input('company'),'website'=>$this->request->input('website'),'source'=>$this->request->input('source','website'),'interest'=>$this->request->input('interest'),'budget'=>$this->request->input('budget')?:null,'notes'=>$this->request->input('notes'),'consent_given'=>$this->request->input('consent')?1:0]); Session::flash('success','הליד נוצר בהצלחה.'); $this->redirect('admin/leads'); }

    public function show(string $id): string
    {
        $data = $this->service->get((int) $id);
        return $this->render('crm/show', [
            'pageTitle' => $data['lead']->name . ' — ליד',
            'lead' => $data['lead']->toArray(),
            'notes' => $data['notes'],
        ]);
    }

    public function edit(string $id): string
    {
        $data = $this->service->get((int) $id);
        return $this->render('crm/form', [
            'pageTitle' => 'עריכת ליד — CRM',
            'lead' => $data['lead']->toArray(),
            'csrf' => $this->getCsrfToken(),
        ]);
    }

    public function update(string $id): void { if(!$this->validateCsrf()){Session::flash('error','שגיאת אבטחה. אנא נסה שוב.');$this->redirect("admin/leads/$id/edit");} $this->service->update((int)$id,['name'=>$this->request->input('name'),'email'=>$this->request->input('email'),'phone'=>$this->request->input('phone'),'company'=>$this->request->input('company'),'website'=>$this->request->input('website'),'source'=>$this->request->input('source'),'interest'=>$this->request->input('interest'),'budget'=>$this->request->input('budget')?:null,'notes'=>$this->request->input('notes')]); Session::flash('success','הליד עודכן בהצלחה.'); $this->redirect("admin/leads/$id"); }

    public function updateStatus(string $id): void { $status=$this->request->input('status'); if($status){$this->service->updateStatus((int)$id,$status);} $this->redirect("admin/leads/$id"); }

    public function addNote(string $id): void { $content=$this->request->input('content'); if($content){$userId=Session::get('user')['id']??null;$type=$this->request->input('type','note');$this->service->addNote((int)$id,$userId,$content,$type);} $this->redirect("admin/leads/$id"); }

    public function delete(string $id): void { $this->service->delete((int)$id); Session::flash('success','הליד נמחק.'); $this->redirect('admin/leads'); }
}