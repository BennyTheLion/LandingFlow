<?php
use App\Controllers\AuthController;
use App\Core\Request;
use App\Core\Session;
use App\Core\Database;

class AuthControllerTest extends TestCase
{
    public function setUp(): void
    {
        $_SESSION = [];
        $_POST = [];
        $_GET = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['HTTPS'] = 'off';
        unset($_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['HTTP_CLIENT_IP']);
        resetDatabase();
        Session::set(CSRF_TOKEN_NAME, bin2hex(random_bytes(32)));
    }

    private function db(): PDO
    {
        return Database::getInstance()->getConnection();
    }

    private function createTestUser(string $email = 'test@example.com', string $password = 'Abcdef1!', int $roleId = 3): int
    {
        $repo = new \App\Repositories\UserRepository();
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        return $repo->create('Test User', $email, '0501234567', $hash, $roleId);
    }

    public function runAll(): array
    {
        return $this->runTests([
            'testRegisterWithValidData','testRegisterWithDuplicateEmail','testRegisterWithWeakPassword',
            'testRegisterWithEmptyName','testRegisterWithInvalidEmail','testRegisterWithMismatchedPasswords',
            'testRegisterAssignsClientRole','testRegisterWithStrongPassphrase',
            'testLoginWithValidCredentials','testLoginWithWrongPassword','testLoginWithNonexistentEmail',
            'testLoginSetsSessionUser','testLoginUpdatesLastLoginTimestamp',
            'testLogoutDestroysSession',
            'testForgotPasswordCreatesToken','testForgotPasswordWithNonexistentEmail','testForgotPasswordSetsExpiresAt',
            'testResetPasswordWithValidToken','testResetPasswordWithExpiredToken','testResetPasswordWithInvalidToken',
            'testResetPasswordCleansUpOldTokens','testRegisterWithoutCsrf','testLoginWithoutCsrf',
        ]);
    }

    public function testRegisterWithValidData(): void { $_SERVER['REQUEST_METHOD']='POST'; $_POST=['name'=>'John Doe','email'=>'john@test.com','phone'=>'0501234567','password'=>'Abcdef1!','password_confirm'=>'Abcdef1!',CSRF_TOKEN_NAME=>Session::get(CSRF_TOKEN_NAME)]; $c=new AuthController(); try{$c->register();}catch(\Throwable $e){} $u=$this->db()->query("SELECT * FROM users WHERE email='john@test.com'")->fetch(); $this->assertTrue((bool)$u,'User should be created'); $this->assertEquals('John Doe',$u['name']); $this->assertEquals(3,(int)$u['role_id'],'Should get client role'); }

    public function testRegisterWithDuplicateEmail(): void { $this->createTestUser('dup@test.com'); $_SERVER['REQUEST_METHOD']='POST'; $_POST=['name'=>'Dup','email'=>'dup@test.com','phone'=>'0500000000','password'=>'Abcdef1!','password_confirm'=>'Abcdef1!',CSRF_TOKEN_NAME=>Session::get(CSRF_TOKEN_NAME)]; $c=new AuthController(); try{$c->register();}catch(\Throwable $e){} $cnt=$this->db()->query("SELECT COUNT(*) as c FROM users WHERE email='dup@test.com'")->fetch(); $this->assertEquals(1,(int)$cnt['c'],'Should not create duplicate'); }

    public function testRegisterWithWeakPassword(): void { $_SERVER['REQUEST_METHOD']='POST'; $_POST=['name'=>'Weak','email'=>'weak@test.com','phone'=>'0501111111','password'=>'short','password_confirm'=>'short',CSRF_TOKEN_NAME=>Session::get(CSRF_TOKEN_NAME)]; $c=new AuthController(); try{$c->register();}catch(\Throwable $e){} $u=$this->db()->query("SELECT * FROM users WHERE email='weak@test.com'")->fetch(); $this->assertFalse((bool)$u,'Weak password should be rejected'); }

