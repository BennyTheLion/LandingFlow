<?php
namespace App\Scanner;

use App\Models\SpamResult;

interface SpamScannerInterface
{
    public function scan(string $html, string $url, array $headers = []): SpamResult;
}
