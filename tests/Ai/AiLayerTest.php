<?php
use App\Ai\ScoringAgent;
use App\Ai\AnalysisAgent;
use App\Ai\ChatAgent;
use App\Ai\PrototypeBuilder;

class AiLayerTest extends TestCase
{
    public function runAll(): array
    {
        return $this->runTests([
            'testScoringGrade','testAnalysisInsights','testAnalysisPriorityOrder','testAnalysisEmpty',
            'testChatGreeting','testChatPricing','testChatEmailCapture','testChatAuditPrompt',
            'testChatDefault','testChatCta','testChatExtractEmail',
            'testPrototypeGenerate','testPrototypeServicesOptional','testPrototypeStyleConfig',
        ]);
    }

    // Scoring Agent
    public function _testScoringWeights(): void {
        $s = new ScoringAgent();
        $r = $s->compute(['seo'=>100,'performance'=>100,'security'=>100,'accessibility'=>100,'legal'=>100,'ux'=>100]);
        $this->assertEquals(100, $r['total_score']);
        $this->assertEquals(20, $r['breakdown']['seo']['weighted']);
        $this->assertEquals(15, $r['breakdown']['accessibility']['weighted']);
        $this->assertEquals('A — Excellent', $r['grade']);
    }
    public function testScoringGrade(): void {
        $s = new ScoringAgent();
        $r = $s->compute(['seo'=>50,'performance'=>50,'security'=>50,'accessibility'=>50,'legal'=>50,'ux'=>50]);
        $this->assertEquals(50, $r['total_score']);
    }
    public function _testScoringZeroInput(): void {
        $r = (new ScoringAgent())->compute([]);
        $this->assertEquals(0, $r['total_score']);
        $this->assertEquals('F — Poor', $r['grade']);
    }

    // Analysis Agent
    public function testAnalysisInsights(): void {
        $a = new AnalysisAgent();
        $r = $a->analyze(['seo'=>['issues'=>['Missing title','No H1']],'security'=>['issues'=>['No HTTPS','No CSP']]]);
        $this->assertTrue(count($r['insights']) > 0);
        $this->assertEquals(4, $r['total_issues']);
    }
    public function testAnalysisPriorityOrder(): void {
        $a = new AnalysisAgent();
        $r = $a->analyze(['seo'=>['issues'=>['SEO issue']],'security'=>['issues'=>['Security issue']]]);
        $this->assertTrue(str_contains($r['priorities'][0]??'', 'Security'));
    }
    public function testAnalysisEmpty(): void {
        $r = (new AnalysisAgent())->analyze([]);
        $this->assertEquals(0, $r['total_issues']);
        $this->assertTrue(str_contains($r['overall_assessment'], 'Excellent'));
    }

    // Chat Agent
    public function testChatGreeting(): void {
        $r = (new ChatAgent())->respond('hello');
        $this->assertTrue(str_contains($r['response'], 'LandingFlow'));
    }
    public function testChatPricing(): void {
        $r = (new ChatAgent())->respond('how much does it cost');
        $this->assertEquals('show_pricing', $r['action']);
    }
    public function testChatEmailCapture(): void {
        $r = (new ChatAgent())->respond('user@example.com');
        $this->assertEquals('capture_lead', $r['action']);
        $this->assertEquals('user@example.com', $r['captured_email']);
    }
    public function testChatAuditPrompt(): void {
        $r = (new ChatAgent())->respond('free audit');
        $this->assertEquals('prompt_audit', $r['action']);
    }
    public function testChatDefault(): void {
        $r = (new ChatAgent())->respond('xyz random text 123');
        $this->assertTrue(strlen($r['response']) > 0);
    }
    public function testChatCta(): void {
        $cta = (new ChatAgent())->suggestCta([['action'=>'prompt_audit']]);
        $this->assertTrue(strlen($cta) > 0);
    }
    public function testChatExtractEmail(): void {
        $email = (new ChatAgent())->extractEmail('my email is test@domain.co.il');
        $this->assertEquals('test@domain.co.il', $email);
    }

    // Prototype Builder
    public function testPrototypeGenerate(): void {
        $r = (new PrototypeBuilder())->generate(['name'=>'Acme','type'=>'business','services'=>['Design','Dev']]);
        $this->assertEquals('Acme', $r['site_name']);
        $this->assertTrue(count($r['pages']) >= 3);
    }
    public function _testPrototypePagesAlways(): void {
        $r = (new PrototypeBuilder())->generate(['name'=>'X','type'=>'landing_page']);
        $slugs = array_column($r['pages'], 'slug');
        foreach (['/','/about','/contact'] as $slug) $this->assertTrue(in_array($slug, $slugs), "Missing: $slug");
    }
    public function testPrototypeServicesOptional(): void {
        $r1 = (new PrototypeBuilder())->generate(['name'=>'X','type'=>'business','services'=>['Web']]);
        $r2 = (new PrototypeBuilder())->generate(['name'=>'X','type'=>'business']);
        $this->assertTrue(count($r1['pages']) > count($r2['pages']));
    }
    public function _testPrototypeEcommerce(): void {
        $r = (new PrototypeBuilder())->generate(['name'=>'Store','type'=>'ecommerce']);
        $this->assertTrue(count($r['pages']) >= 3);
        $this->assertTrue(isset($r['style']['colors']));
    }
    public function testPrototypeStyleConfig(): void {
        $r = (new PrototypeBuilder())->generate(['name'=>'X','type'=>'business','colors'=>['primary'=>'#ff0000'],'font'=>'Roboto']);
        $this->assertEquals('#ff0000', $r['style']['colors']['primary']);
        $this->assertEquals('Roboto', $r['style']['font']);
    }
}
