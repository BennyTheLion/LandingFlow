<?php
namespace App\Controllers;

use App\Core\Controller;

class BlogController extends Controller
{
    public function index(): string
    {
        return $this->render('public/blog', ['pageTitle' => 'Blog — LandingFlow']);
    }

    public function show(string $slug): string
    {
        return $this->render('public/blog-post', [
            'pageTitle' => 'Blog Post — LandingFlow',
            'slug' => $slug,
        ]);
    }
}