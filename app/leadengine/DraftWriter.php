<?php
namespace App\LeadEngine;

use App\Core\Logger;
use App\Services\OpenAiService;

/**
 * DraftWriter — Stage 4 (spec §8).
 *
 * Produces two things per prospect:
 *   A. the message draft (editable in the panel)
 *   B. video_brief — the card you read from while recording the Loom
 *
 * The LLM writes A when configured; otherwise a template does. B is always
 * built deterministically from audit numbers — it is a script for a human, and
 * a hallucinated timestamp or metric would be worse than useless.
 */
class DraftWriter
{
    /** Hard limits from §8 */
    public const MAX_WORDS_EMAIL = 90;
    public const MAX_WORDS_WHATSAPP = 60;

    private OpenAiService $llm;

    public function __construct(?OpenAiService $llm = null)
    {
        $this->llm = $llm ?? new OpenAiService();
    }

    /**
     * @param array $prospect Row from ProspectRepository
     * @return array{subject:string,body:string,video_brief:string,generated_by:string}
     */
    public function write(array $prospect, AuditResult $audit, string $channel = 'email'): array
    {
        $issue = $audit->primaryIssue !== 'none'
            ? $audit->primaryIssue
            : HotScore::primaryIssue($audit, (bool) ($prospect['spends_on_ads'] ?? false));

        $subject = $this->subject($prospect, $issue);
        $llmBody = $this->writeWithLlm($prospect, $audit, $issue, $channel);

        return [
            'subject'      => $subject,
            'body'         => $llmBody ?? $this->writeFromTemplate($prospect, $audit, $issue, $channel),
            'video_brief'  => $this->videoBrief($prospect, $audit, $issue),
            'generated_by' => $llmBody !== null ? 'llm' : 'template',
        ];
    }

    // ------------------------------------------------------------------ subject

    /** The primary issue is the subject line (§6) */
    private function subject(array $prospect, string $issue): string
    {
        $name = (string) ($prospect['business_name'] ?? '');
        return match ($issue) {
            'broken_form'           => "הטופס באתר של {$name} לא מגיע ליעד",
            'no_analytics_with_ads' => "אתם מפרסמים, אבל אין מעקב באתר של {$name}",
            'slow_mobile'           => "האתר של {$name} נטען לאט במובייל",
            'no_accessibility'      => "חסרה הצהרת נגישות באתר של {$name}",
            'no_click_to_call'      => "אי אפשר להתקשר בלחיצה מהאתר של {$name}",
            'weak_seo'              => "כמה דברים שמצאתי באתר של {$name}",
            default                 => "בדקתי את האתר של {$name}",
        };
    }

    // --------------------------------------------------------------- LLM draft

    private function writeWithLlm(array $prospect, AuditResult $audit, string $issue, string $channel): ?string
    {
        if (!$this->llm->isAvailable()) {
            return null;
        }

        $maxWords = $channel === 'whatsapp' ? self::MAX_WORDS_WHATSAPP : self::MAX_WORDS_EMAIL;
        $greetName = $this->greetingName($prospect);

        $system = <<<PROMPT
אתה כותב פנייה ראשונה קרה בעברית, מאדם אחד לאדם אחר. לא שיווק, לא סוכנות.

חוקים מחייבים:
- פתח בשם הפרטי של איש הקשר, אם קיים.
- השורה הראשונה היא הבעיה שמצאת. לא הצגה עצמית.
- שלב מספר קונקרטי אחד בלבד מנתוני הבדיקה שקיבלת. אל תמציא מספרים.
- סיים ב-CTA רך: הצעה לשלוח את הרשימה המלאה, גם אם לא נעבוד יחד.
- מקסימום {$maxWords} מילים. קצר יותר עדיף.

אסור בהחלט:
- סופרלטיבים ("הטוב ביותר", "מוביל", "פורץ דרך")
- "אנחנו סוכנות", "אנחנו מתמחים", כל הצגה עצמית של חברה
- יותר מאימוג'י אחד בכל ההודעה
- הבטחות על אחוזי שיפור או הכנסות
- שורת נושא (היא נכתבת בנפרד)
- חתימה, שם שולח, או פרטי קשר (נוספים אוטומטית)

החזר את גוף ההודעה בלבד, טקסט רגיל, בלי כותרות ובלי הסברים.
PROMPT;

        $facts = $this->factSheet($prospect, $audit, $issue);
        $user = "נתוני הבדיקה:\n{$facts}\n\n"
            . "הבעיה שנמכור: " . HotScore::issueLabel($issue) . "\n"
            . "המספר לצטט: " . HotScore::issueEvidence($issue, $audit) . "\n"
            . ($greetName !== null ? "שם לפתיחה: {$greetName}\n" : "אין שם איש קשר — פתח ב'שלום' בלי שם.\n")
            . "\nכתוב את גוף ההודעה.";

        try {
            $body = $this->llm->complete($system, $user, 600, 0.6);
        } catch (\Throwable $e) {
            Logger::warning('leadengine: LLM draft failed', ['message' => $e->getMessage()]);
            return null;
        }

        if ($body === null) {
            return null;
        }

        $body = trim(strip_tags($body));
        if ($body === '') {
            return null;
        }

        // Enforce the word cap the prompt asked for — models overshoot
        $words = preg_split('/\s+/u', $body) ?: [];
        if (count($words) > $maxWords + 25) {
            Logger::info('leadengine: LLM draft over word limit, using template', [
                'words' => count($words), 'limit' => $maxWords,
            ]);
            return null;
        }

        return $body;
    }

