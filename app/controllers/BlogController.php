<?php
namespace App\Controllers;

use App\Core\Controller;

class BlogController extends Controller
{
    public function index(): string
    {
        return $this->render('public/blog', ['pageTitle' => 'בלוג — LandingFlow']);
    }

    public function show(string $slug): string
    {
        return $this->render('public/blog-post', [
            'pageTitle' => 'בלוג — LandingFlow',
            'slug' => $slug,
        ]);
    }
}