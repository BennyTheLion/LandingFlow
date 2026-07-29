<?php
namespace App\Controllers;

use App\Core\Controller;

class PageController extends Controller
{
    public function about(): string
    {
        return $this->render('public/about', [
            'pageTitle' => 'אודות LandingFlow',
        ]);
    }

    public function pricing(): string
    {
        return $this->render('public/pricing', [
            'pageTitle' => 'מחירים — LandingFlow',
        ]);
    }

    public function portfolio(): string
    {
        $sites = require CONFIG_PATH . '/demo.sites.php';
        return $this->render('public/portfolio', [
            'pageTitle' => 'תיק עבודות — LandingFlow',
            'pageDescription' => 'צפו באתרים שבנינו. גללו בין הפרויקטים שלנו.',
            'sites' => $sites,
        ]);
    }
}
