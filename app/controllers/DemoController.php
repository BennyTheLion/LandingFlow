<?php
namespace App\Controllers;

use App\Core\Controller;

class DemoController extends Controller
{
    public function index(): string
    {
        $sites = require CONFIG_PATH . '/demo.sites.php';
        return $this->render('public/demo', [
            'pageTitle' => 'דמו חי — LandingFlow',
            'pageDescription' => 'צפו בדמו חי של אתרים שבנינו. ראו את התוצאות בזמן אמת.',
            'sites' => $sites,
            'csrf' => $this->getCsrfToken(),
        ]);
    }

    public function request(): string
    {
        if (!$this->validateCsrf()) {
            \App\Core\Session::flash('error', 'שגיאת אבטחה. אנא נסה שוב.');
            $this->redirect('demo');
        }

        $name = $this->request->input('name');
        $phone = $this->request->input('phone');
        $email = $this->request->input('email');
        $projectType = $this->request->input('project_type');
        $message = $this->request->input('message');

        if (empty($name) || empty($phone)) {
            \App\Core\Session::flash('error', 'אנא מלא שם וטלפון.');
            $this->redirect('demo');
        }

        try {
            $db = \App\Core\Database::getInstance();
            $db->getConnection()->prepare(
                "INSERT INTO contact_messages (name, email, phone, message, created_at) VALUES (?, ?, ?, ?, NOW())"
            )->execute([$name . ' (דמו)', $email, $phone, "סוג פרויקט: $projectType\n$message"]);
            
            \App\Core\Session::flash('success', 'הבקשה התקבלה! נחזור אליך בהקדם.');
            
            // Create lead + send emails
            (new \App\Services\LeadService(new \App\Repositories\LeadRepository()))->captureFromWebsite($name . ' (דמו)', $phone, $email, 'website', $projectType, $message);

        } catch (\Exception $e) {
            \App\Core\Session::flash('error', 'שגיאה בשליחה. אנא נסה שוב.');
        }

        $this->redirect('demo');
    }

    /** AI PrototypeBuilder — generates site structure from business inputs */
    public function build(): void
    {
        try {
        // Limit to 3 free trials per session
        $trials = (int)(\App\Core\Session::get('demo_trials') ?? 0);
        if ($trials >= 3) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'limit_reached' => true,
                'trials' => $trials,
                'contact' => [
                    'email' => 'info@landingflow.co.il',
                    'phone' => '+972528529448',
                    'whatsapp' => 'https://wa.me/972528529448',
                ],
                'message' => 'הגעת למגבלת 3 הניסיונות החינמיים. צור קשר להמשך.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $input = $this->request->all();
        $model = $input['model'] ?? 'free';
        $result = null;
        $usedAi = false;

        // AI Premium model
        if ($model === 'ai') {
            $ai = new \App\Services\OpenAiService();
            if ($ai->isAvailable()) {
                $desc = $input['description'] ?? '';
                $result = $ai->generateDemoSite(
                    $desc,
                    $input['name'] ?? 'My Business',
                    $input['type'] ?? 'business_site'
                );
                if ($result) {
                    $usedAi = true;
                }
            }
        }

        // Fallback to free builder
        $aiUnavailable = false;
        if (!$result) {
            $aiUnavailable = ($model === 'ai' && !$usedAi);

            $builder = new \App\Ai\PrototypeBuilder();
            $result = $builder->generate([
                'name'        => $input['name'] ?? 'My Business',
                'type'        => $input['type'] ?? 'business_site',
                'description' => $input['description'] ?? '',
                'services'    => $input['services'] ?? [],
                'colors'      => $input['colors'] ?? ['primary' => '#2563eb', 'secondary' => '#1e40af', 'accent' => '#f59e0b'],
                'font'        => $input['font'] ?? 'Inter',
            ]);
        }
        
        \App\Core\Session::set('demo_trials', $trials + 1);
        
        $response = [
            'success' => true,
            'prototype' => $result,
            'trials_remaining' => 2 - $trials,
            'model_used' => $usedAi ? 'ai' : 'free',
        ];
        if ($aiUnavailable) {
            $response['ai_unavailable'] = true;
            $response['message'] = 'שירות ה-AI אינו זמין. נוצר עם הבונה הרגיל.';
        }

        header('Content-Type: application/json');
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
        } catch (\Throwable $e) {
            header('Content-Type: application/json');
            echo json_encode(['success'=>false, 'error'=>$e->getMessage(), 'file'=>$e->getFile(), 'line'=>$e->getLine()], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}
