<?php

use App\LeadEngine\ApprovalToken;
use App\LeadEngine\AuditResult;
use App\LeadEngine\CsvImporter;
use App\LeadEngine\DraftWriter;
use App\LeadEngine\HotScore;
use App\LeadEngine\HtmlSignals;
use App\LeadEngine\LeadEngineConfig;
use App\LeadEngine\PoliteFetcher;
use App\LeadEngine\SendGuard;
use App\Repositories\DoNotContactRepository;
use App\Repositories\LeadEngineRepository;
use App\Repositories\OutreachRepository;
use App\Repositories\ProspectRepository;

/**
 * Lead Engine tests — the scoring formula, the HTML signals, dedup, the
 * approval token, and the send guardrails.
 *
 * No network: every test works on fixture HTML or database rows.
 */
class LeadEngineTest extends TestCase
{
    private ProspectRepository $prospects;
    private OutreachRepository $outreach;
    private DoNotContactRepository $dnc;

    public function setUp(): void
    {
        resetDatabase();
        $this->prospects = new ProspectRepository();
        $this->outreach = new OutreachRepository();
        $this->dnc = new DoNotContactRepository();
        LeadEngineConfig::flush();
    }

    public function runAll(): array
    {
        return $this->runTests([
            'testHotScoreIsZeroForAPerfectSite',
            'testHotScoreIsHighForANeglectedSite',
            'testAdsBonusRaisesScoreAndCapsAt100',
            'testUnreachableSiteScoresZero',
            'testPrimaryIssuePriorityOrder',
            'testAnalyticsSignalDetection',
            'testAccessibilityStatementNeedsMoreThanTheWord',
            'testClickToCallAndViewportSignals',
            'testContactFormDetection',
            'testEmailExtractionFiltersJunk',
            'testPhoneExtractionNormalizesIsraeliNumbers',
            'testJsonLdPersonNameExtraction',
            'testFirstNameStripsHonorifics',
            'testDomainKeyNormalization',
            'testRobotsTxtParsingAndLongestMatch',
            'testDedupBlocksSameDomainWithinWindow',
            'testDoNotContactBlocksByDomainAndEmail',
            'testApprovalTokenRoundTripAndTamperRejection',
            'testApprovalTokenIsSingleUse',
            'testExpiredApprovalTokenIsRejected',
            'testSendGuardRequiresVideoUrl',
            'testSendGuardEnforcesDailyCap',
            'testSendGuardRejectsWeekendAndOffHours',
            'testSendGuardBlocksWhatsappColdOutreach',
            'testKillSwitchBlocksEverything',
            'testTemplateDraftHasNoBannedPhrases',
            'testVideoBriefContainsAuditNumbers',
            'testFooterCarriesIdentityAndOptOut',
            'testCsvImportParsesAliasesAndSkipsBadRows',
            'testSettingsOverrideEnvDefaults',
        ]);
    }

    // ------------------------------------------------------------- hot_score

    /** A site with nothing wrong is worth 0 to contact (spec §6) */
    public function testHotScoreIsZeroForAPerfectSite(): void
    {
        $audit = $this->audit([
            'perfMobile' => 100, 'a11yScore' => 100, 'seoScore' => 100,
            'hasAnalytics' => true, 'hasAccessibilityStatement' => true, 'hasClickToCall' => true,
        ]);
        $this->assertEquals(0, HotScore::compute($audit), 'Perfect site should score 0');
    }

    /** Worst case hits the 100 ceiling */
    public function testHotScoreIsHighForANeglectedSite(): void
    {
        $audit = $this->audit([
            'perfMobile' => 0, 'a11yScore' => 0, 'seoScore' => 0,
            'hasAnalytics' => false, 'hasAccessibilityStatement' => false, 'hasClickToCall' => false,
        ]);
        $this->assertEquals(100, HotScore::compute($audit), 'Worst site should score 100');

        // A realistic mid-range site: slow, no tracking, no a11y statement
        $mid = $this->audit([
            'perfMobile' => 30, 'a11yScore' => 65, 'seoScore' => 70,
            'hasAnalytics' => false, 'hasAccessibilityStatement' => false, 'hasClickToCall' => true,
        ]);
        // 70*.30 + 35*.20 + 100*.15 + 100*.15 + 30*.10 + 0 = 21+7+15+15+3 = 61
        $this->assertEquals(61, HotScore::compute($mid), 'Mid-range site score');
    }

