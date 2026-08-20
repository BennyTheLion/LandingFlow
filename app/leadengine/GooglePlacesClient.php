<?php
namespace App\LeadEngine;

use App\Core\Logger;

/**
 * GooglePlacesClient — Stage 1 source A (spec §5).
 *
 * Text Search with structured "{niche} {city}" queries, then Place Details for
 * phone + website. Filters per spec: rating >= 4.0, >= 30 ratings, has a website.
 *
 * Cost: ~$32 per 1,000 searches. Set a daily budget cap in GCP — this client
 * will happily page through results if asked to.
 */
class GooglePlacesClient
{
    private const TEXT_SEARCH = 'https://maps.googleapis.com/maps/api/place/textsearch/json';
    private const DETAILS = 'https://maps.googleapis.com/maps/api/place/details/json';

    /** Spec §5 filters */
    public const MIN_RATING = 4.0;
    public const MIN_REVIEWS = 30;

    private string $apiKey;

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? (defined('GOOGLE_PLACES_API_KEY') ? GOOGLE_PLACES_API_KEY : '');
    }

    public function isAvailable(): bool
    {
        return $this->apiKey !== '';
    }

    /** Hebrew search terms per niche — what the businesses actually call themselves */
    public const NICHE_QUERIES = [
        'dental_clinic' => 'מרפאת שיניים',
        'law_firm'      => 'עורך דין',
        'aesthetics'    => 'קליניקת אסתטיקה',
        'contractor'    => 'קבלן שיפוצים',
        'accountant'    => 'רואה חשבון',
        'veterinary'    => 'וטרינר',
        'real_estate'   => 'תיווך נדל"ן',
        'gym'           => 'חדר כושר',
    ];

    /**
     * One "{niche} {city}" search, filtered and enriched.
     *
     * @param int $maxResults Caps Place Details calls, which are the billable part
     * @return array<int,array{business_name:string,url:string,domain:string,phone:?string,
     *               city:string,niche:string,source:string,source_ref:string}>
     */
    public function search(string $niche, string $city, int $maxResults = 20): array
    {
        if (!$this->isAvailable()) {
            Logger::warning('leadengine: Google Places key not configured');
            return [];
        }

        $queryTerm = self::NICHE_QUERIES[$niche] ?? $niche;
        $response = $this->httpGet(self::TEXT_SEARCH, [
            'query'    => trim($queryTerm . ' ' . $city),
            'language' => 'he',
            'region'   => 'il',
            'key'      => $this->apiKey,
        ]);

        if ($response === null) {
            return [];
        }

        $status = $response['status'] ?? 'UNKNOWN';
        if ($status !== 'OK' && $status !== 'ZERO_RESULTS') {
            Logger::error('leadengine: Places text search returned an error', [
                'status' => $status,
                'error'  => $response['error_message'] ?? '',
                'niche'  => $niche,
                'city'   => $city,
            ]);
            return [];
        }

        $out = [];
        foreach ($response['results'] ?? [] as $place) {
            if (count($out) >= $maxResults) {
                break;
            }

            $rating = (float) ($place['rating'] ?? 0);
            $reviews = (int) ($place['user_ratings_total'] ?? 0);
            if ($rating < self::MIN_RATING || $reviews < self::MIN_REVIEWS) {
                continue;
            }
            if (($place['business_status'] ?? 'OPERATIONAL') !== 'OPERATIONAL') {
                continue;
            }

            $placeId = (string) ($place['place_id'] ?? '');
            if ($placeId === '') {
                continue;
            }

            // Text Search does not return website/phone — Details does
            $details = $this->details($placeId);
            $website = $details['website'] ?? null;
            if (!is_string($website) || $website === '') {
                continue; // spec §5: a business with no website is not a prospect
            }

            $url = PoliteFetcher::normalizeUrl($website);
            $domain = PoliteFetcher::domainKey($url);
            if ($domain === '') {
                continue;
            }
            // Social pages and marketplace listings are not the business's site
            if (preg_match('/(facebook|instagram|wixsite\.com\/[^\/]+\/?$|linktr\.ee|easy\.co\.il|dapey|b144)/i', $domain)) {
                continue;
            }

            $out[] = [
                'business_name' => (string) ($details['name'] ?? $place['name'] ?? $domain),
                'url'           => $url,
                'domain'        => $domain,
                'phone'         => $this->normalizePhone($details['formatted_phone_number'] ?? null),
                'city'          => $city,
                'niche'         => $niche,
                'source'        => 'google_places',
                'source_ref'    => $placeId,
                'rating'        => $rating,
                'reviews'       => $reviews,
            ];
        }

        Logger::info('leadengine: Places search complete', [
            'niche' => $niche, 'city' => $city,
            'raw' => count($response['results'] ?? []), 'kept' => count($out),
        ]);

        return $out;
    }

    /** @return array<string,mixed> */
    private function details(string $placeId): array
    {
        $response = $this->httpGet(self::DETAILS, [
            'place_id' => $placeId,
            'fields'   => 'name,website,formatted_phone_number,formatted_address,business_status',
            'language' => 'he',
            'key'      => $this->apiKey,
        ]);

        if ($response === null || ($response['status'] ?? '') !== 'OK') {
            return [];
        }
        return is_array($response['result'] ?? null) ? $response['result'] : [];
    }

    /** @return array<string,mixed>|null */
    private function httpGet(string $endpoint, array $params): ?array
    {
        $ch = curl_init($endpoint . '?' . http_build_query($params));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_USERAGENT      => LeadEngineConfig::USER_AGENT,
        ]);
        $body = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error !== '' || $httpCode !== 200 || !is_string($body)) {
            Logger::error('leadengine: Places HTTP call failed', ['http' => $httpCode, 'error' => $error]);
            return null;
        }

        $data = json_decode($body, true);
        return is_array($data) ? $data : null;
    }

    /** Israeli numbers to a bare 0XXXXXXXXX form */
    private function normalizePhone(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }
        $digits = preg_replace('/[^0-9+]/', '', $phone) ?? '';
        if (str_starts_with($digits, '+972')) {
            $digits = '0' . substr($digits, 4);
        }
        return $digits !== '' ? $digits : null;
    }
}