    // ---------------------------------------------------------- template draft

    /**
     * The no-LLM path. Deliberately plain: it follows the same rules the prompt
     * enforces, so a template draft is sendable as-is after a quick edit.
     */
    private function writeFromTemplate(array $prospect, AuditResult $audit, string $issue, string $channel): string
    {
        $greetName = $this->greetingName($prospect);
        $greeting = $greetName !== null ? "היי {$greetName}," : 'שלום,';
        $evidence = HotScore::issueEvidence($issue, $audit);
        $domain = (string) ($prospect['domain'] ?? '');

        $opening = match ($issue) {
            'broken_form' =>
                "נכנסתי ל-{$domain} וניסיתי לשלוח דרך טופס יצירת הקשר — ההודעה לא הגיעה ליעד.",
            'no_analytics_with_ads' =>
                "ראיתי שאתם מפרסמים, ונכנסתי ל-{$domain}. לא מצאתי בקוד האתר שום קוד מעקב — "
                . "כלומר אין דרך לדעת איזו מודעה מביאה פנייה.",
            'slow_mobile' =>
                "בדקתי את {$domain} בטלפון. {$evidence}. "
                . "בפועל זה אומר שחלק מהגולשים עוזבים לפני שהדף נפתח.",
            'no_accessibility' =>
                "עברתי על {$domain} ולא מצאתי הצהרת נגישות. "
                . "לאתר עסקי בישראל זו חשיפה משפטית, וזה גם התיקון הזול ביותר ברשימה.",
            'no_click_to_call' =>
                "נכנסתי ל-{$domain} מהטלפון וניסיתי להתקשר. "
                . "אין קישור חיוג באתר — גולש במובייל צריך להעתיק את המספר ידנית.",
            default =>
                "עברתי על {$domain} ומצאתי כמה דברים שמשפיעים ישירות על פניות. {$evidence}.",
        };

        $extras = $this->secondaryFindings($audit, $issue);
        $middle = $extras !== [] ? "\n\nעוד שני דברים שראיתי: " . implode('; ', array_slice($extras, 0, 2)) . '.' : '';

        $cta = "\n\nהכנתי רשימה מלאה של מה שמצאתי. אשמח לשלוח לך אותה — גם אם לא נעבוד יחד.";

        if ($channel === 'whatsapp') {
            // 60-word budget: greeting + opening + CTA only
            return $greeting . ' ' . $opening . $cta;
        }

        return $greeting . "\n\n" . $opening . $middle . $cta;
    }

    /** @return string[] */
    private function secondaryFindings(AuditResult $audit, string $primaryIssue): array
    {
        $findings = [];
        if (!$audit->hasSsl) {
            $findings[] = 'האתר לא מוגן ב-HTTPS';
        }
        if (!$audit->mobileViewportOk) {
            $findings[] = 'אין הגדרת viewport, כך שהתצוגה במובייל נשברת';
        }
        if (!$audit->hasClickToCall && $primaryIssue !== 'no_click_to_call') {
            $findings[] = 'אין לחיצה להתקשרות';
        }
        if (!$audit->hasAccessibilityStatement && $primaryIssue !== 'no_accessibility') {
            $findings[] = 'אין הצהרת נגישות';
        }
        if (!$audit->contactFormFound) {
            $findings[] = 'לא מצאתי טופס יצירת קשר בכלל';
        }
        if (!$audit->hasAnalytics && $primaryIssue !== 'no_analytics_with_ads') {
            $findings[] = 'אין קוד מעקב באתר';
        }
        return $findings;
    }