    public function testAdsBonusRaisesScoreAndCapsAt100(): void
    {
        $audit = $this->audit([
            'perfMobile' => 50, 'a11yScore' => 50, 'seoScore' => 50,
            'hasAnalytics' => false, 'hasAccessibilityStatement' => false, 'hasClickToCall' => false,
        ]);
        // 50*.30 + 50*.20 + 100*.15 + 100*.15 + 50*.10 + 100*.10 = 15+10+15+15+5+10 = 70
        $base = HotScore::compute($audit, false);
        $this->assertEquals(70, $base, 'Base score without ads bonus');
        $this->assertEquals(98, HotScore::compute($audit, true), '70 x 1.4 = 98');

        $bad = $this->audit(['perfMobile' => 10, 'a11yScore' => 10, 'seoScore' => 10]);
        $this->assertEquals(100, HotScore::compute($bad, true), 'Ads bonus must cap at 100');
    }

    /**
     * A homepage we could not fetch must not score 100 off all-zero inputs —
     * that would flood the queue with dead domains.
     */
    public function testUnreachableSiteScoresZero(): void
    {
        $audit = $this->audit(['fetchOk' => false, 'perfMobile' => 0, 'a11yScore' => 0, 'seoScore' => 0]);
        $this->assertEquals(0, HotScore::compute($audit), 'Unreachable site must score 0');
    }

    public function testPrimaryIssuePriorityOrder(): void
    {
        // broken_form wins over everything
        $audit = $this->audit(['perfMobile' => 5, 'hasAnalytics' => false]);
        $this->assertEquals('broken_form', HotScore::primaryIssue($audit, true, true));

        // ads + no analytics beats slow mobile
        $this->assertEquals('no_analytics_with_ads', HotScore::primaryIssue($audit, true, false));

        // no ads: slow mobile wins
        $this->assertEquals('slow_mobile', HotScore::primaryIssue($audit, false, false));

        // fast site, no a11y statement and weak a11y score
        $a11y = $this->audit([
            'perfMobile' => 90, 'a11yScore' => 50,
            'hasAccessibilityStatement' => false, 'hasClickToCall' => true, 'seoScore' => 90,
        ]);
        $this->assertEquals('no_accessibility', HotScore::primaryIssue($a11y));

        // everything fine except click-to-call
        $ctc = $this->audit([
            'perfMobile' => 90, 'a11yScore' => 90, 'seoScore' => 90,
            'hasAccessibilityStatement' => true, 'hasClickToCall' => false,
        ]);
        $this->assertEquals('no_click_to_call', HotScore::primaryIssue($ctc));

        // only SEO left
        $seo = $this->audit([
            'perfMobile' => 90, 'a11yScore' => 90, 'seoScore' => 40,
            'hasAccessibilityStatement' => true, 'hasClickToCall' => true,
        ]);
        $this->assertEquals('weak_seo', HotScore::primaryIssue($seo));

        // nothing to sell
        $clean = $this->audit([
            'perfMobile' => 95, 'a11yScore' => 95, 'seoScore' => 95,
            'hasAccessibilityStatement' => true, 'hasClickToCall' => true, 'hasAnalytics' => true,
        ]);
        $this->assertEquals('none', HotScore::primaryIssue($clean));
    }

    // --------------------------------------------------------- HTML signals

    public function testAnalyticsSignalDetection(): void
    {
        $this->assertTrue(HtmlSignals::hasAnalytics('<script>gtag("config","G-ABC");</script>'), 'gtag');
        $this->assertTrue(HtmlSignals::hasAnalytics('<script src="https://www.googletagmanager.com/gtm.js"></script>'), 'GTM');
        $this->assertTrue(HtmlSignals::hasAnalytics("ga('create', 'UA-1', 'auto');"), 'universal GA');
        $this->assertFalse(HtmlSignals::hasAnalytics('<p>אנחנו מנתחים את הנתונים שלכם</p>'), 'prose is not analytics');

        $this->assertTrue(HtmlSignals::hasMetaPixel("fbq('init', '123');"), 'fbq');
        $this->assertTrue(HtmlSignals::hasMetaPixel('<script src="https://connect.facebook.net/en_US/fbevents.js">'), 'fbevents');
        $this->assertFalse(HtmlSignals::hasMetaPixel('<a href="https://facebook.com/mypage">עקבו אחרינו</a>'), 'a link is not a pixel');
    }

