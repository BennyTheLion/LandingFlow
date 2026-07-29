<?php
namespace App\Scanner;

use App\Models\PerformanceResult;

interface PerformanceScannerInterface
{
    public function scan(string $html, string $url, array $headers = []): PerformanceResult;
}
