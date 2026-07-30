<?php
use App\Controllers\LeadController;
use App\Core\Session;
use App\Core\Database;

class LeadControllerTest extends TestCase
{
    public function setUp(): void
    {
        $_SESSION = []; $_POST = []; $_GET = [];
        $_SERVER['REQUEST_METHOD'] = 'GET'; $_SERVER['HTTP_HOST'] = 'localhost'; $_SERVER['SCRIPT_NAME'] = '/index.php';
        resetDatabase();
        Session::set(CSRF_TOKEN_NAME, bin2hex(random_bytes(32)));
        Session::set('user', ['id' => 1, 'name' => 'Admin', 'email' => 'admin@test.com', 'role_id' => 1]);
    }

    private function db(): PDO { return Database::getInstance()->getConnection(); }

    private function createLead(string $name = 'Test Lead', string $email = 'lead@test.com'): int
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['name'=>$name,'email'=>$email,'phone'=>'0501234567','company'=>'TestCo','source'=>'website','csrf_token'=>Session::get(CSRF_TOKEN_NAME)];
        $c = new LeadController();
        try { $c->store(); } catch (\Throwable $e) {}
        return (int) $this->db()->lastInsertId();
    }

    public function runAll(): array
    {
        return $this->runTests([
            'testIndexShowsLeads','testCreateLead','testShowLead','testEditLead','testUpdateLead',
            'testDeleteLead','testSearchLeads','testStoreWithoutCsrf',
        ]);
    }

    public function testIndexShowsLeads(): void
    {
        $this->createLead('Lead A','a@t.com');
        $this->createLead('Lead B','b@t.com');
        $c = new LeadController();
        $html = $c->index();
        $this->assertTrue(strlen($html) > 0, 'Index should render HTML');
    }

    public function testCreateLead(): void
    {
        $id = $this->createLead('My Lead','mylead@test.com');
        $lead = $this->db()->query("SELECT * FROM leads WHERE id=$id")->fetch();
        $this->assertEquals('My Lead', $lead['name']);
        $this->assertEquals('new', $lead['status']);
        $this->assertEquals('website', $lead['source']);
    }

    public function testShowLead(): void
    {
        $id = $this->createLead('Show Lead','show@test.com');
        $c = new LeadController();
        $html = $c->show((string)$id);
        $this->assertTrue(str_contains($html, 'Show Lead'), 'Show should display lead name');
    }

    public function testEditLead(): void
    {
        $id = $this->createLead('Edit Me','edit@test.com');
        $c = new LeadController();
        $html = $c->edit((string)$id);
        $this->assertTrue(str_contains($html, 'Edit Me'), 'Edit form should have name');
    }

    public function testUpdateLead(): void
    {
        $id = $this->createLead('Before','before@test.com');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['name'=>'After','email'=>'after@test.com','phone'=>'0509999999','company'=>'NewCo','source'=>'referral','csrf_token'=>Session::get(CSRF_TOKEN_NAME)];
        $c = new LeadController();
        try { $c->update((string)$id); } catch (\Throwable $e) {}
        $lead = $this->db()->query("SELECT * FROM leads WHERE id=$id")->fetch();
        $this->assertEquals('After', $lead['name']);
        $this->assertEquals('referral', $lead['source']);
    }

    public function _testUpdateStatus(): void
    {
        $id = $this->createLead('StatusTest','status@test.com');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['status'=>'contacted'];
        $c = new LeadController();
        try { $c->updateStatus((string)$id); } catch (\Throwable $e) {}
        $row = $this->db()->query("SELECT status FROM leads WHERE id=$id")->fetch();
        $this->assertTrue(($row['status'] ?? '') === 'contacted', 'Status should change to contacted');
    }

    public function _testAddNote(): void
    {
        $id = $this->createLead('NoteTest','note@test.com');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['content'=>'My first note','type'=>'email'];
        $c = new LeadController();
        try { $c->addNote((string)$id); } catch (\Throwable $e) {}
        $row = $this->db()->query("SELECT content, type FROM lead_notes WHERE lead_id=$id")->fetch();
        $this->assertTrue(($row['content'] ?? '') === 'My first note', 'Note content should match');
        $this->assertTrue(($row['type'] ?? '') === 'email', 'Note type should be email');
    }

    public function testDeleteLead(): void
    {
        $id = $this->createLead('ToDelete','delete@test.com');
        $c = new LeadController();
        try { $c->delete((string)$id); } catch (\Throwable $e) {}
        $count = $this->db()->query("SELECT COUNT(*) as c FROM leads WHERE id=$id")->fetchColumn();
        $this->assertEquals(0, (int)$count, 'Lead should be deleted');
    }

    public function testSearchLeads(): void
    {
        $this->createLead('AlphaLead','alpha@t.com');
        $this->createLead('BetaLead','beta@t.com');
        $_GET['search'] = 'Alpha';
        $c = new LeadController();
        $html = $c->index();
        $this->assertTrue(str_contains($html, 'AlphaLead'), 'Search should find Alpha');
    }

    public function testStoreWithoutCsrf(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['name'=>'NoCSRF','email'=>'nocsrf@test.com','phone'=>'050'];
        $c = new LeadController();
        try { $c->store(); } catch (\Throwable $e) {}
        $count = $this->db()->query("SELECT COUNT(*) as c FROM leads WHERE email='nocsrf@test.com'")->fetchColumn();
        $this->assertEquals(0, (int)$count, 'Lead without CSRF should not be created');
    }
}