    /**
     * The word "נגישות" in body copy is not a published statement — only a link
     * or an explicit "הצהרת נגישות" counts.
     */
    public function testAccessibilityStatementNeedsMoreThanTheWord(): void
    {
        $this->assertTrue(
            HtmlSignals::hasAccessibilityStatement('<a href="/accessibility">נגישות</a>'),
            'href=/accessibility counts'
        );
        $this->assertTrue(
            HtmlSignals::hasAccessibilityStatement('<footer><a href="/x">הצהרת נגישות</a></footer>'),
            'explicit phrase counts'
        );
        $this->assertFalse(
            HtmlSignals::hasAccessibilityStatement('<p>אנחנו מאמינים בנגישות לכולם ובשירות אדיב</p>'),
            'marketing copy about accessibility does not count'
        );
    }

    public function testClickToCallAndViewportSignals(): void
    {
        $this->assertTrue(HtmlSignals::hasClickToCall('<a href="tel:03-1234567">התקשרו</a>'));
        $this->assertTrue(HtmlSignals::hasClickToCall("<a href='tel:+972501234567'>call</a>"));
        $this->assertFalse(HtmlSignals::hasClickToCall('<p>טלפון: 03-1234567</p>'), 'plain text number is not click-to-call');

        $this->assertTrue(HtmlSignals::hasMobileViewport('<meta name="viewport" content="width=device-width, initial-scale=1">'));
        $this->assertFalse(HtmlSignals::hasMobileViewport('<meta name="viewport" content="width=1024">'), 'fixed width is not responsive');
        $this->assertFalse(HtmlSignals::hasMobileViewport('<html><head></head></html>'), 'no viewport tag');
    }

    public function testContactFormDetection(): void
    {
        $this->assertTrue(
            HtmlSignals::hasContactForm('<form action="/send"><input type="email" name="email"><button>שלח</button></form>'),
            'email input'
        );
        $this->assertTrue(
            HtmlSignals::hasContactForm('<form><input name="שם"><textarea name="הודעה"></textarea></form>'),
            'Hebrew field names'
        );
        $this->assertTrue(
            HtmlSignals::hasContactForm('<iframe src="https://docs.google.com/forms/d/e/abc/viewform"></iframe>'),
            'embedded Google Form'
        );
        $this->assertFalse(
            HtmlSignals::hasContactForm('<form action="/search"><input type="search" name="q"></form>'),
            'a search box is not a contact form'
        );
    }

    public function testEmailExtractionFiltersJunk(): void
    {
        $html = '<a href="mailto:yossi@cohen-dental.co.il">מייל</a>
                 <p>office@cohen-dental.co.il</p>
                 <img src="logo@2x.png">
                 <p>no-reply@mailer.example.com</p>';
        $emails = HtmlSignals::extractEmails($html);

        $this->assertTrue(in_array('yossi@cohen-dental.co.il', $emails, true), 'mailto address found');
        $this->assertTrue(in_array('office@cohen-dental.co.il', $emails, true), 'bare address found');
        $this->assertFalse(in_array('logo@2x.png', $emails, true), 'asset filename excluded');
        $this->assertFalse(in_array('no-reply@mailer.example.com', $emails, true), 'no-reply excluded');
    }

    public function testPhoneExtractionNormalizesIsraeliNumbers(): void
    {
        $phones = HtmlSignals::extractPhones('<a href="tel:+972-50-123-4567">נייד</a><p>03-6543210</p>');
        $this->assertTrue(in_array('0501234567', $phones, true), '+972 normalized to leading 0');
        $this->assertTrue(in_array('036543210', $phones, true), 'landline digits only');
    }

    public function testJsonLdPersonNameExtraction(): void
    {
        $html = '<script type="application/ld+json">
            {"@type":"Dentist","name":"מרפאת כהן","founder":{"@type":"Person","name":"יוסי כהן"}}
        </script>';
        $blocks = HtmlSignals::extractJsonLd($html);
        $this->assertEquals(1, count($blocks), 'one JSON-LD block parsed');
        $this->assertEquals('יוסי כהן', HtmlSignals::personNameFromJsonLd($blocks));

        // A company name in the founder slot must be rejected
        $company = HtmlSignals::extractJsonLd(
            '<script type="application/ld+json">{"founder":{"name":"כהן ובניו בע\"מ"}}</script>'
        );
        $this->assertNull(HtmlSignals::personNameFromJsonLd($company), 'company name is not a person');

        // Malformed JSON must not throw
        $this->assertEquals(0, count(HtmlSignals::extractJsonLd('<script type="application/ld+json">{oops</script>')));
    }

