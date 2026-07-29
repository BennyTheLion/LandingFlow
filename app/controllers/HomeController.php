<?php
namespace App\Controllers;

use App\Core\Controller;

class HomeController extends Controller
{
    /**
     * LandingFlow homepage
     */
    public function index(): string
    {
        return $this->render('public/home', [
            'pageTitle' => 'LandingFlow — דשבורד אחד לכל הנתונים על האתר שלכם',
            'pageDescription' => 'LandingFlow מרכז ניטור, ביקורות, CRM ותחזוקת אתרים בדשבורד אחד. ראו בדיוק מה קורה באתר שלכם, בכל רגע. התחילו עכשיו בחינם.',
        ]);
    }

    /**
     * Preview endpoint — load a saved landing page by slug
     */
    public function preview(string $slug): string
    {
        // TODO: load landing page from database by slug
        return $this->render('public/preview', [
            'slug' => $slug,
        ]);
    }
}
