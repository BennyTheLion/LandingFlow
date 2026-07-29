<?php
/**
 * Knowledge Base — Landing Page Master Index
 * Maps every industry to its best-practice section order, design patterns,
 * and conversion strategies. Used by the template engine to auto-generate
 * industry-appropriate landing pages.
 *
 * Sources: Lapa Ninja, Land-book, Awwwards, One Page Love, Godly, Site Inspire
 * Updated: 2026-07-10
 */

return [

    // =================================================================
    // TECHNOLOGY & DIGITAL
    // =================================================================

    'saas' => [
        'sectionOrder' => ['hero','trust','features','howItWorks','integrations','pricing','testimonials','faq','cta','footer'],
        'heroPattern' => 'split-left',
        'ctaStyle' => 'primary-filled',
        'socialProof' => ['logos','case-studies','stats'],
        'primaryAction' => 'start-trial',
        'tone' => 'modern',
    ],

    'ai' => [
        'sectionOrder' => ['hero','stats','howItWorks','features','use-cases','testimonials','pricing','faq','cta','footer'],
        'heroPattern' => 'centered-gradient',
        'ctaStyle' => 'glow',
        'socialProof' => ['stats','logos','testimonials'],
        'primaryAction' => 'demo',
        'tone' => 'modern',
    ],

    'tech-startup' => [
        'sectionOrder' => ['hero','problem','solution','features','howItWorks','stats','testimonials','pricing','cta','footer'],
        'heroPattern' => 'product-screenshot',
        'ctaStyle' => 'primary-filled',
        'socialProof' => ['investors','stats','logos'],
        'primaryAction' => 'signup',
        'tone' => 'bold',
    ],

    'mobile-app' => [
        'sectionOrder' => ['hero','features','screenshots','howItWorks','testimonials','pricing','download-cta','footer'],
        'heroPattern' => 'phone-mockup',
        'ctaStyle' => 'store-buttons',
        'socialProof' => ['ratings','reviews','downloads'],
        'primaryAction' => 'download',
        'tone' => 'modern',
    ],

    // =================================================================
    // AGENCIES & SERVICES
    // =================================================================

    'digital-agency' => [
        'sectionOrder' => ['hero','portfolio','services','process','stats','testimonials','team','cta','contact','footer'],
        'heroPattern' => 'full-width-video',
        'ctaStyle' => 'primary-outline',
        'socialProof' => ['portfolio','logos','case-studies'],
        'primaryAction' => 'contact',
        'tone' => 'bold',
    ],

    'marketing' => [
        'sectionOrder' => ['hero','stats','services','case-studies','process','testimonials','pricing','faq','cta','footer'],
        'heroPattern' => 'split-left',
        'ctaStyle' => 'primary-filled',
        'socialProof' => ['case-studies','stats','testimonials'],
        'primaryAction' => 'consultation',
        'tone' => 'modern',
    ],

    'seo' => [
        'sectionOrder' => ['hero','audit-cta','services','case-studies','stats','testimonials','pricing','faq','cta','footer'],
        'heroPattern' => 'centered',
        'ctaStyle' => 'primary-filled',
        'socialProof' => ['case-studies','rankings','testimonials'],
        'primaryAction' => 'audit',
        'tone' => 'modern',
    ],

    'design-studio' => [
        'sectionOrder' => ['hero','portfolio','services','process','testimonials','team','cta','contact','footer'],
        'heroPattern' => 'full-width-image',
        'ctaStyle' => 'minimal',
        'socialProof' => ['portfolio','awards','testimonials'],
        'primaryAction' => 'contact',
        'tone' => 'luxury',
    ],

    // =================================================================
    // E-COMMERCE & RETAIL
    // =================================================================

    'ecommerce' => [
        'sectionOrder' => ['hero','categories','featured-products','benefits','testimonials','newsletter','footer'],
        'heroPattern' => 'centered',
        'ctaStyle' => 'primary-filled',
        'socialProof' => ['reviews','trust-badges','stats'],
        'primaryAction' => 'shop',
        'tone' => 'warm',
    ],

    // =================================================================
    // REAL ESTATE
    // =================================================================

    'real-estate' => [
        'sectionOrder' => ['hero','search','featured-listings','neighborhoods','stats','agent','testimonials','cta','contact','footer'],
        'heroPattern' => 'full-width-image',
        'ctaStyle' => 'primary-filled',
        'socialProof' => ['stats','testimonials','agent-profile'],
        'primaryAction' => 'contact',
        'tone' => 'luxury',
    ],

    // =================================================================
    // LEGAL & PROFESSIONAL
    // =================================================================

    'lawyer' => [
        'sectionOrder' => ['hero','specialties','about','team','case-studies','testimonials','faq','cta','contact','footer'],
        'heroPattern' => 'split-left',
        'ctaStyle' => 'primary-filled',
        'socialProof' => ['case-studies','certifications','testimonials'],
        'primaryAction' => 'consultation',
        'tone' => 'luxury',
    ],

    'accounting' => [
        'sectionOrder' => ['hero','services','benefits','stats','testimonials','faq','cta','contact','footer'],
        'heroPattern' => 'centered',
        'ctaStyle' => 'primary-filled',
        'socialProof' => ['stats','testimonials','certifications'],
        'primaryAction' => 'consultation',
        'tone' => 'modern',
    ],

    'insurance' => [
        'sectionOrder' => ['hero','services','benefits','stats','testimonials','faq','cta','contact','footer'],
        'heroPattern' => 'centered',
        'ctaStyle' => 'primary-filled',
        'socialProof' => ['stats','testimonials'],
        'primaryAction' => 'quote',
        'tone' => 'warm',
    ],

    'financial' => [
        'sectionOrder' => ['hero','stats','services','about','testimonials','faq','cta','contact','footer'],
        'heroPattern' => 'split-left',
        'ctaStyle' => 'primary-filled',
        'socialProof' => ['stats','certifications','testimonials'],
        'primaryAction' => 'consultation',
        'tone' => 'luxury',
    ],

    // =================================================================
    // MEDICAL & HEALTHCARE
    // =================================================================

    'doctor' => [
        'sectionOrder' => ['hero','services','about','team','testimonials','insurance','faq','cta','contact','footer'],
        'heroPattern' => 'split-left',
        'ctaStyle' => 'primary-filled',
        'socialProof' => ['certifications','testimonials','stats'],
        'primaryAction' => 'appointment',
        'tone' => 'warm',
    ],

    'dentist' => [
        'sectionOrder' => ['hero','emergency','services','team','beforeAfter','testimonials','insurance','faq','cta','contact','footer'],
        'heroPattern' => 'split-left',
        'ctaStyle' => 'primary-filled',
        'socialProof' => ['before-after','testimonials','reviews'],
        'primaryAction' => 'appointment',
        'tone' => 'warm',
    ],

    'plastic-surgeon' => [
        'sectionOrder' => ['hero','about','procedures','beforeAfter','team','testimonials','faq','cta','contact','footer'],
        'heroPattern' => 'centered',
        'ctaStyle' => 'luxury-filled',
        'socialProof' => ['before-after','testimonials','certifications'],
        'primaryAction' => 'consultation',
        'tone' => 'luxury',
    ],

    'veterinary' => [
        'sectionOrder' => ['hero','services','team','about','testimonials','faq','cta','contact','footer'],
        'heroPattern' => 'centered',
        'ctaStyle' => 'primary-filled',
        'socialProof' => ['testimonials','reviews'],
        'primaryAction' => 'appointment',
        'tone' => 'warm',
    ],

    // =================================================================
    // BEAUTY & WELLNESS
    // =================================================================

    'beauty-salon' => [
        'sectionOrder' => ['hero','services','beforeAfter','team','pricing','testimonials','booking-cta','contact','footer'],
        'heroPattern' => 'full-width-image',
        'ctaStyle' => 'primary-filled',
        'socialProof' => ['before-after','testimonials','instagram'],
        'primaryAction' => 'booking',
        'tone' => 'warm',
    ],

    'barber' => [
        'sectionOrder' => ['hero','services','team','pricing','gallery','testimonials','booking-cta','contact','footer'],
        'heroPattern' => 'full-width-image',
        'ctaStyle' => 'primary-filled',
        'socialProof' => ['gallery','testimonials','reviews'],
        'primaryAction' => 'booking',
        'tone' => 'bold',
    ],

    'spa' => [
        'sectionOrder' => ['hero','treatments','about','team','pricing','testimonials','booking-cta','contact','footer'],
        'heroPattern' => 'full-width-image',
        'ctaStyle' => 'luxury-filled',
        'socialProof' => ['testimonials','reviews','certifications'],
        'primaryAction' => 'booking',
        'tone' => 'luxury',
    ],

    // =================================================================
    // FOOD & HOSPITALITY
    // =================================================================

    'restaurant' => [
        'sectionOrder' => ['hero','menu','about','gallery','testimonials','booking-cta','contact','footer'],
        'heroPattern' => 'full-width-image',
        'ctaStyle' => 'primary-filled',
        'socialProof' => ['reviews','gallery','testimonials'],
        'primaryAction' => 'reserve',
        'tone' => 'warm',
    ],

    'cafe' => [
        'sectionOrder' => ['hero','menu','about','gallery','testimonials','contact','footer'],
        'heroPattern' => 'full-width-image',
        'ctaStyle' => 'minimal',
        'socialProof' => ['reviews','gallery','instagram'],
        'primaryAction' => 'order',
        'tone' => 'warm',
    ],

    'hotel' => [
        'sectionOrder' => ['hero','rooms','amenities','gallery','about','testimonials','booking-cta','contact','footer'],
        'heroPattern' => 'full-width-video',
        'ctaStyle' => 'luxury-filled',
        'socialProof' => ['ratings','gallery','testimonials'],
        'primaryAction' => 'booking',
        'tone' => 'luxury',
    ],

    // =================================================================
    // FITNESS & SPORTS
    // =================================================================

    'gym' => [
        'sectionOrder' => ['hero','classes','trainers','membership','facilities','testimonials','cta','contact','footer'],
        'heroPattern' => 'full-width-image',
        'ctaStyle' => 'primary-filled',
        'socialProof' => ['stats','testimonials','before-after'],
        'primaryAction' => 'trial',
        'tone' => 'bold',
    ],

    'personal-trainer' => [
        'sectionOrder' => ['hero','about','programs','beforeAfter','testimonials','pricing','cta','contact','footer'],
        'heroPattern' => 'split-left',
        'ctaStyle' => 'primary-filled',
        'socialProof' => ['before-after','testimonials','certifications'],
        'primaryAction' => 'consultation',
        'tone' => 'bold',
    ],

    // =================================================================
    // CONSTRUCTION & TRADES
    // =================================================================

    'construction' => [
        'sectionOrder' => ['hero','services','portfolio','process','stats','testimonials','cta','contact','footer'],
        'heroPattern' => 'full-width-image',
        'ctaStyle' => 'primary-filled',
        'socialProof' => ['portfolio','stats','testimonials'],
        'primaryAction' => 'quote',
        'tone' => 'bold',
    ],

    'architect' => [
        'sectionOrder' => ['hero','portfolio','services','process','team','testimonials','cta','contact','footer'],
        'heroPattern' => 'full-width-image',
        'ctaStyle' => 'minimal',
        'socialProof' => ['portfolio','awards','testimonials'],
        'primaryAction' => 'consultation',
        'tone' => 'luxury',
    ],

    'interior-design' => [
        'sectionOrder' => ['hero','portfolio','services','process','beforeAfter','testimonials','cta','contact','footer'],
        'heroPattern' => 'full-width-image',
        'ctaStyle' => 'primary-filled',
        'socialProof' => ['portfolio','before-after','testimonials'],
        'primaryAction' => 'consultation',
        'tone' => 'luxury',
    ],

    'electrician' => [
        'sectionOrder' => ['hero','services','about','testimonials','cta','contact','footer'],
        'heroPattern' => 'centered',
        'ctaStyle' => 'primary-filled',
        'socialProof' => ['testimonials','reviews'],
        'primaryAction' => 'call',
        'tone' => 'modern',
    ],

    'plumber' => [
        'sectionOrder' => ['hero','services','about','testimonials','cta','contact','footer'],
        'heroPattern' => 'centered',
        'ctaStyle' => 'primary-filled',
        'socialProof' => ['testimonials','reviews'],
        'primaryAction' => 'call',
        'tone' => 'modern',
    ],

    'cleaning' => [
        'sectionOrder' => ['hero','services','pricing','about','testimonials','faq','cta','contact','footer'],
        'heroPattern' => 'centered',
        'ctaStyle' => 'primary-filled',
        'socialProof' => ['testimonials','reviews','trust-badges'],
        'primaryAction' => 'quote',
        'tone' => 'warm',
    ],

    'moving' => [
        'sectionOrder' => ['hero','services','process','pricing','testimonials','faq','cta','contact','footer'],
        'heroPattern' => 'centered',
        'ctaStyle' => 'primary-filled',
        'socialProof' => ['testimonials','reviews','stats'],
        'primaryAction' => 'quote',
        'tone' => 'warm',
    ],

    'auto-repair' => [
        'sectionOrder' => ['hero','services','about','testimonials','cta','contact','footer'],
        'heroPattern' => 'centered',
        'ctaStyle' => 'primary-filled',
        'socialProof' => ['testimonials','reviews'],
        'primaryAction' => 'appointment',
        'tone' => 'modern',
    ],

    'car-dealer' => [
        'sectionOrder' => ['hero','inventory','services','about','testimonials','cta','contact','footer'],
        'heroPattern' => 'full-width-image',
        'ctaStyle' => 'primary-filled',
        'socialProof' => ['reviews','stats','testimonials'],
        'primaryAction' => 'contact',
        'tone' => 'bold',
    ],

    // =================================================================
    // CREATIVE & MEDIA
    // =================================================================

    'photography' => [
        'sectionOrder' => ['hero','portfolio','services','about','testimonials','cta','contact','footer'],
        'heroPattern' => 'full-width-image',
        'ctaStyle' => 'minimal',
        'socialProof' => ['portfolio','testimonials','instagram'],
        'primaryAction' => 'contact',
        'tone' => 'luxury',
    ],

    'wedding' => [
        'sectionOrder' => ['hero','portfolio','services','about','testimonials','pricing','cta','contact','footer'],
        'heroPattern' => 'full-width-image',
        'ctaStyle' => 'luxury-filled',
        'socialProof' => ['portfolio','testimonials','instagram'],
        'primaryAction' => 'contact',
        'tone' => 'luxury',
    ],

    'event-hall' => [
        'sectionOrder' => ['hero','gallery','packages','about','testimonials','booking-cta','contact','footer'],
        'heroPattern' => 'full-width-image',
        'ctaStyle' => 'luxury-filled',
        'socialProof' => ['gallery','testimonials','stats'],
        'primaryAction' => 'booking',
        'tone' => 'luxury',
    ],

    // =================================================================
    // EDUCATION
    // =================================================================

    'school' => [
        'sectionOrder' => ['hero','about','programs','stats','testimonials','enrollment-cta','contact','footer'],
        'heroPattern' => 'centered',
        'ctaStyle' => 'primary-filled',
        'socialProof' => ['stats','testimonials','certifications'],
        'primaryAction' => 'enroll',
        'tone' => 'warm',
    ],

    'university' => [
        'sectionOrder' => ['hero','programs','campus','stats','testimonials','faculty','enrollment-cta','contact','footer'],
        'heroPattern' => 'full-width-image',
        'ctaStyle' => 'primary-filled',
        'socialProof' => ['stats','testimonials','rankings'],
        'primaryAction' => 'apply',
        'tone' => 'modern',
    ],

    'online-course' => [
        'sectionOrder' => ['hero','curriculum','instructors','benefits','testimonials','pricing','faq','enroll-cta','footer'],
        'heroPattern' => 'split-left',
        'ctaStyle' => 'primary-filled',
        'socialProof' => ['stats','testimonials','instructor-profiles'],
        'primaryAction' => 'enroll',
        'tone' => 'modern',
    ],

    // =================================================================
    // NONPROFIT & COMMUNITY
    // =================================================================

    'nonprofit' => [
        'sectionOrder' => ['hero','mission','impact','programs','stats','testimonials','donate-cta','contact','footer'],
        'heroPattern' => 'full-width-image',
        'ctaStyle' => 'primary-filled',
        'socialProof' => ['stats','testimonials','partners'],
        'primaryAction' => 'donate',
        'tone' => 'warm',
    ],

    // =================================================================
    // PROFESSIONAL SERVICES (catch-all)
    // =================================================================

    'professional' => [
        'sectionOrder' => ['hero','services','about','team','testimonials','faq','cta','contact','footer'],
        'heroPattern' => 'split-left',
        'ctaStyle' => 'primary-filled',
        'socialProof' => ['testimonials','certifications'],
        'primaryAction' => 'consultation',
        'tone' => 'modern',
    ],

    // =================================================================
    // FALLBACK
    // =================================================================

    'general' => [
        'sectionOrder' => ['hero','features','about','stats','testimonials','cta','contact','footer'],
        'heroPattern' => 'centered',
        'ctaStyle' => 'primary-filled',
        'socialProof' => ['testimonials','stats'],
        'primaryAction' => 'contact',
        'tone' => 'modern',
    ],
];