    public function testFirstNameStripsHonorifics(): void
    {
        $this->assertEquals('יוסי', HtmlSignals::firstName('יוסי כהן'));
        $this->assertEquals('דנה', HtmlSignals::firstName('ד"ר דנה לוי'));
        $this->assertEquals('Sarah', HtmlSignals::firstName('Dr. Sarah Klein'));
        $this->assertEquals('', HtmlSignals::firstName(''));
    }

    // ------------------------------------------------------------- fetching

    public function testDomainKeyNormalization(): void
    {
        $this->assertEquals('cohen-dental.co.il', PoliteFetcher::domainKey('https://www.cohen-dental.co.il/about'));
        $this->assertEquals('cohen-dental.co.il', PoliteFetcher::domainKey('HTTP://Cohen-Dental.co.il'));
        $this->assertEquals('example.com', PoliteFetcher::domainKey('example.com'));

        $this->assertEquals('https://example.co.il', PoliteFetcher::normalizeUrl('example.co.il'));
        $this->assertEquals('https://example.co.il', PoliteFetcher::normalizeUrl('https://example.co.il/'));
        $this->assertEquals('', PoliteFetcher::normalizeUrl('  '));
    }

    public function testRobotsTxtParsingAndLongestMatch(): void
    {
        $rules = PoliteFetcher::parseRobots("User-agent: *\nDisallow: /private\nAllow: /private/public\n");
        $this->assertEquals(2, count($rules), 'two directives for the * group');

        // Our own UA group wins over *
        $specific = PoliteFetcher::parseRobots(
            "User-agent: *\nDisallow: /\n\nUser-agent: LandingFlowBot\nDisallow: /admin\n"
        );
        $this->assertEquals(1, count($specific), 'only the LandingFlowBot group applies');
        $this->assertEquals('/admin', $specific[0][1]);

        // Comments and blank lines are ignored
        $commented = PoliteFetcher::parseRobots("# a comment\nUser-agent: *\nDisallow: /tmp # trailing\n");
        $this->assertEquals('/tmp', $commented[0][1], 'trailing comment stripped');
    }

    // ----------------------------------------------------------------- dedup

    public function testDedupBlocksSameDomainWithinWindow(): void
    {
        $pipeline = new \App\Services\LeadEnginePipeline();

        $first = $pipeline->addProspect([
            'business_name' => 'מרפאת כהן', 'url' => 'https://www.cohen-dental.co.il',
        ]);
        $this->assertTrue($first['created'], 'first insert succeeds');
        $this->assertGreaterThan(0, $first['prospect_id']);

        // Same domain, different URL shape → still a duplicate
        $second = $pipeline->addProspect([
            'business_name' => 'Cohen Dental', 'url' => 'http://cohen-dental.co.il/contact',
        ]);
        $this->assertFalse($second['created'], 'duplicate domain rejected');
        $this->assertEquals('duplicate', $second['reason']);
        $this->assertEquals($first['prospect_id'], $second['prospect_id'], 'points at the existing row');

        // Missing URL is rejected outright
        $this->assertFalse($pipeline->addProspect(['business_name' => 'No Site'])['created']);
    }

    public function testDoNotContactBlocksByDomainAndEmail(): void
    {
        $this->dnc->add('blocked.co.il', null, null, 'בקשת הסרה');
        $this->dnc->add(null, 'stop@other.co.il', null, 'בקשת הסרה');

        $this->assertTrue($this->dnc->isBlocked('blocked.co.il'), 'blocked by domain');
        $this->assertTrue($this->dnc->isBlocked('www.blocked.co.il'), 'www stripped before matching');
        $this->assertTrue($this->dnc->isBlocked(null, 'stop@other.co.il'), 'blocked by email');
        $this->assertTrue($this->dnc->isBlocked(null, 'STOP@other.co.il'), 'email match is case-insensitive');
        $this->assertFalse($this->dnc->isBlocked('allowed.co.il', 'ok@allowed.co.il'), 'unrelated prospect allowed');
        $this->assertFalse($this->dnc->isBlocked(), 'no identifiers means no block');

        // Sourcing must refuse a blocked domain
        $result = (new \App\Services\LeadEnginePipeline())->addProspect([
            'business_name' => 'Blocked', 'url' => 'https://blocked.co.il',
        ]);
        $this->assertFalse($result['created']);
        $this->assertEquals('do_not_contact', $result['reason']);
    }

