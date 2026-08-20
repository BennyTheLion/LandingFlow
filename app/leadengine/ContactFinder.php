<?php
namespace App\LeadEngine;

use App\Core\Logger;
use App\Services\OpenAiService;

/**
 * ContactFinder — Stage 3 (spec §7).
 *
 * Attempt order, stopping at the first success:
 *   1. Scrape /contact, /about, /אודות, /צור-קשר — regex for email + phone,
 *      LLM for the person's name
 *   2. JSON-LD / schema.org on the homepage (founder / employee)
 *   3. Google Places phone (passed in by the caller)
 *   4. Fallback: info@domain with contact_name = null
 *
 * All fetching goes through PoliteFetcher: identified UA, robots.txt honoured,
 * one request per 2 seconds per domain.
 */
class ContactFinder
{
    /** Common contact/about paths, Hebrew and English, URL-encoded as needed */
    private const CANDIDATE_PATHS = [
        '/contact',
        '/contact-us',
        '/about',
        '/about-us',
        '/%D7%A6%D7%95%D7%A8-%D7%A7%D7%A9%D7%A8',   // צור-קשר
        '/%D7%A6%D7%95%D7%A8_%D7%A7%D7%A9%D7%A8',   // צור_קשר
        '/%D7%90%D7%95%D7%93%D7%95%D7%AA',          // אודות
        '/%D7%A2%D7%9C%D7%99%D7%A0%D7%95',          // עלינו
    ];

    private PoliteFetcher $fetcher;
    private OpenAiService $llm;

    public function __construct(?PoliteFetcher $fetcher = null, ?OpenAiService $llm = null)
    {
        $this->fetcher = $fetcher ?? new PoliteFetcher();
        $this->llm = $llm ?? new OpenAiService();
    }

    /**
     * @param string|null $placesPhone Phone already known from Google Places
     * @return array{email:?string,phone:?string,contact_name:?string,contact_role:?string,
     *               source:string,pages_tried:int}
     */
    public function find(string $url, ?string $placesPhone = null): array
    {
        $url = PoliteFetcher::normalizeUrl($url);
        $domain = PoliteFetcher::domainKey($url);

        $found = [
            'email'        => null,
            'phone'        => $placesPhone !== null && $placesPhone !== '' ? $placesPhone : null,
            'contact_name' => null,
            'contact_role' => null,
            'source'       => 'none',
            'pages_tried'  => 0,
        ];

        // --- Attempt 1: contact / about pages --------------------------------
        foreach (self::CANDIDATE_PATHS as $path) {
            if ($found['email'] !== null && $found['contact_name'] !== null) {
                break;
            }

            $page = $this->fetcher->fetch($url . $path);
            $found['pages_tried']++;
            if (!$page['ok'] || $page['body'] === '') {
                continue;
            }

            $emails = HtmlSignals::extractEmails($page['body']);
            $ownDomainEmail = $this->preferOwnDomain($emails, $domain);
            if ($found['email'] === null && $ownDomainEmail !== null) {
                $found['email'] = $ownDomainEmail;
                $found['source'] = 'contact_page';
            }

            if ($found['phone'] === null) {
                $phones = HtmlSignals::extractPhones($page['body']);
                if ($phones !== []) {
                    $found['phone'] = $phones[0];
                    $found['source'] = $found['source'] === 'none' ? 'contact_page' : $found['source'];
                }
            }

            if ($found['contact_name'] === null) {
                $name = $this->extractName($page['body']);
                if ($name !== null) {
                    $found['contact_name'] = $name['name'];
                    $found['contact_role'] = $name['role'];
                    $found['source'] = 'contact_page';
                }
            }
        }

        // --- Attempt 2: JSON-LD on the homepage ------------------------------
        if ($found['contact_name'] === null || $found['email'] === null) {
            $home = $this->fetcher->fetch($url);
            $found['pages_tried']++;
            if ($home['ok'] && $home['body'] !== '') {
                if ($found['contact_name'] === null) {
                    $jsonLd = HtmlSignals::extractJsonLd($home['body']);
                    $name = HtmlSignals::personNameFromJsonLd($jsonLd);
                    if ($name !== null) {
                        $found['contact_name'] = $name;
                        $found['source'] = 'json_ld';
                    }
                }
                if ($found['email'] === null) {
                    $email = $this->preferOwnDomain(HtmlSignals::extractEmails($home['body']), $domain);
                    if ($email !== null) {
                        $found['email'] = $email;
                        $found['source'] = $found['source'] === 'none' ? 'homepage' : $found['source'];
                    }
                }
                if ($found['phone'] === null) {
                    $phones = HtmlSignals::extractPhones($home['body']);
                    if ($phones !== []) {
                        $found['phone'] = $phones[0];
                    }
                }
            }
        }

        // --- Attempt 3: Google Places phone ----------------------------------
        if ($found['email'] === null && $found['phone'] !== null && $found['source'] === 'none') {
            $found['source'] = 'google_places';
        }

        // --- Attempt 4: fallback --------------------------------------------
        if ($found['email'] === null) {
            $found['email'] = 'info@' . $domain;
            $found['source'] = 'fallback_info';
            Logger::info('leadengine: falling back to info@ address', ['domain' => $domain]);
        }

        return $found;
    }