    // ----------------------------------------------------------- video brief

    /**
     * The recording card from §8 — business context, a timed segment plan, the
     * exact numbers to show, and which tabs to open first.
     */
    public function videoBrief(array $prospect, AuditResult $audit, string $issue): string
    {
        $business = (string) ($prospect['business_name'] ?? '');
        $domain = (string) ($prospect['domain'] ?? '');
        $contact = (string) ($prospect['contact_name'] ?? '');
        $greetName = $this->greetingName($prospect);
        $ads = !empty($prospect['spends_on_ads']);
        $perf = $audit->perfMobile ?? 0;
        $perfNote = $audit->perfSource === 'pagespeed' ? 'PageSpeed' : 'הערכה מקומית — אל תציג כ-PageSpeed';

        $openLine = $ads
            ? "\"היי " . ($greetName ?: '') . ", ראיתי את המודעות שלכם, נכנסתי לאתר\""
            : "\"היי " . ($greetName ?: '') . ", חיפשתי " . ($prospect['niche'] ?? 'עסק') . " ב"
              . ($prospect['city'] ?? 'אזור') . " והגעתי לאתר שלכם\"";

        $showLine = match ($issue) {
            'broken_form' =>
                "הראה: מלא את הטופס בשידור חי והראה שלא מגיעה תגובה/אישור.",
            'no_analytics_with_ads' =>
                "הראה: view-source, חפש 'gtag' ו-'fbq' — הראה שאין תוצאות. זה הרגע החזק בסרטון.",
            'slow_mobile' =>
                "הראה: טען את האתר בטלפון בשידור חי. ציון מהירות מובייל = {$perf}. ({$perfNote})",
            'no_accessibility' =>
                "הראה: גלול לפוטר — אין קישור להצהרת נגישות. ציון נגישות: {$audit->a11yScore}/100.",
            'no_click_to_call' =>
                "הראה: פתח את האתר בטלפון, לחץ על מספר הטלפון — לא קורה כלום.",
            default =>
                "הראה: את דף הבית, והצבע על הבעיה המרכזית.",
        };

        $painLine = match ($issue) {
            'no_analytics_with_ads' =>
                "\"אתם משלמים על מודעות, אבל אין מעקב — אתם לא יודעים איזו מודעה מביאה לקוח.\"",
            'slow_mobile' =>
                "\"האתר נטען לאט במובייל, ורוב הגולשים שלכם מגיעים מהטלפון. "
                . "כל שנייה של המתנה מורידה פניות.\"",
            'no_accessibility' =>
                "\"אין הצהרת נגישות. לאתר עסקי בישראל זו חשיפה משפטית ממשית, "
                . "וזה התיקון הכי זול ברשימה.\"",
            'no_click_to_call' =>
                "\"גולש שמחפש טלפון ולא יכול ללחוץ — פשוט חוזר לגוגל ומתקשר למתחרה.\"",
            'broken_form' =>
                "\"כל מי שמילא את הטופס בחודשים האחרונים — ההודעה שלו לא הגיעה אליכם.\"",
            default =>
                "\"כל אחד מהדברים האלה מוריד פניות, וכולם מתוקנים תוך יום עבודה.\"",
        };

        $secondary = $this->secondaryFindings($audit, $issue);
        $secondaryLine = $secondary !== []
            ? '           הצבע גם על: ' . implode(' · ', array_slice($secondary, 0, 3))
            : '';

        $tabs = ['האתר: https://' . $domain, 'view-source של דף הבית'];
        if ($audit->perfSource === 'pagespeed') {
            $tabs[] = 'דוח PageSpeed';
        }
        if ($issue === 'broken_form') {
            $tabs[] = 'עמוד יצירת הקשר';
        }

        $scores = sprintf(
            'מהירות מובייל: %s | נגישות: %d | SEO: %d | אבטחה: %d | hot score: %d',
            $audit->perfMobile ?? '?',
            $audit->a11yScore,
            $audit->seoScore,
            $audit->securityScore,
            $audit->hotScore
        );

        $lines = [
            "עסק: {$business} | דומיין: {$domain}",
            'איש קשר: ' . ($contact !== '' ? $contact : '— (לא נמצא)')
                . ' | מפרסם בפייסבוק: ' . ($ads ? 'כן' : 'לא נבדק'),
            'הבעיה למכירה: ' . HotScore::issueLabel($issue),
            $scores,
            '',
            '0:00-0:10  ' . $openLine,
            '0:10-0:50  ' . $showLine,
        ];
        if ($secondaryLine !== '') {
            $lines[] = $secondaryLine;
        }
        $lines[] = '0:50-2:30  ' . $painLine;
        $lines[] = '2:30-3:00  "הכנתי רשימה מלאה של מה שמצאתי, אשמח לשלוח בחינם."';
        $lines[] = '';
        $lines[] = 'טאבים לפתוח מראש: [' . implode('] [', $tabs) . ']';
        $lines[] = '';
        $lines[] = 'אל תגיד: שמות מתחרים, הבטחות באחוזים, "אני סוכנות".';

        if ($audit->perfSource === 'heuristic') {
            $lines[] = '⚠ ציון המהירות הוא הערכה מקומית ולא PageSpeed — אל תצטט אותו כמספר PageSpeed.';
        }

        return implode("\n", $lines);
    }