    // -------------------------------------------------------- approval token

    public function testApprovalTokenRoundTripAndTamperRejection(): void
    {
        $issued = ApprovalToken::issue(42);

        $this->assertEquals(64, strlen($issued['token']), 'token is 32 random bytes hex');
        $this->assertEquals(64, strlen($issued['hash']), 'hash is sha256 hex');
        $this->assertNotEquals($issued['token'], $issued['hash'], 'the raw token is never the stored value');

        $this->assertTrue(ApprovalToken::matches(42, $issued['token'], $issued['hash']), 'correct token verifies');
        $this->assertFalse(ApprovalToken::matches(43, $issued['token'], $issued['hash']), 'token is bound to one draft');
        $this->assertFalse(ApprovalToken::matches(42, 'deadbeef', $issued['hash']), 'forged token rejected');
        $this->assertFalse(ApprovalToken::matches(42, '', $issued['hash']), 'empty token rejected');
    }

    public function testApprovalTokenIsSingleUse(): void
    {
        $draftId = $this->seedDraft();
        $issued = ApprovalToken::issue($draftId);
        $this->outreach->updateDraft($draftId, [
            'approval_token'   => $issued['hash'],
            'token_expires_at' => $issued['expires_at'],
            'token_used_at'    => null,
        ]);

        $hash = ApprovalToken::hashFor($draftId, $issued['token']);
        $this->assertTrue($this->outreach->consumeToken($draftId, $hash), 'first use succeeds');
        $this->assertFalse($this->outreach->consumeToken($draftId, $hash), 'replay is rejected');
    }

    public function testExpiredApprovalTokenIsRejected(): void
    {
        $draftId = $this->seedDraft();
        $issued = ApprovalToken::issue($draftId);
        $this->outreach->updateDraft($draftId, [
            'approval_token'   => $issued['hash'],
            // 73 hours ago — past the 72h TTL
            'token_expires_at' => gmdate('Y-m-d H:i:s', time() - 3600),
            'token_used_at'    => null,
        ]);

        $hash = ApprovalToken::hashFor($draftId, $issued['token']);
        $this->assertFalse($this->outreach->consumeToken($draftId, $hash), 'expired token rejected');
    }

    // ---------------------------------------------------------- send guards

    public function testSendGuardRequiresVideoUrl(): void
    {
        $guard = new SendGuard($this->outreach, $this->dnc);
        $wednesdayNoon = new \DateTimeImmutable('2026-08-19 12:00:00', new \DateTimeZone('Asia/Jerusalem'));

        $noVideo = $guard->check($this->draftRow(['video_url' => '']), $wednesdayNoon);
        $this->assertFalse($noVideo['allowed'], 'no video means no send');
        $this->assertTrue($this->hasBlocker($noVideo, 'סרטון'), 'blocker mentions the video');

        $badVideo = $guard->check($this->draftRow(['video_url' => 'not-a-url']), $wednesdayNoon);
        $this->assertFalse($badVideo['allowed'], 'malformed video URL rejected');

        $ok = $guard->check($this->draftRow(), $wednesdayNoon);
        $this->assertTrue($ok['allowed'], 'a complete draft in-window passes');

        // Approval-time check enforces the same rule
        $blockers = $guard->checkApprovable($this->draftRow(['video_url' => '']));
        $this->assertGreaterThan(0, count($blockers), 'cannot approve without a video');
        $this->assertEquals(0, count($guard->checkApprovable($this->draftRow())), 'complete draft is approvable');
    }

