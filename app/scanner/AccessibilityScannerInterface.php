<?php
namespace App\Scanner;

use App\Models\AccessibilityResult;

interface AccessibilityScannerInterface
{
    public function scan(string $html, string $url): AccessibilityResult;
}