    // ----------------------------------------------------------------- helpers

    /** First name only, or null when we never found one (§7) */
    private function greetingName(array $prospect): ?string
    {
        $contactName = trim((string) ($prospect['contact_name'] ?? ''));
        if ($contactName === '') {
            return null;
        }
        $first = HtmlSignals::firstName($contactName);
        return $first !== '' ? $first : null;
    }

    /** The audit numbers handed to the LLM — nothing it can invent */
    private function factSheet(array $prospect, AuditResult $audit, string $issue): string
    {
        $rows = [
            'שם העסק'          => $prospect['business_name'] ?? '',
            'דומיין'           => $prospect['domain'] ?? '',
            'נישה'             => $prospect['niche'] ?? '',
            'עיר'              => $prospect['city'] ?? '',
            'איש קשר'          => $prospect['contact_name'] ?? '(לא נמצא)',
            'מפרסם במודעות'    => !empty($prospect['spends_on_ads']) ? 'כן' : 'לא ידוע',
            'מהירות מובייל'    => ($audit->perfMobile ?? '?') . '/100'
                                  . ($audit->perfSource === 'heuristic' ? ' (הערכה, לא PageSpeed)' : ' (PageSpeed)'),
            'ציון נגישות'      => $audit->a11yScore . '/100',
            'ציון SEO'         => $audit->seoScore . '/100',
            'HTTPS'            => $audit->hasSsl ? 'יש' : 'אין',
            'Analytics'        => $audit->hasAnalytics ? 'יש' : 'אין',
            'Meta Pixel'       => $audit->hasMetaPixel ? 'יש' : 'אין',
            'הצהרת נגישות'     => $audit->hasAccessibilityStatement ? 'יש' : 'אין',
            'לחיצה להתקשרות'   => $audit->hasClickToCall ? 'יש' : 'אין',
            'טופס יצירת קשר'   => $audit->contactFormFound ? 'נמצא' : 'לא נמצא',
        ];

        $out = [];
        foreach ($rows as $label => $value) {
            if ($value !== '' && $value !== null) {
                $out[] = "- {$label}: {$value}";
            }
        }
        return implode("\n", $out);
    }

    /**
     * Required footer on every outbound message (§11.2): identified sender plus
     * a clear opt-out. Appended at send time, never stored in the editable body.
     */
    public static function footer(): string
    {
        $identity = LeadEngineConfig::senderIdentity();
        $unsubscribe = LeadEngineConfig::unsubscribeUrl();

        return "\n\n—\n{$identity}\n"
            . "אם אינך מעוניין לקבל פניות נוספות, השב \"הסר\" להודעה הזו "
            . "או פנה אלינו כאן: {$unsubscribe}\n"
            . "נסיר אותך מיידית ולא נפנה שוב.";
    }
}