    public function testSendGuardEnforcesDailyCap(): void
    {
        (new LeadEngineRepository())->setSetting('max_daily_sends', '2');
        (new LeadEngineRepository())->setSetting('min_minutes_between_sends', '0');
        LeadEngineConfig::flush();

        $wednesdayNoon = new \DateTimeImmutable('2026-08-19 12:00:00', new \DateTimeZone('Asia/Jerusalem'));
        $guard = new SendGuard($this->outreach, $this->dnc);

        $this->assertTrue($guard->check($this->draftRow(), $wednesdayNoon)['allowed'], 'allowed at 0 sends');

        // Two sends today reaches the cap
        for ($i = 0; $i < 2; $i++) {
            $id = $this->seedDraft();
            $this->outreach->updateDraft($id, ['status' => 'sent', 'sent_at' => date('Y-m-d H:i:s')]);
        }
        $this->assertEquals(2, $this->outreach->sentTodayCount(), 'two sends counted today');

        $capped = $guard->check($this->draftRow(), $wednesdayNoon);
        $this->assertFalse($capped['allowed'], 'daily cap blocks further sends');
        $this->assertTrue($this->hasBlocker($capped, 'מקסימום'), 'blocker mentions the cap');
    }

    public function testSendGuardRejectsWeekendAndOffHours(): void
    {
        $guard = new SendGuard($this->outreach, $this->dnc);
        $tz = new \DateTimeZone('Asia/Jerusalem');

        // 2026-08-21 is a Friday, 2026-08-22 a Saturday
        $friday = $guard->check($this->draftRow(), new \DateTimeImmutable('2026-08-21 12:00:00', $tz));
        $this->assertFalse($friday['allowed'], 'no sending on Friday');

        $saturday = $guard->check($this->draftRow(), new \DateTimeImmutable('2026-08-22 12:00:00', $tz));
        $this->assertFalse($saturday['allowed'], 'no sending on Saturday');

        // Sunday is a work day in Israel
        $sunday = $guard->check($this->draftRow(), new \DateTimeImmutable('2026-08-23 12:00:00', $tz));
        $this->assertTrue($sunday['allowed'], 'Sunday is a work day');

        $tooEarly = $guard->check($this->draftRow(), new \DateTimeImmutable('2026-08-19 07:30:00', $tz));
        $this->assertFalse($tooEarly['allowed'], 'before the window opens');

        $tooLate = $guard->check($this->draftRow(), new \DateTimeImmutable('2026-08-19 19:30:00', $tz));
        $this->assertFalse($tooLate['allowed'], 'after the window closes');
    }

    /** §11.4 — cold WhatsApp violates Meta policy and risks the number */
    public function testSendGuardBlocksWhatsappColdOutreach(): void
    {
        $guard = new SendGuard($this->outreach, $this->dnc);
        $result = $guard->check(
            $this->draftRow(['channel' => 'whatsapp']),
            new \DateTimeImmutable('2026-08-19 12:00:00', new \DateTimeZone('Asia/Jerusalem'))
        );
        $this->assertFalse($result['allowed'], 'WhatsApp cold outreach is blocked in code');
        $this->assertTrue($this->hasBlocker($result, 'וואטסאפ'));
    }

    public function testKillSwitchBlocksEverything(): void
    {
        (new LeadEngineRepository())->setSetting('sending_halted', '1');
        LeadEngineConfig::flush();

        $result = (new SendGuard($this->outreach, $this->dnc))->check(
            $this->draftRow(),
            new \DateTimeImmutable('2026-08-19 12:00:00', new \DateTimeZone('Asia/Jerusalem'))
        );
        $this->assertFalse($result['allowed'], 'halt switch blocks an otherwise valid send');
        $this->assertTrue($this->hasBlocker($result, 'מוקפאות'));
    }

    // --------------------------------------------------------------- drafts

    /** §8 forbids superlatives and agency self-introduction */
    public function testTemplateDraftHasNoBannedPhrases(): void
    {
        $writer = new DraftWriter(new \App\Services\OpenAiService());
        $audit = $this->audit([
            'perfMobile' => 28, 'a11yScore' => 40, 'seoScore' => 55,
            'hasAnalytics' => false, 'hasAccessibilityStatement' => false, 'hasClickToCall' => false,
        ]);
        $audit->primaryIssue = 'slow_mobile';

        $written = $writer->write([
            'business_name' => 'מרפאת שיניים ד"ר כהן',
            'domain'        => 'cohen-dental.co.il',
            'contact_name'  => 'יוסי כהן',
            'niche'         => 'dental_clinic',
            'city'          => 'תל אביב',
            'spends_on_ads' => 0,
        ], $audit);

        // No API key in tests, so this must be the template path
        $this->assertEquals('template', $written['generated_by'], 'falls back to template without an API key');

        $this->assertContains('היי יוסי', $written['body'], 'opens with the first name');
        $this->assertContains('28', $written['body'], 'quotes a concrete number from the audit');

        foreach (['סוכנות מובילה', 'הטוב ביותר', 'פורץ דרך', 'אנחנו מתמחים'] as $banned) {
            $this->assertFalse(str_contains($written['body'], $banned), "must not contain: $banned");
        }

        $words = count(preg_split('/\s+/u', trim($written['body'])) ?: []);
        $this->assertTrue($words <= DraftWriter::MAX_WORDS_EMAIL + 25, "email draft is $words words, near the 90 cap");

        // No contact name → neutral greeting, never "היי ,"
        $anon = $writer->write(['business_name' => 'עסק', 'domain' => 'x.co.il'], $audit);
        $this->assertContains('שלום', $anon['body'], 'neutral greeting without a name');
        $this->assertFalse(str_contains($anon['body'], 'היי ,'), 'no dangling comma greeting');
    }

