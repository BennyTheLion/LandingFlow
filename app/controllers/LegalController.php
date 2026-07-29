<?php
namespace App\Controllers;

use App\Core\Controller;

class LegalController extends Controller
{
    private function legalPage(string $page, string $title): string
    {
        return $this->render('public/legal', [
            'pageTitle' => $title . ' — LandingFlow',
            'page' => $page,
            'title' => $title,
        ]);
    }

    public function privacyPolicy(): string       { return $this->legalPage('privacy', 'מדיניות פרטיות'); }
    public function termsOfService(): string       { return $this->legalPage('terms', 'תנאי שימוש'); }
    public function cookiePolicy(): string         { return $this->legalPage('cookies', 'מדיניות עוגיות'); }
    public function accessibilityStatement(): string { return $this->legalPage('accessibility', 'הצהרת נגישות'); }
    public function fairDisclosure(): string       { return $this->legalPage('disclosure', 'גילוי נאות'); }
    public function dataDeletion(): string         { return $this->legalPage('deletion', 'מחיקת מידע'); }
    public function dataRetention(): string        { return $this->legalPage('retention', 'שמירת מידע'); }
    public function serviceAgreement(): string     { return $this->legalPage('service', 'הסכם שירות'); }
    public function maintenanceAgreement(): string { return $this->legalPage('maintenance', 'הסכם תחזוקה'); }
}