    /**
     * An address on the prospect's own domain beats a gmail.com one, which beats
     * a generic inbox. Returns null when nothing usable was found.
     */
    private function preferOwnDomain(array $emails, string $domain): ?string
    {
        if ($emails === []) {
            return null;
        }

        $onDomain = [];
        $offDomain = [];
        foreach ($emails as $email) {
            $emailHost = substr($email, strpos($email, '@') + 1);
            if ($emailHost === $domain || str_ends_with($emailHost, '.' . $domain)) {
                $onDomain[] = $email;
            } else {
                $offDomain[] = $email;
            }
        }

        $pool = $onDomain !== [] ? $onDomain : $offDomain;

        // Prefer a person-looking mailbox over a role mailbox
        usort($pool, function (string $a, string $b): int {
            $roleA = (int) preg_match('/^(info|office|contact|mail|sales|support|admin|service)@/i', $a);
            $roleB = (int) preg_match('/^(info|office|contact|mail|sales|support|admin|service)@/i', $b);
            return $roleA <=> $roleB;
        });

        return $pool[0] ?? null;
    }

    /**
     * Pull the owner's name out of page text.
     *
     * Tries a set of Hebrew/English patterns first — cheap, deterministic, and
     * free. Falls back to the LLM only when patterns miss and it is configured.
     *
     * @return array{name:string,role:?string}|null
     */
    private function extractName(string $html): ?array
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags($html)) ?? '');
        if ($text === '') {
            return null;
        }
        $text = mb_substr($text, 0, 6000);

        // Hebrew and English title patterns that reliably precede a real name
        $patterns = [
            '/(?:ד"ר|דוקטור|עו"ד|רו"ח|פרופ\'?)\s+([\p{Hebrew}]{2,15}\s+[\p{Hebrew}]{2,15})/u' => 'בעל מקצוע',
            '/(?:בעל(?:ת)?\s+ה?(?:עסק|מרפאה|חברה)|מנכ"ל(?:ית)?|מייסד(?:ת)?|מנהל(?:ת)?)[:\s,–-]+([\p{Hebrew}]{2,15}\s+[\p{Hebrew}]{2,15})/u' => 'בעלים',
            '/([\p{Hebrew}]{2,15}\s+[\p{Hebrew}]{2,15})\s*[,–-]\s*(?:מנכ"ל(?:ית)?|בעל(?:ת)?\s+ה?עסק|מייסד(?:ת)?)/u' => 'בעלים',
            '/\b(?:founder|owner|ceo|managing director)\b[:\s,–-]+([A-Z][a-z]{1,14}\s+[A-Z][a-z]{1,14})/i' => 'Founder',
        ];

        foreach ($patterns as $pattern => $role) {
            if (preg_match($pattern, $text, $m) && HtmlSignals::looksLikePersonName($m[1])) {
                return ['name' => trim($m[1]), 'role' => $role];
            }
        }

        return $this->extractNameWithLlm($text);
    }

    /** @return array{name:string,role:?string}|null */
    private function extractNameWithLlm(string $text): ?array
    {
        if (!$this->llm->isAvailable()) {
            return null;
        }

        $prompt = "להלן טקסט מעמוד 'צור קשר' או 'אודות' של אתר עסקי בישראל.\n"
            . "מצא את שמו של הבעלים / איש הקשר הראשי, אם הוא מופיע במפורש.\n"
            . "החזר JSON בלבד: {\"name\": \"שם מלא או null\", \"role\": \"תפקיד או null\"}\n"
            . "אל תנחש. אם אין שם של אדם ספציפי בטקסט, החזר name = null.\n\n"
            . "--- טקסט ---\n" . $text;

        try {
            $raw = $this->llm->complete(
                'אתה מחלץ מידע מובנה מטקסט. אתה מחזיר JSON תקין בלבד, בלי הסברים.',
                $prompt,
                300
            );
        } catch (\Throwable $e) {
            Logger::warning('leadengine: LLM name extraction failed', ['message' => $e->getMessage()]);
            return null;
        }

        if ($raw === null) {
            return null;
        }

        $data = OpenAiService::extractJson($raw);
        $name = $data['name'] ?? null;
        if (!is_string($name) || $name === '' || strtolower($name) === 'null') {
            return null;
        }
        if (!HtmlSignals::looksLikePersonName($name)) {
            return null;
        }

        $role = $data['role'] ?? null;
        return [
            'name' => trim($name),
            'role' => is_string($role) && $role !== '' && strtolower($role) !== 'null' ? trim($role) : null,
        ];
    }
}
