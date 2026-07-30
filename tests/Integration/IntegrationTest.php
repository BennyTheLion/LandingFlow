<?php
use App\Services\ScanService;
use App\Core\Session;

class IntegrationTest extends TestCase
{
    public function setUp(): void { resetDatabase(); Session::set(CSRF_TOKEN_NAME, bin2hex(random_bytes(32))); }

    private function sampleHtml(): string {
        return '<!DOCTYPE html><html lang="en"><head><title>Integration Test</title><meta name="description" content="Test"><meta name="viewport" content="width=device-width,initial-scale=1"></head><body><h1>Test Page</h1><p>This is a test page with enough content to test all scanners. We provide reliable services.</p><section><h2>FAQ</h2><h3>What is this?</h3><p>This is a test.</p></section></body></html>';
    }

    public function runAll(): array
    {
        return $this->runTests([
            'testFullPipelineRun','testPreviewScan','testScoreInRange',
            'testPersistenceWithLead','testPersistenceWithoutLead','testAnalysisGenerated',
            'testReportContainsAllScanners','testRecommendationsGenerated',
        ]);
    }

    public function testFullPipelineRun(): void {
        $s = new ScanService();
        $r = $s->runFullScan('https://test.com', $this->sampleHtml(), ['content-encoding: gzip', 'x-frame-options: DENY']);
        $this->assertTrue(isset($r['report_id']), 'Should return report_id');
        $this->assertTrue($r['overall_score'] >= 0, 'Should have overall score');
    }

    public function testPreviewScan(): void {
        $r = (new ScanService())->previewScan($this->sampleHtml(), 'https://preview.com');
        $this->assertTrue($r['overall_score'] >= 0);
    }

    public function testScoreInRange(): void {
        $r = (new ScanService())->runFullScan('https://test.com', $this->sampleHtml(), []);
        $this->assertTrue($r['overall_score'] >= 0 && $r['overall_score'] <= 100);
    }

    public function testPersistenceWithLead(): void {
        $db = \App\Core\Database::getInstance()->getConnection();
        $db->exec("INSERT INTO leads (name, email, phone, status, consent_given, created_at, updated_at) VALUES ('Test','t@t.com','050','new',1,datetime('now'),datetime('now'))");
        $leadId = (int) $db->lastInsertId();
        $r = (new ScanService())->runFullScan('https://lead-scan.com', $this->sampleHtml(), [], $leadId);
        $report = $db->query("SELECT * FROM audit_reports WHERE id={$r['report_id']}")->fetch();
        $this->assertEquals($leadId, (int)$report['lead_id'], 'Report should link to lead');
    }

    public function testPersistenceWithoutLead(): void {
        $r = (new ScanService())->runFullScan('https://no-lead.com', $this->sampleHtml(), []);
        $db = \App\Core\Database::getInstance()->getConnection();
        $report = $db->query("SELECT * FROM audit_reports WHERE id={$r['report_id']}")->fetch();
        $this->assertNull($report['lead_id'], 'Report without lead should have null lead_id');
    }

    public function testAnalysisGenerated(): void {
        $r = (new ScanService())->runFullScan('https://analysis.com', $this->sampleHtml(), []);
        $this->assertTrue(isset($r['analysis']['overall_assessment']));
        $this->assertTrue(strlen($r['analysis']['overall_assessment']) > 0);
    }

    public function testReportContainsAllScanners(): void {
        $r = (new ScanService())->runFullScan('https://all-scanners.com', $this->sampleHtml(), []);
        $report = $r['full_report'];
        foreach (['seo','performance','security','accessibility'] as $key) {
            $this->assertTrue(isset($report[$key]), "Missing scanner: $key");
        }
    }

    public function testRecommendationsGenerated(): void {
        $r = (new ScanService())->runFullScan('https://recs.com', $this->sampleHtml(), []);
        $this->assertTrue(count($r['recommendations']) > 0, 'Should generate recommendations');
    }
}
