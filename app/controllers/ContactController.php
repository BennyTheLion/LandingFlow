<?php
namespace App\Controllers;

use App\Core\Controller;

class ContactController extends Controller
{
    public function index(): string
    {
        return $this->render('public/contact', [
            'pageTitle' => 'צור קשר — LandingFlow',
            'csrf' => $this->getCsrfToken(),
        ]);
    }

    public function submit(): string
    {
        if (!$this->validateCsrf()) {
            \App\Core\Session::flash('error', 'שגיאת אבטחה. אנא נסה שוב.');
            $this->redirect('contact');
        }

        $name = $this->request->input('name');
        $email = $this->request->input('email');
        $phone = $this->request->input('phone');
        $message = $this->request->input('message');

        if (empty($name) || empty($email) || empty($phone) || empty($message)) {
            \App\Core\Session::flash('error', 'אנא מלא את כל השדות.');
            $this->redirect('contact');
        }

        try {
            $db = \App\Core\Database::getInstance();
            $db->getConnection()->prepare(
                "INSERT INTO contact_messages (name, email, phone, message, created_at) VALUES (?, ?, ?, ?, NOW())"
            )->execute([$name, $email, $phone, $message]);
            
            // Create lead + send email notifications
            (new \App\Services\LeadService(new \App\Repositories\LeadRepository()))->captureFromWebsite($name, $phone, $email, 'website', '', $message);

            \App\Core\Session::flash('success', 'ההודעה נשלחה בהצלחה! ניצור קשר בקרוב.');
        } catch (\Exception $e) {
            \App\Core\Session::flash('error', 'שגיאה בשליחת ההודעה. אנא נסה שוב.');
        }

        $this->redirect('contact');
    }
}
