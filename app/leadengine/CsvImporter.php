<?php
namespace App\LeadEngine;

/**
 * CsvImporter — Stage 1 source C (spec §5).
 *
 * Expected header: business_name, url, phone, city, niche
 * Extra columns are ignored; column order does not matter; a UTF-8 BOM and
 * Hebrew header names are both tolerated.
 */
class CsvImporter
{
    /** Canonical column => accepted header spellings */
    private const COLUMN_ALIASES = [
        'business_name' => ['business_name', 'business', 'name', 'company', 'שם', 'שם העסק', 'שם עסק'],
        'url'           => ['url', 'website', 'site', 'domain', 'אתר', 'כתובת אתר', 'דומיין'],
        'phone'         => ['phone', 'tel', 'telephone', 'mobile', 'טלפון', 'נייד'],
        'email'         => ['email', 'mail', 'e-mail', 'מייל', 'אימייל'],
        'city'          => ['city', 'town', 'עיר'],
        'niche'         => ['niche', 'category', 'industry', 'type', 'נישה', 'תחום', 'קטגוריה'],
        'contact_name'  => ['contact_name', 'contact', 'owner', 'איש קשר', 'בעלים'],
        'spends_on_ads' => ['spends_on_ads', 'ads', 'advertising', 'מפרסם', 'מודעות'],
    ];

    /**
     * @return array{rows:array<int,array<string,mixed>>,errors:string[],skipped:int}
     *         `rows` are ready for ProspectRepository::create()
     */
    public function parse(string $csvPath): array
    {
        $rows = [];
        $errors = [];
        $skipped = 0;

        if (!is_readable($csvPath)) {
            return ['rows' => [], 'errors' => ['לא ניתן לקרוא את הקובץ.'], 'skipped' => 0];
        }

        $handle = fopen($csvPath, 'r');
        if ($handle === false) {
            return ['rows' => [], 'errors' => ['פתיחת הקובץ נכשלה.'], 'skipped' => 0];
        }

        try {
            $header = fgetcsv($handle);
            if ($header === false || $header === [null]) {
                return ['rows' => [], 'errors' => ['הקובץ ריק.'], 'skipped' => 0];
            }

            // Strip a UTF-8 BOM from the first cell
            $header[0] = preg_replace('/^\x{FEFF}/u', '', (string) $header[0]) ?? $header[0];

            $map = $this->mapColumns($header);
            if (!isset($map['url']) && !isset($map['business_name'])) {
                return [
                    'rows'    => [],
                    'errors'  => ['לא נמצאה עמודת url או business_name בכותרת הקובץ.'],
                    'skipped' => 0,
                ];
            }

            $lineNumber = 1;
            while (($record = fgetcsv($handle)) !== false) {
                $lineNumber++;
                if ($record === [null] || $this->isBlank($record)) {
                    continue;
                }

                $get = function (string $column) use ($map, $record): string {
                    $index = $map[$column] ?? null;
                    return $index !== null ? trim((string) ($record[$index] ?? '')) : '';
                };

                $url = PoliteFetcher::normalizeUrl($get('url'));
                $domain = PoliteFetcher::domainKey($url);
                if ($domain === '' || !str_contains($domain, '.')) {
                    $errors[] = "שורה {$lineNumber}: כתובת אתר חסרה או לא תקינה — נדלגה.";
                    $skipped++;
                    continue;
                }

                $name = $get('business_name');
                $email = $get('email');
                $adsRaw = strtolower($get('spends_on_ads'));

                $rows[] = [
                    'business_name' => $name !== '' ? $name : $domain,
                    'domain'        => $domain,
                    'url'           => $url,
                    'phone'         => $get('phone') !== '' ? $get('phone') : null,
                    'email'         => $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null,
                    'city'          => $get('city') !== '' ? $get('city') : null,
                    'niche'         => $get('niche') !== '' ? $get('niche') : null,
                    'contact_name'  => $get('contact_name') !== '' ? $get('contact_name') : null,
                    'spends_on_ads' => in_array($adsRaw, ['1', 'true', 'yes', 'y', 'כן'], true) ? 1 : 0,
                    'source'        => 'csv',
                    'status'        => 'new',
                ];
            }
        } finally {
            fclose($handle);
        }

        return ['rows' => $rows, 'errors' => $errors, 'skipped' => $skipped];
    }

    /** @return array<string,int> canonical column => index in the record */
    private function mapColumns(array $header): array
    {
        $map = [];
        foreach ($header as $index => $rawName) {
            $name = strtolower(trim((string) $rawName));
            if ($name === '') {
                continue;
            }
            foreach (self::COLUMN_ALIASES as $canonical => $aliases) {
                if (isset($map[$canonical])) {
                    continue;
                }
                foreach ($aliases as $alias) {
                    if ($name === strtolower($alias)) {
                        $map[$canonical] = $index;
                        break 2;
                    }
                }
            }
        }
        return $map;
    }

    private function isBlank(array $record): bool
    {
        foreach ($record as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }
        return true;
    }
}