    public function testVideoBriefContainsAuditNumbers(): void
    {
        $writer = new DraftWriter(new \App\Services\OpenAiService());
        $audit = $this->audit(['perfMobile' => 31, 'a11yScore' => 44, 'seoScore' => 60, 'hasAnalytics' => false]);
        $audit->hotScore = 78;

        $brief = $writer->videoBrief([
            'business_name' => 'מרפאת שיניים ד"ר כהן',
            'domain'        => 'cohen-dental.co.il',
            'contact_name'  => 'יוסי כהן',
            'spends_on_ads' => 1,
        ], $audit, 'no_analytics_with_ads');

        $this->assertContains('cohen-dental.co.il', $brief, 'domain in the brief');
        $this->assertContains('יוסי', $brief, 'contact name in the brief');
        $this->assertContains('0:00-0:10', $brief, 'timed segments');
        $this->assertContains('טאבים לפתוח מראש', $brief, 'tab list');
        $this->assertContains('31', $brief, 'the mobile score appears');
        $this->assertContains('מפרסם בפייסבוק: כן', $brief, 'ads flag surfaced');

        // A heuristic score must be labelled so it is never quoted as PageSpeed
        $this->assertContains('הערכה מקומית', $brief, 'heuristic perf score is flagged');
    }

    /** §11.2 — identified sender and a clear opt-out on every message */
    public function testFooterCarriesIdentityAndOptOut(): void
    {
        $footer = DraftWriter::footer();
        $this->assertContains('LandingFlow', $footer, 'sender identity present');
        $this->assertContains('הסר', $footer, 'opt-out instruction present');
        $this->assertContains('example.com/data-deletion', $footer, 'opt-out URL present');

        // The sender appends it, so it cannot be edited away in the panel
        $composed = (new \App\Services\OutreachSender())->composeBody([
            'body' => 'גוף ההודעה', 'video_url' => 'https://loom.com/share/abc',
        ]);
        $this->assertContains('גוף ההודעה', $composed);
        $this->assertContains('https://loom.com/share/abc', $composed, 'video link included');
        $this->assertContains('הסר', $composed, 'footer appended at send time');
    }

    // ------------------------------------------------------------------ CSV

    public function testCsvImportParsesAliasesAndSkipsBadRows(): void
    {
        $csv = STORAGE_PATH . '/lead-engine-test-import.csv';
        @mkdir(dirname($csv), 0755, true);
        file_put_contents($csv, "\xEF\xBB\xBF" . implode("\n", [
            'שם העסק,אתר,טלפון,עיר,נישה,מפרסם',
            'מרפאת כהן,cohen-dental.co.il,03-1234567,תל אביב,dental_clinic,כן',
            'עסק בלי אתר,,050-1111111,חיפה,law_firm,לא',
            'לוי ושותפים,https://levi-law.co.il/,02-9999999,ירושלים,law_firm,no',
            '',
        ]));

        try {
            $parsed = (new CsvImporter())->parse($csv);

            $this->assertEquals(2, count($parsed['rows']), 'two valid rows');
            $this->assertEquals(1, $parsed['skipped'], 'the row without a URL is skipped');

            $first = $parsed['rows'][0];
            $this->assertEquals('מרפאת כהן', $first['business_name'], 'BOM-prefixed Hebrew header mapped');
            $this->assertEquals('cohen-dental.co.il', $first['domain'], 'domain normalized');
            $this->assertEquals('https://cohen-dental.co.il', $first['url'], 'scheme added');
            $this->assertEquals(1, $first['spends_on_ads'], '"כן" parsed as true');
            $this->assertEquals('csv', $first['source']);

            $this->assertEquals(0, $parsed['rows'][1]['spends_on_ads'], '"no" parsed as false');
            $this->assertEquals('levi-law.co.il', $parsed['rows'][1]['domain'], 'trailing slash stripped');
        } finally {
            @unlink($csv);
        }
    }