    public function testRegisterWithEmptyName(): void { $_SERVER['REQUEST_METHOD']='POST'; $_POST=['name'=>'','email'=>'noname@test.com','phone'=>'0502222222','password'=>'Abcdef1!','password_confirm'=>'Abcdef1!',CSRF_TOKEN_NAME=>Session::get(CSRF_TOKEN_NAME)]; $c=new AuthController(); try{$c->register();}catch(\Throwable $e){} $u=$this->db()->query("SELECT * FROM users WHERE email='noname@test.com'")->fetch(); $this->assertFalse((bool)$u,'Empty name rejected'); }

    public function testRegisterWithInvalidEmail(): void { $_SERVER['REQUEST_METHOD']='POST'; $_POST=['name'=>'Bad Email','email'=>'notanemail','phone'=>'0503333333','password'=>'Abcdef1!','password_confirm'=>'Abcdef1!',CSRF_TOKEN_NAME=>Session::get(CSRF_TOKEN_NAME)]; $c=new AuthController(); try{$c->register();}catch(\Throwable $e){} $u=$this->db()->query("SELECT * FROM users WHERE name='Bad Email'")->fetch(); $this->assertFalse((bool)$u,'Invalid email rejected'); }

    public function testRegisterWithMismatchedPasswords(): void { $_SERVER['REQUEST_METHOD']='POST'; $_POST=['name'=>'Mismatch','email'=>'mismatch@test.com','phone'=>'0504444444','password'=>'Abcdef1!','password_confirm'=>'Different1!',CSRF_TOKEN_NAME=>Session::get(CSRF_TOKEN_NAME)]; $c=new AuthController(); try{$c->register();}catch(\Throwable $e){} $u=$this->db()->query("SELECT * FROM users WHERE email='mismatch@test.com'")->fetch(); $this->assertFalse((bool)$u,'Mismatched passwords rejected'); }

    public function testRegisterAssignsClientRole(): void { $_SERVER['REQUEST_METHOD']='POST'; $_POST=['name'=>'ClientRole','email'=>'clientrole@test.com','phone'=>'0505555555','password'=>'Abcdef1!','password_confirm'=>'Abcdef1!',CSRF_TOKEN_NAME=>Session::get(CSRF_TOKEN_NAME)]; $c=new AuthController(); try{$c->register();}catch(\Throwable $e){} $u=$this->db()->query("SELECT * FROM users WHERE email='clientrole@test.com'")->fetch(); $this->assertEquals(3,(int)$u['role_id'],'New users get role_id=3'); }

    public function testRegisterWithStrongPassphrase(): void { $_SERVER['REQUEST_METHOD']='POST'; $_POST=['name'=>'Passphrase','email'=>'passphrase@test.com','phone'=>'0506666666','password'=>'myverylongpassphrase123','password_confirm'=>'myverylongpassphrase123',CSRF_TOKEN_NAME=>Session::get(CSRF_TOKEN_NAME)]; $c=new AuthController(); try{$c->register();}catch(\Throwable $e){} $u=$this->db()->query("SELECT * FROM users WHERE email='passphrase@test.com'")->fetch(); $this->assertTrue((bool)$u,'Long passphrase accepted'); }

    public function testLoginWithValidCredentials(): void { $uid=$this->createTestUser('login@test.com','Abcdef1!'); $_SERVER['REQUEST_METHOD']='POST'; $_POST=['email'=>'login@test.com','password'=>'Abcdef1!',CSRF_TOKEN_NAME=>Session::get(CSRF_TOKEN_NAME)]; $c=new AuthController(); try{$c->login();}catch(\Throwable $e){} $this->assertTrue(Session::has('user'),'Session should have user'); $usr=Session::get('user'); $this->assertEquals($uid,$usr['id']); $this->assertEquals('Test User',$usr['name']); }

    public function testLoginWithWrongPassword(): void { $this->createTestUser('wrongpw@test.com','Abcdef1!'); $_SERVER['REQUEST_METHOD']='POST'; $_POST=['email'=>'wrongpw@test.com','password'=>'WrongPassword',CSRF_TOKEN_NAME=>Session::get(CSRF_TOKEN_NAME)]; $c=new AuthController(); try{$c->login();}catch(\Throwable $e){} $this->assertFalse(Session::has('user'),'Wrong password should not login'); }

