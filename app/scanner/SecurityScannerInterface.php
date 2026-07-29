<?php
namespace App\Scanner;

use App\Models\SecurityResult;

interface SecurityScannerInterface
{
    public function scan(string $html, string $url, array $headers = []): SecurityResult;
}
