<?php
/**
 * Knowledge Base — Template Engine
 * Reads the master index and component patterns, then generates
 * an optimized section order and design spec for any industry.
 *
 * Usage:
 *   $engine = new TemplateEngine();
 *   $spec = $engine->build('dentist');
 *   // $spec now contains: sectionOrder, components, heroPattern, etc.
 */

class TemplateEngine
{
    private array $index;
    private array $patterns;

    public function __construct()
    {
        $this->index    = require __DIR__ . '/index.php';
        $this->patterns = require __DIR__ . '/components/patterns.php';
    }

    /**
     * Build a complete page specification for a given industry key.
     */
    public function build(string $industryKey): array
    {
        $idx = $this->index[$industryKey] ?? $this->index['general'];

        return [
            'sectionOrder' => $idx['sectionOrder'],
            'heroPattern'  => $idx['heroPattern'],
            'ctaStyle'     => $idx['ctaStyle'],
            'socialProof'  => $idx['socialProof'],
            'primaryAction' => $idx['primaryAction'],
            'tone'          => $idx['tone'],
            'components'    => $this->resolveComponents($idx['sectionOrder']),
            'designTokens'  => $this->patterns['design_tokens'],
        ];
    }

    /**
     * Resolve each section in the order to its best component variant.
     */
    private function resolveComponents(array $order): array
    {
        $resolved = [];
        foreach ($order as $sectionType) {
            $variant = $this->pickVariant($sectionType);
            if ($variant) {
                $resolved[$sectionType] = $variant;
            }
        }
        return $resolved;
    }

    /**
     * Pick the best component variant for a section type.
     */
    private function pickVariant(string $type): ?array
    {
        // Map section types to pattern keys
        $map = [
            'hero'          => 'hero',
            'features'      => 'features',
            'services'      => 'features',
            'about'         => 'about',
            'team'          => 'about',
            'stats'         => 'stats',
            'testimonials'  => 'testimonials',
            'pricing'       => 'pricing',
            'cta'           => 'cta',
            'faq'           => 'faq',
            'contact'       => 'contact',
            'footer'        => 'footer',
            // Custom sections
            'menu'          => 'custom',
            'gallery'       => 'custom',
            'portfolio'     => 'custom',
            'specialties'   => 'custom',
            'howItWorks'    => 'custom',
            'integrations'  => 'custom',
            'schedule'      => 'custom',
            'trainers'      => 'custom',
            'membership'    => 'custom',
            'products'      => 'custom',
            'properties'    => 'custom',
            'neighborhoods' => 'custom',
            'agent'         => 'custom',
            'treatments'    => 'custom',
            'beforeAfter'   => 'custom',
            'booking-cta'   => 'custom',
            'curriculum'    => 'custom',
            'instructors'   => 'custom',
            'courses'       => 'custom',
        ];

        $patternKey = $map[$type] ?? null;
        if (!$patternKey) return null;

        $pattern = $this->patterns[$patternKey] ?? null;
        if (!$pattern) return null;

        // For sections with variants, pick default
        if (isset($pattern['variants'])) {
            return $pattern['variants'][array_key_first($pattern['variants'])];
        }

        // For custom sections, return the raw config
        return $pattern[$type] ?? $pattern;
    }

    /**
     * Get all supported industries.
     */
    public function industries(): array
    {
        $list = [];
        foreach ($this->index as $key => $data) {
            $list[$key] = $data['sectionOrder'] ?? [];
        }
        return $list;
    }

    /**
     * Map user-friendly category names to industry keys.
     */
    public function resolveCategory(string $userCategory): string
    {
        $map = [
            'restaurant'    => 'restaurant',
            'professional'  => 'lawyer',
            'tech'          => 'saas',
            'fitness'       => 'gym',
            'retail'        => 'ecommerce',
            'realestate'    => 'real-estate',
            'beauty'        => 'beauty-salon',
            'education'     => 'online-course',
            'construction'  => 'construction',
            'auto'          => 'auto-repair',
            'general'       => 'general',
        ];
        return $map[$userCategory] ?? 'general';
    }
}