    public function testLoginWithNonexistentEmail(): void { $_SERVER['REQUEST_METHOD']='POST'; $_POST=['email'=>'noone@test.com','password'=>'Abcdef1!',CSRF_TOKEN_NAME=>Session::get(CSRF_TOKEN_NAME)]; $c=new AuthController(); try{$c->login();}catch(\Throwable $e){} $this->assertFalse(Session::has('user'),'Nonexistent email should not login'); }

    public function testLoginSetsSessionUser(): void { $this->createTestUser('session@test.com','Abcdef1!'); $_SERVER['REQUEST_METHOD']='POST'; $_POST=['email'=>'session@test.com','password'=>'Abcdef1!',CSRF_TOKEN_NAME=>Session::get(CSRF_TOKEN_NAME)]; $c=new AuthController(); try{$c->login();}catch(\Throwable $e){} $u=Session::get('user'); $this->assertNotNull($u); $this->assertArrayHasKey('id',$u); $this->assertArrayHasKey('role_id',$u); $this->assertEquals('session@test.com',$u['email']); }

    public function testLoginUpdatesLastLoginTimestamp(): void { $this->createTestUser('lastlogin@test.com','Abcdef1!'); $_SERVER['REQUEST_METHOD']='POST'; $_SERVER['REMOTE_ADDR']='10.0.0.1'; $_POST=['email'=>'lastlogin@test.com','password'=>'Abcdef1!',CSRF_TOKEN_NAME=>Session::get(CSRF_TOKEN_NAME)]; $c=new AuthController(); try{$c->login();}catch(\Throwable $e){} $row=$this->db()->query("SELECT last_login_at, last_login_ip FROM users WHERE email='lastlogin@test.com'")->fetch(); $this->assertNotNull($row['last_login_at'],'last_login_at should be set'); $this->assertEquals('10.0.0.1',$row['last_login_ip'],'IP should be logged'); }

    public function testLogoutDestroysSession(): void { Session::set('user',['id'=>1,'name'=>'Test','role_id'=>1]); $_SERVER['REQUEST_METHOD']='GET'; $c=new AuthController(); try{$c->logout();}catch(\Throwable $e){} $this->assertFalse(Session::has('user'),'Session user should be removed on logout'); }

    public function testForgotPasswordCreatesToken(): void { $this->createTestUser('forgot@test.com','Abcdef1!'); $_SERVER['REQUEST_METHOD']='POST'; $_POST=['email'=>'forgot@test.com',CSRF_TOKEN_NAME=>Session::get(CSRF_TOKEN_NAME)]; $c=new AuthController(); try{$c->forgotPassword();}catch(\Throwable $e){} $t=$this->db()->query("SELECT * FROM password_resets WHERE email='forgot@test.com'")->fetch(); $this->assertTrue((bool)$t,'Token should be created'); $this->assertEquals('forgot@test.com',$t['email']); $this->assertNotNull($t['expires_at'],'expires_at should be set'); }

    public function testForgotPasswordWithNonexistentEmail(): void { $_SERVER['REQUEST_METHOD']='POST'; $_POST=['email'=>'noone@nowhere.com',CSRF_TOKEN_NAME=>Session::get(CSRF_TOKEN_NAME)]; $c=new AuthController(); try{$c->forgotPassword();}catch(\Throwable $e){} $cnt=$this->db()->query("SELECT COUNT(*) as c FROM password_resets")->fetch(); $this->assertEquals(0,(int)$cnt['c'],'No token for nonexistent email'); }

    public function testForgotPasswordSetsExpiresAt(): void { $this->createTestUser('expires@test.com'); $_SERVER['REQUEST_METHOD']='POST'; $_POST=['email'=>'expires@test.com',CSRF_TOKEN_NAME=>Session::get(CSRF_TOKEN_NAME)]; $c=new AuthController(); try{$c->forgotPassword();}catch(\Throwable $e){} $t=$this->db()->query("SELECT expires_at FROM password_resets WHERE email='expires@test.com'")->fetch(); $this->assertNotNull($t['expires_at'],'expires_at must be populated'); }

