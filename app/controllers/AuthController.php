<?php
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Core\Logger;
use App\Repositories\UserRepository;
use App\Repositories\PasswordResetRepository;
use App\Repositories\EmailVerificationRepository;
use App\Services\AuthService;

class AuthController extends Controller
{
    private AuthService $auth;

    public function __construct(?Request $request = null)
    {
        parent::__construct($request);
        $this->auth = new AuthService(
            new UserRepository(),
            new PasswordResetRepository(),
            new EmailVerificationRepository()
        );
    }

    public function loginForm(): string
    {
        if (Session::has('user')) { $this->redirect('admin'); }
        return $this->render('auth/login', ['pageTitle' => 'התחברות — LandingFlow', 'csrf' => $this->getCsrfToken()]);
    }

    public function login(): void
    {
        if (!$this->validateCsrf()) { Session::flash('error', 'שגיאת אבטחה. אנא נסה שוב.'); $this->redirect('login'); }
        $email = $this->request->input('email'); $password = $this->request->input('password');
        if (empty($email) || empty($password)) { Session::flash('error', 'נא למלא אימייל וסיסמה.'); $this->redirect('login'); }
        $result = $this->auth->login($email, $password, $this->request->getClientIp());
        if (!$result['success']) { Session::flash('error', $result['error']); $this->redirect('login'); }
        Session::set('user', $result['user']->toSession()); Session::regenerate();
        $this->redirect('admin');
    }

    public function registerForm(): string
    {
        if (Session::has('user')) { $this->redirect('admin'); }
        return $this->render('auth/register', ['pageTitle' => 'הרשמה — LandingFlow', 'csrf' => $this->getCsrfToken()]);
    }

    public function register(): void
    {
        Logger::info('register: START', ['method' => $this->request->getMethod(), 'uri' => $this->request->getUri(), 'ip' => $this->request->getClientIp()]);
        if (!$this->validateCsrf()) { Logger::warning('register: CSRF failed'); Session::flash('error', 'שגיאת אבטחה. אנא נסה שוב.'); $this->redirect('register'); }
        $name = trim($this->request->input('name') ?? ''); $email = trim($this->request->input('email') ?? ''); $phone = trim($this->request->input('phone') ?? ''); $password = $this->request->input('password') ?? ''; $confirm = $this->request->input('password_confirm') ?? '';
        $result = $this->auth->register($name, $email, $phone, $password, $confirm);
        if (!$result['success']) { Session::flash('error', implode('<br>', $result['errors'])); $this->redirect('register'); }
        Logger::info('register: user created', ['user_id' => $result['userId']]);
        Session::flash('success', 'ההרשמה בוצעה בהצלחה! אפשר להתחבר עכשיו.');
        try { (new \App\Services\LeadService(new \App\Repositories\LeadRepository()))->captureFromWebsite($name, $phone, $email, 'website', 'הרשמה', 'נרשם באתר'); } catch (\Exception $e) { Logger::error('register: LeadService failed', ['error' => $e->getMessage()]); }
        $this->redirect('login');
    }

    public function logout(): void { Session::destroy(); $this->redirect('/'); }

    public function forgotPasswordForm(): string { return $this->render('auth/forgot', ['pageTitle' => 'שכחתי סיסמה — LandingFlow', 'csrf' => $this->getCsrfToken()]); }

    public function forgotPassword(): void
    {
        if (!$this->validateCsrf()) { Session::flash('error', 'שגיאת אבטחה. אנא נסה שוב.'); $this->redirect('forgot-password'); }
        $email = $this->request->input('email');
        if (empty($email)) { Session::flash('error', 'נא להזין כתובת אימייל.'); $this->redirect('forgot-password'); }
        $this->auth->forgotPassword($email);
        Session::flash('success', 'אם הכתובת קיימת במערכת, נשלח אליה קישור לאיפוס סיסמה.'); $this->redirect('login');
    }

    public function resetPasswordForm(string $token): string { return $this->render('auth/reset', ['pageTitle' => 'איפוס סיסמה — LandingFlow', 'csrf' => $this->getCsrfToken(), 'token' => $token]); }

    public function resetPassword(): void
    {
        if (!$this->validateCsrf()) { Session::flash('error', 'שגיאת אבטחה. אנא נסה שוב.'); $this->redirect('login'); }
        $token = $this->request->input('token'); $password = $this->request->input('password'); $confirm = $this->request->input('password_confirm');
        if (empty($token)) { Session::flash('error', 'קישור לא תקין או שפג תוקפו.'); $this->redirect('login'); }
        $result = $this->auth->resetPassword($token, $password, $confirm);
        if (!$result['success']) { Session::flash('error', $result['error']); $this->redirect('login'); }
        Session::flash('success', 'הסיסמה עודכנה בהצלחה! אפשר להתחבר עכשיו.'); $this->redirect('login');
    }

    public function verifyEmail(string $token): void
    {
        $result = $this->auth->verifyEmail($token);
        if (!$result['success']) { Session::flash('error', $result['error']); $this->redirect('login'); }
        Session::flash('success', 'כתובת האימייל אומתה בהצלחה!'); $this->redirect('login');
    }
}