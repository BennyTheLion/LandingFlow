<?php
/**
 * Knowledge Base — Reusable Component Patterns Library
 * Each component defines layout variants, design rules, and responsive behavior.
 * The generator uses these to build unique, industry-appropriate pages.
 */

return [

  // ====================================================================
  // HERO SECTIONS
  // ====================================================================
  'hero' => [
    'variants' => [
      'centered' => [
        'id' => 'centered',
        'label' => 'מרכזי',
        'layout' => 'text-center, max-width-680, cta-below',
        'best_for' => ['saas','ai','startups','consulting','coaching','courses'],
        'structure' => ['badge','headline','subtitle','cta_buttons','social_proof','hero_image'],
      ],
      'split' => [
        'id' => 'split',
        'label' => 'חצוי',
        'layout' => 'grid-2col, text-left, image-right',
        'best_for' => ['agencies','marketing','design','photography','realestate'],
        'structure' => ['headline','subtitle','cta','stats_row','hero_image'],
      ],
      'fullscreen' => [
        'id' => 'fullscreen',
        'label' => 'מסך מלא',
        'layout' => 'vh-100, overlay, centered-content',
        'best_for' => ['hotels','travel','restaurants','cafes','events','weddings'],
        'structure' => ['bg_video_or_image','overlay','headline','subtitle','cta','scroll_indicator'],
      ],
      'minimal' => [
        'id' => 'minimal',
        'label' => 'מינימליסטי',
        'layout' => 'text-left, no-image, clean-typography',
        'best_for' => ['lawyers','accounting','insurance','doctors','dentists'],
        'structure' => ['headline','subtitle','cta','trust_badges'],
      ],
      'illustration' => [
        'id' => 'illustration',
        'label' => 'איורי',
        'layout' => 'grid-2col, text-left, illustration-right',
        'best_for' => ['saas','ai','mobile-apps','tech'],
        'structure' => ['headline','subtitle','cta','illustration','logos'],
      ],
      'search_focused' => [
        'id' => 'search_focused',
        'label' => 'ממוקד חיפוש',
        'layout' => 'centered, search-bar-prominent',
        'best_for' => ['realestate','travel','ecommerce','job-boards'],
        'structure' => ['headline','search_bar','popular_searches'],
      ],
    ],
  ],

  // ====================================================================
  // FEATURES / SERVICES SECTIONS
  // ====================================================================
  'features' => [
    'variants' => [
      'grid_icons' => [
        'id' => 'grid_icons',
        'label' => 'גריד אייקונים',
        'layout' => 'grid-3-or-4-col, icon-top, title-below, description',
        'best_for' => 'universal',
      ],
      'list_alternating' => [
        'id' => 'list_alternating',
        'label' => 'רשימה מתחלפת',
        'layout' => 'alternating-image-text, left-right',
        'best_for' => ['saas','agencies','marketing'],
      ],
      'tabs' => [
        'id' => 'tabs',
        'label' => 'טאבים',
        'layout' => 'tab-navigation, content-panel-per-tab',
        'best_for' => ['saas','ai','tech'],
      ],
      'cards_horizontal' => [
        'id' => 'cards_horizontal',
        'label' => 'כרטיסיות אופקיות',
        'layout' => 'horizontal-scroll-cards, snap-scroll',
        'best_for' => ['ecommerce','retail','restaurants'],
      ],
      'numbered_steps' => [
        'id' => 'numbered_steps',
        'label' => 'שלבים ממוספרים',
        'layout' => 'numbered-list, step-icon, step-description',
        'best_for' => ['consulting','services','construction'],
      ],
    ],
  ],

  // ====================================================================
  // ABOUT SECTIONS
  // ====================================================================
  'about' => [
    'variants' => [
      'story' => [
        'id' => 'story',
        'label' => 'סיפור',
        'layout' => 'text-left, image-right, narrative-style',
        'best_for' => ['restaurants','cafes','boutique','creative'],
        'structure' => ['image','headline','story_text','stats','signature'],
      ],
      'mission_values' => [
        'id' => 'mission_values',
        'label' => 'משימה וערכים',
        'layout' => 'centered-headline, 3-column-values',
        'best_for' => ['nonprofits','startups','education'],
        'structure' => ['headline','mission_statement','values_grid','cta'],
      ],
      'timeline' => [
        'id' => 'timeline',
        'label' => 'ציר זמן',
        'layout' => 'vertical-timeline, milestones',
        'best_for' => ['startups','agencies','construction'],
        'structure' => ['headline','timeline_items','current_marker'],
      ],
      'team_focused' => [
        'id' => 'team_focused',
        'label' => 'ממוקד צוות',
        'layout' => 'grid-team-cards, role-under-photo',
        'best_for' => ['lawyers','doctors','dentists','agencies'],
        'structure' => ['headline','team_grid','bio_short','cta'],
      ],
    ],
  ],

  // ====================================================================
  // STATISTICS / SOCIAL PROOF
  // ====================================================================
  'stats' => [
    'variants' => [
      'counter_row' => [
        'id' => 'counter_row',
        'label' => 'שורת מונים',
        'layout' => 'horizontal-stats, animated-counter',
        'best_for' => 'universal',
        'structure' => ['stat_number','stat_label'],
      ],
      'logos_bar' => [
        'id' => 'logos_bar',
        'label' => 'שורת לוגואים',
        'layout' => 'horizontal-logo-scroll, grayscale-logos',
        'best_for' => ['saas','b2b','enterprise'],
        'structure' => ['headline','logo_grid'],
      ],
      'case_study_highlight' => [
        'id' => 'case_study_highlight',
        'label' => 'הבלטת תיק הצלחה',
        'layout' => 'split-layout, problem-solution-result',
        'best_for' => ['agencies','consulting','saas'],
        'structure' => ['problem','solution','result','testimonial'],
      ],
    ],
  ],

  // ====================================================================
  // TESTIMONIAL SECTIONS
  // ====================================================================
  'testimonials' => [
    'variants' => [
      'cards_grid' => [
        'id' => 'cards_grid',
        'label' => 'גריד כרטיסיות',
        'layout' => '3-column-grid, card-per-testimonial',
        'best_for' => 'universal',
      ],
      'slider' => [
        'id' => 'slider',
        'label' => 'קרוסלה',
        'layout' => 'horizontal-slider, one-testimonial-at-time',
        'best_for' => 'universal',
      ],
      'video' => [
        'id' => 'video',
        'label' => 'עם וידאו',
        'layout' => 'grid-with-video-thumbnails',
        'best_for' => ['courses','fitness','coaching'],
      ],
      'social_embed' => [
        'id' => 'social_embed',
        'label' => 'הטמעת רשתות',
        'layout' => 'tweet/testimonial-embeds, real-social-proof',
        'best_for' => ['saas','startups','ecommerce'],
      ],
    ],
  ],

  // ====================================================================
  // PRICING SECTIONS
  // ====================================================================
  'pricing' => [
    'variants' => [
      'three_tier' => [
        'id' => 'three_tier',
        'label' => '3 מסלולים',
        'layout' => '3-column-cards, middle-highlighted',
        'best_for' => ['saas','subscription','gyms','courses'],
        'structure' => ['plan_name','price','features_list','cta_button','popular_badge'],
      ],
      'comparison_table' => [
        'id' => 'comparison_table',
        'label' => 'טבלת השוואה',
        'layout' => 'feature-comparison-table, checkmarks',
        'best_for' => ['saas','hosting','insurance'],
        'structure' => ['feature_rows','plan_columns','checkmarks'],
      ],
      'simple_two' => [
        'id' => 'simple_two',
        'label' => '2 אפשרויות פשוטות',
        'layout' => '2-column, simple-choice',
        'best_for' => ['services','coaching','courses'],
        'structure' => ['plan_name','price','features','cta'],
      ],
    ],
  ],

  // ====================================================================
  // CTA SECTIONS
  // ====================================================================
  'cta' => [
    'variants' => [
      'banner' => [
        'id' => 'banner',
        'label' => 'באנר',
        'layout' => 'full-width-banner, centered-text, cta-button',
        'best_for' => 'universal',
      ],
      'split_cta' => [
        'id' => 'split_cta',
        'label' => 'CTA חצוי',
        'layout' => 'left-text, right-form-or-button',
        'best_for' => ['saas','marketing','insurance'],
      ],
      'sticky_bar' => [
        'id' => 'sticky_bar',
        'label' => 'פס דביק',
        'layout' => 'fixed-bottom-bar, scroll-triggered',
        'best_for' => ['ecommerce','saas','mobile-apps'],
      ],
    ],
  ],

  // ====================================================================
  // CONTACT SECTIONS
  // ====================================================================
  'contact' => [
    'variants' => [
      'form_map' => [
        'id' => 'form_map',
        'label' => 'טופס + מפה',
        'layout' => '2-column, form-left, map-right',
        'best_for' => ['local-business','restaurants','retail','doctors','dentists'],
      ],
      'simple_form' => [
        'id' => 'simple_form',
        'label' => 'טופס פשוט',
        'layout' => 'centered-form, max-width-500',
        'best_for' => ['saas','consulting','freelance'],
      ],
      'contact_cards' => [
        'id' => 'contact_cards',
        'label' => 'כרטיסי קשר',
        'layout' => '3-cards, phone-email-location',
        'best_for' => ['agencies','services','realestate'],
      ],
      'booking_widget' => [
        'id' => 'booking_widget',
        'label' => 'וידג׳ט הזמנה',
        'layout' => 'calendar-integration, time-slots',
        'best_for' => ['doctors','dentists','beauty','barbers','consulting'],
      ],
    ],
  ],

  // ====================================================================
  // FAQ SECTIONS
  // ====================================================================
  'faq' => [
    'variants' => [
      'accordion' => [
        'id' => 'accordion',
        'label' => 'אקורדיון',
        'layout' => 'expandable-questions, one-open-at-time',
        'best_for' => 'universal',
      ],
      'two_column' => [
        'id' => 'two_column',
        'label' => '2 עמודות',
        'layout' => '2-column-faq-grid, open-inline',
        'best_for' => ['saas','ecommerce','insurance'],
      ],
    ],
  ],

  // ====================================================================
  // INDUSTRY-SPECIFIC CUSTOM SECTIONS
  // ====================================================================
  'custom' => [
    // --- Restaurant / Cafe ---
    'menu' => [
      'layout' => '2-column-grid, dish-name, description, price',
      'mobile' => 'single-column-stack',
      'image_usage' => 'optional food-photo-per-dish',
    ],
    'gallery' => [
      'layout' => '3-column-masonry, lightbox-on-click',
      'mobile' => '2-column-grid',
      'note' => 'use-unsplash-food-photos',
    ],

    // --- Professional Services ---
    'team' => [
      'layout' => 'grid-cards, photo-circle, name, title, bio',
      'mobile' => 'single-column-cards',
    ],
    'specialties' => [
      'layout' => 'icon-grid, specialty-name, short-description',
      'mobile' => '2-column-grid',
    ],

    // --- Tech / SaaS ---
    'how_it_works' => [
      'layout' => '3-step-horizontal, connected-arrows, step-number-icon',
      'mobile' => 'vertical-stack',
    ],
    'integrations' => [
      'layout' => 'logo-grid, grayscale, hover-color',
      'mobile' => '3-column-logo-grid',
    ],

    // --- Fitness ---
    'schedule' => [
      'layout' => 'weekly-grid, day-columns, class-time-rows',
      'mobile' => 'horizontal-scroll',
    ],
    'membership' => [
      'layout' => '3-column-cards, price-prominent, features-list',
      'mobile' => 'vertical-stack',
    ],

    // --- Real Estate ---
    'properties' => [
      'layout' => 'grid-cards, photo, price, rooms, area, location',
      'mobile' => 'single-column',
    ],
    'agent' => [
      'layout' => 'split, photo-left, bio-right, license-badge',
      'mobile' => 'stacked',
    ],

    // --- Beauty / Spa ---
    'treatments' => [
      'layout' => 'service-list, name, duration, price, description',
      'mobile' => 'single-column-list',
    ],
    'booking' => [
      'layout' => 'cta-centered, available-hours, booking-button',
    ],

    // --- Education ---
    'courses' => [
      'layout' => 'grid-cards, level-badge, title, duration, description',
      'mobile' => 'single-column',
    ],
    'curriculum' => [
      'layout' => 'semester-sections, topic-tags',
      'mobile' => 'full-width',
    ],

    // --- Retail ---
    'products' => [
      'layout' => 'grid-cards, photo, name, price, add-to-cart-style',
      'mobile' => '2-column-grid',
    ],

    // --- Medical / Dental ---
    'before_after' => [
      'layout' => 'comparison-pairs, before-left, after-right',
      'mobile' => 'stacked-pairs',
    ],
    'emergency_cta' => [
      'layout' => 'prominent-banner, phone-number-large, 24-7-badge',
    ],

    // --- Construction / Home Services ---
    'portfolio' => [
      'layout' => 'masonry-grid, project-photo, project-name, location',
      'mobile' => '2-column',
    ],
    'process' => [
      'layout' => 'numbered-steps, icon-per-step, timeline-line',
      'mobile' => 'vertical-timeline',
    ],
  ],

  // ====================================================================
  // FOOTER SECTIONS
  // ====================================================================
  'footer' => [
    'variants' => [
      'simple_centered' => [
        'id' => 'simple_centered',
        'label' => 'ממורכז פשוט',
        'layout' => 'centered-logo, nav-links, copyright',
        'best_for' => ['saas','startups','apps'],
      ],
      'multi_column' => [
        'id' => 'multi_column',
        'label' => 'רב עמודות',
        'layout' => '4-column, logo-col, links-col, services-col, contact-col',
        'best_for' => ['agencies','ecommerce','realestate'],
      ],
      'dark_full' => [
        'id' => 'dark_full',
        'label' => 'כהה מלא',
        'layout' => 'dark-bg, multi-column, newsletter-form',
        'best_for' => ['saas','marketing','b2b'],
      ],
    ],
  ],

  // ====================================================================
  // DESIGN TOKENS — layout rules
  // ====================================================================
  'design_tokens' => [
    'spacing' => [
      'section_padding' => '80px 0',
      'section_padding_mobile' => '48px 0',
      'section_gap' => '60px',
      'card_gap' => '24px',
      'content_max_width' => '1100px',
      'text_max_width' => '680px',
    ],
    'grid' => [
      'default_columns' => 3,
      'mobile_columns' => 1,
      'tablet_columns' => 2,
    ],
    'typography' => [
      'h1_size' => 'clamp(2rem, 5vw, 3.2rem)',
      'h2_size' => 'clamp(1.6rem, 4vw, 2.4rem)',
      'h3_size' => '1.15rem',
      'body_size' => '1rem',
      'small_size' => '0.85rem',
      'line_height' => 1.6,
    ],
    'animation' => [
      'fade_in' => 'opacity 0→1, translateY 8px→0, 0.4s ease',
      'stagger_children' => '0.1s delay per child',
      'counter_animate' => 'count-up on scroll into view',
      'parallax' => 'subtle, hero-only',
    ],
    'mobile' => [
      'breakpoint' => '768px',
      'nav' => 'hamburger-menu',
      'grid' => 'single-column',
      'font_scale' => '90%',
      'section_padding' => '48px 0',
    ],
  ],
];
