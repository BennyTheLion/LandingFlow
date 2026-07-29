<?php
namespace App\Scanner;

use App\Models\LandingPageResult;

interface LandingPageScannerInterface
{
    public function scan(string $html, string $url, array $headers = []): LandingPageResult;
}