    // --------------------------------------------------------------- config

    public function testSettingsOverrideEnvDefaults(): void
    {
        $this->assertEquals(55, LeadEngineConfig::int('hot_score_threshold'), 'env baseline');

        (new LeadEngineRepository())->setSetting('hot_score_threshold', '70');
        LeadEngineConfig::flush();
        $this->assertEquals(70, LeadEngineConfig::int('hot_score_threshold'), 'DB overrides env');

        // setSetting is an upsert — writing twice must not duplicate the row
        (new LeadEngineRepository())->setSetting('hot_score_threshold', '65');
        LeadEngineConfig::flush();
        $this->assertEquals(65, LeadEngineConfig::int('hot_score_threshold'), 'second write updates in place');

        (new LeadEngineRepository())->setSetting('active_niches', 'dental_clinic, law_firm ,');
        LeadEngineConfig::flush();
        $niches = LeadEngineConfig::list('active_niches');
        $this->assertEquals(2, count($niches), 'blank entries dropped');
        $this->assertEquals('law_firm', $niches[1], 'values trimmed');
    }

    // --------------------------------------------------------------- helpers

    /** @param array<string,mixed> $overrides */
    private function audit(array $overrides = []): AuditResult
    {
        $audit = new AuditResult();
        $audit->url = 'https://cohen-dental.co.il';
        $audit->fetchOk = true;
        $audit->httpStatus = 200;
        $audit->perfMobile = 50;
        $audit->a11yScore = 50;
        $audit->seoScore = 50;
        $audit->securityScore = 50;
        $audit->hasSsl = true;
        $audit->mobileViewportOk = true;
        $audit->contactFormFound = true;

        foreach ($overrides as $property => $value) {
            $audit->$property = $value;
        }
        return $audit;
    }

    /** A prospect + draft in the database; returns the draft id */
    private function seedDraft(): int
    {
        static $counter = 0;
        $counter++;

        $prospectId = $this->prospects->create([
            'business_name' => 'עסק ' . $counter,
            'domain'        => 'seed' . $counter . '.co.il',
            'url'           => 'https://seed' . $counter . '.co.il',
            'email'         => 'owner' . $counter . '@seed' . $counter . '.co.il',
            'contact_name'  => 'יוסי כהן',
            'status'        => 'drafted',
        ]);

        return $this->outreach->createDraft([
            'prospect_id' => $prospectId,
            'channel'     => 'email',
            'subject'     => 'נושא',
            'body'        => 'גוף ההודעה',
            'video_brief' => 'תסריט',
            'video_url'   => 'https://www.loom.com/share/abc123',
            'status'      => 'draft',
        ]);
    }

    /**
     * An in-memory draft row shaped like findDraftWithContext() output, so guard
     * tests do not depend on the join.
     */
    private function draftRow(array $overrides = []): array
    {
        return array_merge([
            'id'             => 1,
            'prospect_id'    => 1,
            'status'         => 'draft',
            'channel'        => 'email',
            'subject'        => 'האתר של מרפאת כהן נטען לאט במובייל',
            'body'           => 'היי יוסי, בדקתי את האתר שלכם בטלפון.',
            'video_url'      => 'https://www.loom.com/share/abc123',
            'video_brief'    => 'תסריט',
            'prospect_email' => 'yossi@cohen-dental.co.il',
            'prospect_phone' => '036543210',
            'domain'         => 'cohen-dental.co.il',
            'contact_name'   => 'יוסי כהן',
            'followup_step'  => 0,
            'perf_source'    => 'pagespeed',
            'approved_at'    => null,
        ], $overrides);
    }

    private function hasBlocker(array $guardResult, string $needle): bool
    {
        foreach ($guardResult['blockers'] as $blocker) {
            if (str_contains($blocker, $needle)) {
                return true;
            }
        }
        return false;
    }
}