    public function testResetPasswordWithValidToken(): void { $this->createTestUser('reset@example.com','OldPass1!'); $token=bin2hex(random_bytes(32)); $this->db()->prepare("INSERT INTO password_resets (email, token, created_at, expires_at) VALUES ('reset@example.com', ?, datetime('now'), datetime('now','+1 hour'))")->execute([$token]); $_SERVER['REQUEST_METHOD']='POST'; $_POST=['token'=>$token,'password'=>'NewPass2@','password_confirm'=>'NewPass2@',CSRF_TOKEN_NAME=>Session::get(CSRF_TOKEN_NAME)]; $c=new AuthController(); try{$c->resetPassword();}catch(\Throwable $e){} $u=$this->db()->query("SELECT * FROM users WHERE email='reset@example.com'")->fetch(); $this->assertTrue(password_verify('NewPass2@',$u['password']),'Password should be updated'); }

    public function testResetPasswordWithExpiredToken(): void { $this->createTestUser('expired_token@example.com'); $token=bin2hex(random_bytes(32)); $this->db()->prepare("INSERT INTO password_resets (email, token, created_at, expires_at) VALUES ('expired_token@example.com', ?, datetime('now','-25 hours'), datetime('now','-24 hours'))")->execute([$token]); $_SERVER['REQUEST_METHOD']='POST'; $_POST=['token'=>$token,'password'=>'NewPass2@','password_confirm'=>'NewPass2@',CSRF_TOKEN_NAME=>Session::get(CSRF_TOKEN_NAME)]; $c=new AuthController(); try{$c->resetPassword();}catch(\Throwable $e){} $u=$this->db()->query("SELECT * FROM users WHERE email='expired_token@example.com'")->fetch(); $this->assertFalse(password_verify('NewPass2@',$u['password']),'Password NOT changed with expired token'); }

    public function testResetPasswordWithInvalidToken(): void { $this->createTestUser('invalid_token@example.com','OldPass1!'); $_SERVER['REQUEST_METHOD']='POST'; $_POST=['token'=>'invalid-token-does-not-exist','password'=>'NewPass3#','password_confirm'=>'NewPass3#',CSRF_TOKEN_NAME=>Session::get(CSRF_TOKEN_NAME)]; $c=new AuthController(); try{$c->resetPassword();}catch(\Throwable $e){} $u=$this->db()->query("SELECT * FROM users WHERE email='invalid_token@example.com'")->fetch(); $this->assertTrue(password_verify('OldPass1!',$u['password']),'Password should NOT change'); }

    public function testResetPasswordCleansUpOldTokens(): void { $this->createTestUser('cleanup@example.com','OldPass1!'); $token=bin2hex(random_bytes(32)); $this->db()->prepare("INSERT INTO password_resets (email, token, created_at, expires_at) VALUES ('cleanup@example.com', ?, datetime('now'), datetime('now','+1 hour'))")->execute([$token]); $_SERVER['REQUEST_METHOD']='POST'; $_POST=['token'=>$token,'password'=>'NewPass4$','password_confirm'=>'NewPass4$',CSRF_TOKEN_NAME=>Session::get(CSRF_TOKEN_NAME)]; $c=new AuthController(); try{$c->resetPassword();}catch(\Throwable $e){} $cnt=$this->db()->query("SELECT COUNT(*) as c FROM password_resets WHERE email='cleanup@example.com'")->fetch(); $this->assertEquals(0,(int)$cnt['c'],'Old tokens deleted after reset'); }

    public function testRegisterWithoutCsrf(): void { $_SERVER['REQUEST_METHOD']='POST'; $_POST=['name'=>'No CSRF','email'=>'nocsrf@example.com','phone'=>'0507777777','password'=>'Abcdef1!','password_confirm'=>'Abcdef1!']; $c=new AuthController(); try{$c->register();}catch(\Throwable $e){} $u=$this->db()->query("SELECT * FROM users WHERE email='nocsrf@example.com'")->fetch(); $this->assertFalse((bool)$u,'Registration without CSRF token should be rejected'); }

    public function testLoginWithoutCsrf(): void { $this->createTestUser('csrf_login@example.com','Abcdef1!'); $_SERVER['REQUEST_METHOD']='POST'; $_POST=['email'=>'csrf_login@example.com','password'=>'Abcdef1!']; $c=new AuthController(); try{$c->login();}catch(\Throwable $e){} $this->assertFalse(Session::has('user'),'Login without CSRF should not set session'); }
}
