<?php
namespace App\Scanner;

use App\Models\SeoResult;

interface SeoScannerInterface
{
    public function scan(string $html, string $url): SeoResult;
}
