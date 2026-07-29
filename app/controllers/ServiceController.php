<?php
namespace App\Controllers;

use App\Core\Controller;

class ServiceController extends Controller
{
    public function index(): string
    {
        return $this->render('public/services', [
            'pageTitle' => 'שירותים — LandingFlow',
            'serviceType' => 'index',
        ]);
    }

    public function websiteDevelopment(): string
    {
        return $this->render('public/services', [
            'pageTitle' => 'פיתוח אתרים — LandingFlow',
            'serviceType' => 'website',
        ]);
    }

    public function landingPages(): string
    {
        return $this->render('public/services', [
            'pageTitle' => 'דפי נחיתה — LandingFlow',
            'serviceType' => 'landing',
        ]);
    }

    public function hosting(): string
    {
        return $this->render('public/services', [
            'pageTitle' => 'אחסון אתרים — LandingFlow',
            'serviceType' => 'hosting',
        ]);
    }

    public function monitoring(): string
    {
        return $this->render('public/services', [
            'pageTitle' => 'ניטור אתרים — LandingFlow',
            'serviceType' => 'monitoring',
        ]);
    }
}
