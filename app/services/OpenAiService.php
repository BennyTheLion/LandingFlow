<?php
namespace App\Services;

class OpenAiService
{
    private string $apiKey;
    private string $apiUrl;
    private string $model;
    private int $maxTokens;

    public function __construct()
    {
        $this->apiKey   = defined('AI_API_KEY') ? AI_API_KEY : '';
        $this->apiUrl   = defined('AI_API_URL') ? AI_API_URL : 'https://api.openai.com/v1/chat/completions';
        $this->model    = defined('AI_MODEL') ? AI_MODEL : 'gpt-4o-mini';
        $this->maxTokens = defined('AI_MAX_TOKENS') ? (int)AI_MAX_TOKENS : 4096;
    }

    public function isAvailable(): bool
    {
        return defined('AI_ENABLED') && AI_ENABLED && !empty($this->apiKey);
    }

    public function generateDemoSite(string $businessDescription, string $businessName, string $businessType): ?array
    {
        if (!$this->isAvailable()) return null;

        $systemPrompt = $this->getSystemPrompt();

        $userPrompt = "Create a premium demo website for:\n"
            . "Business Name: {$businessName}\n"
            . "Business Type: {$businessType}\n"
            . "Description: {$businessDescription}\n\n"
            . "Generate a complete high-converting one-page website. Return ONLY valid JSON.";

        $response = $this->call($systemPrompt, $userPrompt);

        if (!$response) return null;

        return $this->parseResponse($response);
    }

    private function getSystemPrompt(): string
    {
        return <<<'PROMPT'
You are a premium website generator for a web design agency. Generate HIGH-CONVERTING demo websites that look like $5k-$15k projects.

PERSONA:
- Define brand personality (Luxury/Trust/Modern/Aggressive Growth/Minimal SaaS)
- Define emotional angle (Authority/Speed/Security/Innovation/Premium Quality)
- Define target mindset (Need leads/Need trust/Need modern presence/Need sales)

COMPONENTS (ONLY these): hero, features, about, testimonials, pricing, cta, contact, footer

FLOW: Hero → Trust → Value → Proof → CTA → Contact → Footer

RULES:
- Hero: "Problem + Promise + CTA" formula
- Strong hook, premium copy, one idea per section
- At least 2 CTA sections
- Short, confident, business-driven tone
- Trust signals always present

DESIGN: Pick one: SaaS Modern / Luxury Premium / Local Trust / High Conversion Bold

OUTPUT - Return ONLY this JSON, no explanations:
{
  "persona": {"style": "...", "emotion": "...", "target": "..."},
  "design": {"name": "...", "colors": {"primary":"#...", "secondary":"#...", "accent":"#...", "bg":"#...", "surface":"#...", "text":"#...", "textSoft":"#...", "gradient":"..."}, "fonts": {"heading":"...", "body":"..."}, "spacing":"..."},
  "site_type": "...",
  "brand_name": "...",
  "score": 92,
  "sections": [
    {"type": "hero", "title": "...", "subtitle": "...", "cta": "..."},
    {"type": "features", "variant": "trust", "title": "...", "subtitle": "...", "items": [{"icon":"...", "title":"...", "body":"..."}]},
    {"type": "features", "variant": "value", "title": "...", "subtitle": "...", "items": [{"icon":"...","title":"...","body":"..."}]},
    {"type": "testimonials", "title": "...", "subtitle": "...", "items": [{"name":"...","role":"...","quote":"...","stars":5}]},
    {"type": "cta", "title": "...", "subtitle": "...", "cta": "..."},
    {"type": "about", "title": "...", "body": "..."},
    {"type": "cta", "title": "Ready to Grow Your Business?", "subtitle": "Contact us today.", "cta": "Get Your Free Quote →"},
    {"type": "contact", "title": "Let's Talk", "subtitle": "...", "email": "...", "phone": "...", "fields": ["name","email","message"], "cta": "Send Message"},
    {"type": "footer", "brand": "...", "links": [{"label":"...","href":"#"}]}
  ]
}
PROMPT;
    }

    private function call(string $system, string $user): ?string
    {
        $payload = [
            'model'    => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user',   'content' => $user],
            ],
            'max_tokens'  => $this->maxTokens,
            'temperature' => 0.7,
        ];

        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode !== 200) {
            error_log("[OpenAiService] API error: HTTP $httpCode — $error — $body");
            return null;
        }

        $data = json_decode($body, true);
        return $data['choices'][0]['message']['content'] ?? null;
    }

    private function parseResponse(string $text): ?array
    {
        // Try to extract JSON from the response (handle markdown code blocks)
        $text = trim($text);

        // Remove ```json ... ``` wrapper if present
        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $text, $m)) {
            $text = trim($m[1]);
        }

        $data = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Try to find JSON object boundaries
            if (preg_match('/\{[\s\S]*\}/', $text, $m)) {
                $data = json_decode($m[0], true);
            }
        }

        return is_array($data) ? $data : null;
    }
}
