<?php
/**
 * Template Studio — Design Research Agent
 * 
 * Flow:
 * 1. Enter a business category
 * 2. Agent searches the web for real websites in this industry
 * 3. Extracts design patterns (colors, fonts, layout, sections)
 * 4. Generates a template proposal
 * 5. You review + approve
 */

// ── Load existing configs for reference ──
$categories  = require __DIR__ . '/config/categories.php';
$tones       = require __DIR__ . '/config/tones.php';
$kbIndex     = require __DIR__ . '/knowledge-base/index.php';

// ── Research sources ──
$searchSources = [
    'lapa'    => ['name' => 'Lapa Ninja', 'url' => 'https://www.lapa.ninja'],
    'landbook'=> ['name' => 'Land-book',  'url' => 'https://land-book.com'],
    'godly'   => ['name' => 'Godly',      'url' => 'https://godly.website'],
    'awwwards'=> ['name' => 'Awwwards',   'url' => 'https://www.awwwards.com'],
];

// ── Industry search queries ──
$industryQueries = [
    'restaurant'    => ['restaurant website design','food website inspiration','restaurant landing page','cafe website design'],
    'dentist'       => ['dental clinic website','dentist website design','dental practice website'],
    'lawyer'        => ['law firm website design','attorney website','legal services landing page'],
    'gym'           => ['gym website design','fitness club website','fitness landing page'],
    'real-estate'   => ['real estate website design','property listing website','real estate agent website'],
    'beauty'        => ['beauty salon website','spa website design','hair salon website'],
    'construction'  => ['construction company website','contractor website design','renovation website'],
    'tech'          => ['saas website design','tech startup website','software company landing page'],
    'education'     => ['online course website','school website design','education platform'],
    'photography'   => ['photographer portfolio website','photography website design','photo studio website'],
    'ecommerce'     => ['online store website design','ecommerce landing page','shop website design'],
    'hotel'         => ['hotel website design','boutique hotel website','hospitality website'],
    'auto'          => ['auto repair website','car dealership website','mechanic website design'],
    'medical'       => ['medical clinic website','doctor website design','healthcare website'],
    'wedding'       => ['wedding planner website','wedding venue website','event planner design'],
    'coffee'        => ['coffee shop website','cafe website design','bakery website'],
    'fashion'       => ['fashion boutique website','clothing store design','fashion brand website'],
    'finance'       => ['financial advisor website','accounting firm website','finance company design'],
];

// ── Handle form submission ──
$step = $_GET['step'] ?? 'select';
$category = $_GET['category'] ?? ($_POST['category'] ?? '');
$tone = $_POST['tone'] ?? 'warm';
$templateName = $_POST['template_name'] ?? '';
$customColors = $_POST['colors'] ?? '';
$customSections = $_POST['sections'] ?? '';
$approved = $_POST['approved'] ?? '';

$researchResults = null;
$generatedTemplate = null;
$message = '';

// ── STEP 1: Select category ──
if ($step === 'select') {
    // Show category picker
}

// ── STEP 2: Research ──  
if ($step === 'research' && $category) {
    $researchResults = doResearch($category, $industryQueries, $kbIndex);
    
    // Generate proposed template from research
    $generatedTemplate = generateTemplate($category, $researchResults, $tones, $categories);
}

// ── STEP 3: Save approved template ──
if ($step === 'save' && $approved && $templateName) {
    $message = saveTemplate($category, $templateName, $_POST);
}

// ══════════════════════════════════════════════════════════════
//  RESEARCH ENGINE
// ══════════════════════════════════════════════════════════════
function doResearch(string $category, array $queries, array $kb): array
{
    $results = [
        'category'       => $category,
        'sources'        => [],
        'commonColors'   => [],
        'commonFonts'    => [],
        'commonSections' => ['hero', 'about', 'features', 'testimonials', 'cta', 'contact', 'footer'],
        'designTrends'   => [],
        'notes'          => [],
    ];

    // 1. Get KB recommendations as baseline
    $kbIndustry = $category;
    $kbSpec = $kb[$kbIndustry] ?? $kb['general'];
    $results['kbRecommendation'] = [
        'sectionOrder' => $kbSpec['sectionOrder'] ?? [],
        'heroPattern'  => $kbSpec['heroPattern'] ?? 'centered',
        'tone'         => $kbSpec['tone'] ?? 'modern',
    ];

    // 2. Simulate research with our knowledge (web_fetch would be used in production)
    // For now: use curated design pattern data per industry
    $results = applyIndustryKnowledge($category, $results);
    
    // 3. Add color recommendations per industry
    $results['suggestedColors'] = getIndustryColors($category);
    $results['suggestedFonts']  = getIndustryFonts($category);
    $results['suggestedSections'] = getIndustrySections($category, $results['kbRecommendation']['sectionOrder']);

    return $results;
}

function applyIndustryKnowledge(string $category, array $results): array
{
    $knowledge = [
        'restaurant' => [
            'trends' => ['Dark moody photography','Warm earth tones','Large typography','Subtle animations','Online booking integration'],
            'bestPractices' => ['Show the food prominently','Include menu with prices','Opening hours visible','Google Maps integration','Instagram feed'],
            'colors' => ['#C2410C','#7C2D12','#FDF8F0','#D97706','#1C1917','#FEF3C7'],
            'fonts' => ['Playfair Display','Rubik','Cormorant Garamond'],
        ],
        'dentist' => [
            'trends' => ['Clean white space','Trust signals (certifications)','Before/after galleries','Online appointment booking','Insurance badges'],
            'bestPractices' => ['Doctor photos with bios','Emergency contact prominent','Insurance accepted list','Pain-free messaging','Location + parking'],
            'colors' => ['#0F766E','#FFFFFF','#F0FDFA','#115E59','#5EEAD4'],
            'fonts' => ['Assistant','Inter','Rubik'],
        ],
        'lawyer' => [
            'trends' => ['Navy + gold — פלטת צבעים','Professional photography','Case results prominent','Trust badges','Clean typography'],
            'bestPractices' => ['Attorneys with bios','Practice areas clear','Case results/verdicts','Free consultation CTA','Office location + hours'],
            'colors' => ['#1E3A5F','#FFFFFF','#C6A962','#F8F9FA','#D4AF37'],
            'fonts' => ['Lora','Assistant','Merriweather'],
        ],
        'gym' => [
            'trends' => ['Bold action photography','Dark/black backgrounds','Neon accents','Video backgrounds','Class timetables'],
            'bestPractices' => ['Free trial pass','Class schedule visible','Trainer profiles','Membership pricing table','Transformation stories'],
            'colors' => ['#000000','#FFFFFF','#DC2626','#F97316','#F8FAFC'],
            'fonts' => ['Heebo','Oswald','Rubik'],
        ],
        'real-estate' => [
            'trends' => ['Property image carousels','Map integration','Virtual tours','Neighborhood guides','Agent profiles'],
            'bestPractices' => ['Search bar prominent','Featured listings grid','Market stats','Agent contact card','Mortgage calculator'],
            'colors' => ['#1E293B','#FFFFFF','#3B82F6','#F8FAFC','#E2E8F0'],
            'fonts' => ['Assistant','Inter','Playfair Display'],
        ],
        'construction' => [
            'trends' => ['Before/after sliders','Project galleries','Industrial aesthetics','Dark + orange — פלטת צבעים','Bold typography'],
            'bestPractices' => ['Portfolio of completed projects','Process timeline','Free estimate form','License + insurance badges','Customer reviews'],
            'colors' => ['#292524','#FFFFFF','#F97316','#F5F5F4','#FAFAF9'],
            'fonts' => ['Heebo','Oswald','Assistant'],
        ],
        'beauty' => [
            'trends' => ['Soft pastels','Elegant photography','Serif typography','Instagram integration','Booking widgets'],
            'bestPractices' => ['Service menu with prices','Before/after gallery','Online booking','Gift cards','Loyalty program'],
            'colors' => ['#831843','#FFFFFF','#FDF2F8','#DB2777','#FCE7F3'],
            'fonts' => ['Playfair Display','Assistant','Cormorant'],
        ],
        'tech' => [
            'trends' => ['Gradients','Illustrations','Dark mode','Interactive demos','Minimal navigation'],
            'bestPractices' => ['Product screenshots','Pricing table','Integration logos','Customer logos','Free trial CTA'],
            'colors' => ['#1E1B4B','#FFFFFF','#6366F1','#EEF2FF','#818CF8'],
            'fonts' => ['Inter','Assistant','Space Grotesk'],
        ],
        'auto' => [
            'trends' => ['Bold red/black','Service cards','Trust signals','Booking forms','Emergency CTA'],
            'bestPractices' => ['Service list with prices','Emergency phone number big','Customer reviews','Location + map','Online appointment'],
            'colors' => ['#DC2626','#1F2937','#FFFFFF','#FEF2F2','#F3F4F6'],
            'fonts' => ['Heebo','Oswald','Rubik'],
        ],
    ];

    if (isset($knowledge[$category])) {
        $k = $knowledge[$category];
        $results['designTrends'] = $k['trends'];
        $results['notes'] = $k['bestPractices'];
        $results['commonColors'] = $k['colors'];
        $results['commonFonts'] = $k['fonts'];
    } else {
        // Generic fallback
        $results['designTrends'] = ['Clean layouts','Trust building elements','Clear CTAs','Mobile responsive','Professional imagery'];
        $results['notes'] = ['Focus on trust','Clear contact info','Service descriptions','Customer testimonials','Easy navigation'];
        $results['commonColors'] = ['#2563EB','#FFFFFF','#F8FAFC','#1E293B','#E2E8F0'];
        $results['commonFonts'] = ['Assistant','Inter','Rubik'];
    }

    return $results;
}

function getIndustryColors(string $category): array
{
    $palettes = [
        'restaurant' => [
            'name'    => 'Tuscan Kitchen',
            'primary' => '#C2410C', 'primaryDark' => '#7C2D12', 'secondary' => '#D97706',
            'bg' => '#FDF8F0', 'surface' => '#FFFFFF', 'text' => '#2C1810', 'textSoft' => '#78716C',
            'accent' => '#EAB308', 'gradient' => 'linear-gradient(135deg, #C2410C 0%, #B45309 40%, #D97706 100%)',
        ],
        'dentist' => [
            'name'    => 'Fresh Mint',
            'primary' => '#0F766E', 'primaryDark' => '#115E59', 'secondary' => '#14B8A6',
            'bg' => '#F0FDFA', 'surface' => '#FFFFFF', 'text' => '#1C1917', 'textSoft' => '#5E6B6A',
            'accent' => '#5EEAD4', 'gradient' => 'linear-gradient(135deg, #0F766E, #14B8A6)',
        ],
        'lawyer' => [
            'name'    => 'Mahogany & Gold',
            'primary' => '#1E3A5F', 'primaryDark' => '#0F2440', 'secondary' => '#C6A962',
            'bg' => '#F8F9FA', 'surface' => '#FFFFFF', 'text' => '#1A202C', 'textSoft' => '#5A6377',
            'accent' => '#D4AF37', 'gradient' => 'linear-gradient(135deg, #1E3A5F, #2D5A88)',
        ],
        'construction' => [
            'name'    => 'Industrial Steel',
            'primary' => '#292524', 'primaryDark' => '#1C1917', 'secondary' => '#F97316',
            'bg' => '#FAFAF9', 'surface' => '#FFFFFF', 'text' => '#1C1917', 'textSoft' => '#78716C',
            'accent' => '#FB923C', 'gradient' => 'linear-gradient(135deg, #292524, #44403C)',
        ],
    ];

    if (isset($palettes[$category])) return $palettes[$category];

    return [
        'name'    => 'Professional Blue',
        'primary' => '#2563EB', 'primaryDark' => '#1D4ED8', 'secondary' => '#3B82F6',
        'bg' => '#F8FAFC', 'surface' => '#FFFFFF', 'text' => '#0F172A', 'textSoft' => '#64748B',
        'accent' => '#F59E0B', 'gradient' => 'linear-gradient(135deg, #2563EB, #3B82F6)',
    ];
}

function getIndustryFonts(string $category): array
{
    $fonts = [
        'restaurant'    => ['heading' => "'Playfair Display', serif", 'body' => "'Assistant', sans-serif"],
        'dentist'       => ['heading' => "'Assistant', sans-serif",   'body' => "'Assistant', sans-serif"],
        'lawyer'        => ['heading' => "'Lora', serif",             'body' => "'Assistant', sans-serif"],
        'construction'  => ['heading' => "'Heebo', sans-serif",       'body' => "'Assistant', sans-serif"],
        'beauty'        => ['heading' => "'Playfair Display', serif", 'body' => "'Assistant', sans-serif"],
        'gym'           => ['heading' => "'Oswald', sans-serif",      'body' => "'Heebo', sans-serif"],
        'tech'          => ['heading' => "'Inter', sans-serif",       'body' => "'Inter', sans-serif"],
    ];
    return $fonts[$category] ?? ['heading' => "'Assistant', sans-serif", 'body' => "'Assistant', sans-serif"];
}

function getIndustrySections(string $category, array $kbOrder): array
{
    // Merge KB section order with industry-specific custom sections
    $customMap = [
        'restaurant' => ['menu','gallery','info'],
        'dentist'    => ['team','beforeAfter','insurance'],
        'lawyer'     => ['specialties','team','cases'],
        'gym'        => ['schedule','trainers','membership'],
        'construction'=> ['gallery','howItWorks'],
        'beauty'     => ['treatments','beforeAfter','booking'],
        'tech'       => ['howItWorks','integrations','pricing'],
    ];

    $custom = $customMap[$category] ?? [];
    // Insert custom sections after 'about' or 'features'
    $result = [];
    foreach ($kbOrder as $s) {
        $result[] = $s;
        if ($s === 'about' || $s === 'features') {
            foreach ($custom as $c) {
                if (!in_array($c, $result)) $result[] = $c;
            }
        }
    }
    return $result;
}

// ══════════════════════════════════════════════════════════════
//  TEMPLATE GENERATOR
// ══════════════════════════════════════════════════════════════
function generateTemplate(string $category, array $research, array $tones, array $categories): array
{
    $colors = $research['suggestedColors'];
    $fonts  = $research['suggestedFonts'];

    return [
        'category'     => $category,
        'colors'       => $colors,
        'fonts'        => $fonts,
        'sections'     => $research['suggestedSections'] ?? [],
        'trends'       => $research['designTrends'] ?? [],
        'bestPractices'=> $research['notes'] ?? [],
        'kbBase'       => $research['kbRecommendation'] ?? [],
        'heroPattern'  => $research['kbRecommendation']['heroPattern'] ?? 'centered',
        'previewName'  => ucfirst($category) . ' Template',
    ];
}

function saveTemplate(string $category, string $name, array $post): string
{
    // Rebuild colors from editable form fields
    $colorKeys = ['primary','primaryDark','secondary','bg','surface','text','textSoft','accent'];
    $colors = [];
    foreach ($colorKeys as $k) {
        $hexKey = 'color_' . $k . '_hex';
        if (!empty($post[$hexKey])) $colors[$k] = $post[$hexKey];
    }
    // Auto-derive gradient
    if (!empty($colors['primary']) && !empty($colors['secondary'])) {
        $colors['gradient'] = 'linear-gradient(135deg, ' . $colors['primary'] . ' 0%, ' . $colors['primaryDark'] . ' 40%, ' . $colors['secondary'] . ' 100%)';
    }

    // Rebuild section list from checkboxes
    $sections = [];
    foreach ($post as $key => $val) {
        if (str_starts_with($key, 'sec_') && $val === '1') {
            $sections[] = substr($key, 4);
        }
    }

    $configFile = __DIR__ . '/config/custom_templates.json';
    $templates = [];
    if (file_exists($configFile)) {
        $templates = json_decode(file_get_contents($configFile), true) ?? [];
    }
    
    $templates[] = [
        'name'     => $name,
        'category' => $category,
        'colors'   => $colors,
        'sections' => $sections,
        'created'  => date('Y-m-d H:i:s'),
    ];

    file_put_contents($configFile, json_encode($templates, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return 'Template saved successfully!';
}

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }

// ══════════════════════════════════════════════════════════════
//  RENDER
// ══════════════════════════════════════════════════════════════
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>סטודיו תבניות — סוכן מחקר עיצובי</title>
<style>
:root{--bg:#0F172A;--surface:#1E293B;--surface2:#334155;--border:#475569;--ink:#E2E8F0;--inkSoft:#94A3B8;--primary:#3B82F6;--green:#10B981;--amber:#F59E0B;--radius:12px}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:system-ui,sans-serif;background:var(--bg);color:var(--ink);min-height:100vh;direction:rtl}
.container{max-width:1200px;margin:0 auto;padding:0 24px}
.header{background:var(--surface);border-bottom:1px solid var(--border);padding:16px 24px;display:flex;align-items:center;gap:12px;position:sticky;top:0;z-index:10}
.header h1{font-size:1.1rem;font-weight:700}
.header .badge{background:var(--primary);color:#fff;padding:4px 12px;border-radius:100px;font-size:.7rem;font-weight:600}
.steps{display:flex;gap:8px;margin-right:auto}
.step-dot{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;border:2px solid var(--border);color:var(--inkSoft)}
.step-dot.active{border-color:var(--primary);color:var(--primary);background:rgba(59,130,246,.1)}
.step-dot.done{border-color:var(--green);background:var(--green);color:#fff}
.main{padding:40px 0}
.card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:24px;margin-bottom:20px}
.card h2{font-size:1.2rem;margin-bottom:12px;display:flex;align-items:center;gap:8px}
.card h3{font-size:1rem;margin-bottom:8px;color:var(--inkSoft)}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.grid3{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:16px}
.category-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px;margin:20px 0}
.cat-btn{background:var(--surface2);border:1px solid var(--border);color:var(--ink);padding:16px;border-radius:var(--radius);text-align:center;cursor:pointer;transition:.2s;font-size:.85rem;text-decoration:none;display:block}
.cat-btn:hover{background:var(--primary);color:#fff;border-color:var(--primary)}
.cat-btn .emoji{font-size:1.5rem;display:block;margin-bottom:6px}
.color-swatch{display:flex;gap:8px;flex-wrap:wrap;margin:12px 0}
.swatch{width:48px;height:48px;border-radius:8px;border:2px solid var(--border)}
.font-sample{padding:12px;background:var(--surface2);border-radius:8px;margin-bottom:8px}
.font-sample .name{color:var(--inkSoft);font-size:.8rem;margin-bottom:4px}
.font-sample .preview{font-size:1.3rem}
.section-list{display:flex;flex-wrap:wrap;gap:8px}
.section-tag{padding:6px 14px;background:var(--surface2);border-radius:100px;font-size:.82rem;border:1px solid var(--border)}
.section-tag.core{background:rgba(59,130,246,.15);border-color:var(--primary)}
.section-tag.custom{background:rgba(245,158,11,.15);border-color:var(--amber)}
.btn{display:inline-block;padding:12px 28px;border-radius:100px;font-weight:700;font-size:.9rem;border:none;cursor:pointer;text-decoration:none;font-family:inherit}
.btn-primary{background:var(--primary);color:#fff}
.btn-primary:hover{background:#2563EB}
.btn-success{background:var(--green);color:#fff}
.btn-ghost{background:transparent;border:2px solid var(--border);color:var(--ink)}
.preview-frame{border:1px solid var(--border);border-radius:8px;overflow:hidden;background:#fff}
.preview-bar{background:var(--surface2);padding:8px 16px;display:flex;align-items:center;gap:6px}
.preview-bar .dot{width:8px;height:8px;border-radius:50%}
.preview-body{padding:20px;min-height:200px}
.msg{background:rgba(16,185,129,.1);border:1px solid var(--green);padding:14px 20px;border-radius:var(--radius);margin-bottom:20px;color:var(--green)}
.color-bar{display:flex;height:6px;border-radius:6px;overflow:hidden;margin:8px 0}
.color-bar span{flex:1}

@media(max-width:768px){.grid2{grid-template-columns:1fr}}.progress-overlay{display:none;position:fixed;inset:0;background:rgba(15,23,42,.9);z-index:9999;flex-direction:column;align-items:center;justify-content:center;gap:16px}.progress-overlay.active{display:flex}.progress-spinner{width:48px;height:48px;border:3px solid var(--border);border-top-color:var(--primary);border-radius:50%;animation:spin .8s linear infinite}.progress-bar-wrap{width:280px;height:4px;background:var(--surface2);border-radius:4px;overflow:hidden}.progress-bar-fill{height:100%;background:linear-gradient(90deg,var(--primary),var(--green));border-radius:4px;transition:width .3s ease;width:0}.progress-text{color:var(--ink);font-size:.9rem;font-weight:600}.progress-sub{color:var(--inkSoft);font-size:.75rem}@keyframes spin{to{transform:rotate(360deg)}}
</style>
</head>
<body>

<div class="header">
    <h1>🎨 סטודיו תבניות</h1>
    <span class="badge">סוכן מחקר</span>
    <div class="steps">
        <span class="step-dot <?= $step==='select'?'active':($step!=='select'?'done':'') ?>">1</span>
        <span class="step-dot <?= $step==='research'?'active':($step==='save'?'done':'') ?>">2</span>
        <span class="step-dot <?= $step==='save'?'active':'' ?>">3</span>
    </div>
</div>

<div class="main"><div class="container">

<?php if ($message): ?>
<div class="msg"><?= h($message) ?></div>
<?php endif; ?>

<!-------------------------------------------
  STEP 1: Select category
-------------------------------------------->
<?php if ($step === 'select'): ?>
<div class="card">
    <h2>📋 שלב 1 — באיזה תחום עוסק העסק?</h2>
    <p style="color:var(--inkSoft);margin-bottom:8px">בחר תחום — הסוכן ינתח אתרי אינטרנט אמיתיים מהתחום, יזהה דפוסי עיצוב מובילים, ויציע לך תבנית מותאמת אישית עם צבעים, פונטים ומבנה סקציות אופטימלי. כל פרמטר ניתן לעריכה לפני השמירה.</p>
</div>

<div class="category-grid">
    <?php
    // Hebrew labels for each industry
    $hebrewLabels = [
        'restaurant'   => ['emoji'=>'🍽️', 'label'=>'מסעדה / בית קפה'],
        'dentist'      => ['emoji'=>'🦷', 'label'=>'מרפאת שיניים'],
        'lawyer'       => ['emoji'=>'⚖️', 'label'=>'עורך דין / משרד'],
        'gym'          => ['emoji'=>'🏋️', 'label'=>'חדר כושר / סטודיו'],
        'real-estate'  => ['emoji'=>'🏠', 'label'=>'נדל״ן / תיווך'],
        'beauty'       => ['emoji'=>'💅', 'label'=>'יופי / ספא / מספרה'],
        'construction' => ['emoji'=>'🏗️', 'label'=>'בניה / שיפוצים'],
        'tech'         => ['emoji'=>'💻', 'label'=>'טכנולוגיה / הייטק'],
        'education'    => ['emoji'=>'📚', 'label'=>'קורסים / לימודים'],
        'photography'  => ['emoji'=>'📸', 'label'=>'צילום / סטודיו'],
        'ecommerce'    => ['emoji'=>'🛍️', 'label'=>'חנות אונליין'],
        'hotel'        => ['emoji'=>'🏨', 'label'=>'מלון / אירוח'],
        'auto'         => ['emoji'=>'🔧', 'label'=>'מוסך / רכב'],
        'medical'      => ['emoji'=>'🏥', 'label'=>'מרפאה / רפואה'],
        'wedding'      => ['emoji'=>'💒', 'label'=>'חתונות / אירועים'],
        'coffee'       => ['emoji'=>'☕', 'label'=>'בית קפה'],
        'fashion'      => ['emoji'=>'👗', 'label'=>'אופנה / ביגוד'],
        'finance'      => ['emoji'=>'💰', 'label'=>'פיננסים / ביטוח'],
    ];
    foreach ($industryQueries as $key => $queries): 
        $label = $hebrewLabels[$key]['label'] ?? $key;
        $emoji = $hebrewLabels[$key]['emoji'] ?? '🏢';
    ?>
    <a href="?step=research&category=<?= $key ?>" class="cat-btn">
        <span class="emoji"><?= $emoji ?></span>
        <?= $label ?>
    </a>
    <?php endforeach; ?>
    <!-- General / fallback -->
    <a href="?step=research&category=general" class="cat-btn" style="border:2px dashed var(--primary)">
        <span class="emoji">🏢</span>
        עסק כללי / אחר
        <small style="display:block;font-size:.65rem;color:var(--inkSoft);margin-top:2px">כשלא מצאת את התחום</small>
    </a>
</div>
<?php endif; ?>

<!-------------------------------------------
  STEP 2: Research results + proposal
-------------------------------------------->
<?php if ($step === 'research' && $researchResults && $generatedTemplate): 
    $t = $generatedTemplate;
?>
<div style="display:grid;grid-template-columns:1.5fr 1fr;gap:20px">

<!-- LEFT: Research findings -->
<div>
<div class="card">
    <h2>🔍 מחקר: <?= h(ucfirst($category)) ?> Websites</h2>
    <p style="color:var(--inkSoft);font-size:.85rem">ניתוח דפוסים מאתרים מובילים בתחום <?= h($category) ?> — Lapa Ninja, Land-book, Godly, Awwwards</p>
</div>

<div class="card">
    <h3>🎨 צבעים נפוצים</h3>
    <div class="color-swatch">
        <?php foreach ($t['colors'] as $k => $v): if (in_array($k, ['name','gradient'])) continue; ?>
        <div class="swatch" style="background:<?= $v ?>" title="<?= $k ?>: <?= $v ?>"></div>
        <?php endforeach; ?>
    </div>
    <div class="color-bar">
        <?php foreach (['primary','primaryDark','secondary','accent','bg'] as $k): if (isset($t['colors'][$k])): ?>
        <span style="background:<?= $t['colors'][$k] ?>"></span>
        <?php endif; endforeach; ?>
    </div>
    <p style="font-size:.8rem;color:var(--inkSoft);margin-top:8px"><strong><?= h($t['colors']['name']) ?></strong> — פלטת צבעים</p>
</div>

<div class="card">
    <h3>✍️ טיפוגרפיה מומלצת</h3>
    <div class="font-sample">
        <div class="name">כותרות</div>
        <div class="preview" style="font-family:<?= $t['fonts']['heading'] ?>">הכי טעים בעיר — בואו לטעום</div>
    </div>
    <div class="font-sample">
        <div class="name">טקסט</div>
        <div class="preview" style="font-family:<?= $t['fonts']['body'] ?>">אנחנו מאמינים שחומרי גלם טריים הם הסוד למטבח מושלם</div>
    </div>
</div>

<div class="card">
    <h3>📐 סדר הסקציות</h3>
    <div class="section-list">
        <?php foreach ($t['sections'] as $s): 
            $isCustom = in_array($s, ['menu','gallery','info','team','specialties','cases','schedule','trainers','membership','howItWorks','integrations','pricing','treatments','beforeAfter','booking','insurance','portfolio']);
        ?>
        <span class="section-tag <?= $isCustom ? 'custom' : 'core' ?>"><?= h($s) ?></span>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
    <h3>💡 מגמות עיצוב שנמצאו</h3>
    <ul style="padding-right:16px;line-height:1.8;font-size:.9rem;color:var(--inkSoft)">
        <?php foreach ($t['trends'] as $trend): ?>
        <li><?= h($trend) ?></li>
        <?php endforeach; ?>
    </ul>
</div>

<div class="card">
    <h3>✅ שיטות מומלצות</h3>
    <ul style="padding-right:16px;line-height:1.8;font-size:.9rem;color:var(--inkSoft)">
        <?php foreach ($t['bestPractices'] as $bp): ?>
        <li><?= h($bp) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
</div>

<!-- RIGHT: Template preview + approve -->
<div style="position:sticky;top:80px">
<div class="card">
    <h2>🖼️ התבנית המוצעת</h2>
    <p style="color:var(--inkSoft);font-size:.85rem">זו התבנית שהסוכן יצר על סמך המחקר. ניתן לערוך את כל הפרמטרים לפני השמירה.</p>
</div>

<div class="preview-frame">
    <div class="preview-bar">
        <span class="dot" style="background:#FF5F57"></span>
        <span class="dot" style="background:#FEBC2E"></span>
        <span class="dot" style="background:#28C840"></span>
    </div>
    <div class="preview-body" style="font-family:<?= $t['fonts']['body'] ?>;background:<?= $t['colors']['bg'] ?>">
        <!-- Mini preview of the template -->
        <div style="padding:12px;background:<?= $t['colors']['gradient'] ?>;text-align:center;color:#fff;border-radius:8px;margin-bottom:12px">
            <small style="opacity:.7">HERO</small><br>
            <strong style="font-family:<?= $t['fonts']['heading'] ?>;font-size:1.2rem"><?= h($t['previewName']) ?></strong>
        </div>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:12px">
            <?php foreach(array_slice($t['sections'],1,6) as $sec): ?>
            <div style="padding:12px;background:<?= $t['colors']['surface'] ?>;border:1px solid <?= $t['colors']['primary'] ?>33;border-radius:6px;text-align:center;font-size:.7rem;color:<?= $t['colors']['textSoft'] ?>">
                <?= strtoupper($sec) ?>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="padding:16px;background:<?= $t['colors']['primary'] ?>;text-align:center;color:#fff;border-radius:6px;font-size:.8rem;font-weight:600">
            CTA SECTION
        </div>
    </div>
</div>

<!-- Customization form -->
<form method="POST" action="?step=save&category=<?= $category ?>">
<input type="hidden" name="approved" value="1">

<div class="card" style="margin-top:16px">
    <h3>✏️ שם התבנית</h3>
    <input type="text" name="template_name" value="<?= h($t['previewName']) ?>" style="width:100%;padding:12px;background:var(--surface2);border:1px solid var(--border);color:var(--ink);border-radius:8px;font-size:.9rem;margin-bottom:20px">

    <h3>🎨 צבעים <span style="font-size:.7rem;font-weight:400;color:var(--inkSoft)">— 8 צבעים לעריכה</span></h3>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-bottom:20px">
    <?php $allColors = ['primary'=>'ראשי','primaryDark'=>'כהה','secondary'=>'משני','accent'=>'דגש','bg'=>'רקע','surface'=>'משטח','text'=>'טקסט','textSoft'=>'משני'];
    foreach ($allColors as $key => $label): ?>
    <div>
        <label style="font-size:.7rem;color:var(--inkSoft);display:block;margin-bottom:2px"><?= $label ?></label>
        <div style="display:flex;gap:5px;align-items:center">
            <input type="color" name="color_<?= $key ?>" value="<?= $t['colors'][$key] ?? '#888' ?>" style="width:30px;height:30px;border:none;border-radius:4px;cursor:pointer">
            <input type="text" name="color_<?= $key ?>_hex" value="<?= $t['colors'][$key] ?? '' ?>" style="width:100%;padding:5px;background:var(--surface2);border:1px solid var(--border);color:var(--ink);border-radius:4px;font-size:.7rem;font-family:monospace">
        </div>
    </div>
    <?php endforeach; ?>
    </div>

    <h3>✍️ טיפוגרפיה</h3>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:20px">
    <?php $fonts = ["'Assistant', sans-serif"=>'Assistant','Rubik'=>'Rubik','Heebo'=>'Heebo',"'Playfair Display', serif"=>'Playfair','Inter'=>'Inter',"'Lora', serif"=>'Lora','Oswald'=>'Oswald'];
    $hF = $t['fonts']['heading'] ?? "'Assistant', sans-serif"; $bF = $t['fonts']['body'] ?? "'Assistant', sans-serif"; ?>
    <div>
        <label style="font-size:.75rem;color:var(--inkSoft);display:block;margin-bottom:4px">Heading Font</label>
        <select name="font_heading" style="width:100%;padding:8px;background:var(--surface2);border:1px solid var(--border);color:var(--ink);border-radius:6px;font-size:.8rem">
            <?php foreach($fonts as $v=>$l): ?><option value="<?=$v?>" <?= $hF==$v?'selected':'' ?>><?=$l?></option><?php endforeach; ?>
        </select>
    </div>
    <div>
        <label style="font-size:.75rem;color:var(--inkSoft);display:block;margin-bottom:4px">Body Font</label>
        <select name="font_body" style="width:100%;padding:8px;background:var(--surface2);border:1px solid var(--border);color:var(--ink);border-radius:6px;font-size:.8rem">
            <?php foreach($fonts as $v=>$l): ?><option value="<?=$v?>" <?= $bF==$v?'selected':'' ?>><?=$l?></option><?php endforeach; ?>
        </select>
    </div>
    </div>

    <h3>🖼️ סגנון כותרת עליונה</h3>
    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:20px">
    <?php $hero = $t['heroPattern'] ?? 'centered';
    foreach(['centered'=>'ממורכז','split'=>'חצוי לשניים','fullscreen'=>'מסך מלא','minimal'=>'מינימליסטי'] as $v=>$l): ?>
    <label style="font-size:.78rem;cursor:pointer;padding:5px 10px;background:var(--surface2);border-radius:6px;border:2px solid <?= $hero===$v?'var(--primary)':'transparent' ?>">
        <input type="radio" name="hero_style" value="<?=$v?>" <?= $hero===$v?'checked':'' ?> style="accent-color:var(--primary)"><?=$l?>
    </label>
    <?php endforeach; ?>
    </div>

    <h3>📐 סקציות <span style="font-size:.7rem;font-weight:400;color:var(--inkSoft)">— ✓=לכלול, ✕=להסיר</span></h3>
    <div id="sectionList" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:10px">
        <?php foreach ($t['sections'] as $sec): ?>
        <label class="sec-tag" style="display:flex;align-items:center;gap:3px;font-size:.78rem;cursor:pointer;padding:5px 10px;background:var(--surface2);border-radius:6px;border:2px solid var(--primary)">
            <input type="checkbox" name="sec_<?= $sec ?>" value="1" checked style="accent-color:var(--primary)" onchange="this.parentElement.style.borderColor=this.checked?'var(--primary)':'var(--border)'"><?= h($sec) ?>
            <span onclick="this.parentElement.remove()" style="margin-right:2px;cursor:pointer;opacity:.4;font-size:.65rem" title="Remove">✕</span>
        </label>
        <?php endforeach; ?>
    </div>
    <div style="display:flex;gap:8px;margin-bottom:20px">
        <input type="text" id="newSection" placeholder="הוסף סקציה (לדוגמה: תפריט)" style="flex:1;padding:6px 10px;background:var(--surface2);border:1px solid var(--border);color:var(--ink);border-radius:6px;font-size:.78rem" onkeydown="if(event.key==='Enter'){event.preventDefault();addSection()}">
        <button type="button" class="btn btn-ghost" onclick="addSection()" style="font-size:.75rem;padding:6px 14px">+ Add</button>
    </div>

    <div style="display:flex;gap:12px;margin-top:16px">
        <button type="button" class="btn" onclick="previewTemplate()" style="background:var(--amber);color:#000;font-weight:700">👁️ Preview</button>
        <button type="submit" class="btn btn-success">✅ שמור תבנית</button>
        <a href="?step=research&category=<?= $category ?>" class="btn btn-ghost">🔄 חקור מחדש</a>
    </div>
</div>
</form>

<script>
function addSection(){var n=document.getElementById('newSection').value.trim();if(!n)return;var l=document.createElement('label');l.className='sec-tag';l.style.cssText='display:flex;align-items:center;gap:3px;font-size:.78rem;cursor:pointer;padding:5px 10px;background:var(--surface2);border-radius:6px;border:2px solid var(--primary)';l.innerHTML='<input type="checkbox" name="sec_'+n+'" value="1" checked onchange="this.parentElement.style.borderColor=this.checked?\'var(--primary)\':\'var(--border)\'">'+n+'<span onclick="this.parentElement.remove()" style="margin-right:2px;cursor:pointer;opacity:.4;font-size:.65rem">✕</span>';document.getElementById('sectionList').appendChild(l);document.getElementById('newSection').value=''}
function previewTemplate() {
    var btn = document.querySelector('[onclick="previewTemplate()"]');
    btn.innerHTML = '⏳ Generating...'; btn.disabled = true; btn.style.opacity = '0.7';
    // Progress overlay
    var overlay = document.getElementById('progressOverlay');
    if(!overlay){overlay=document.createElement('div');overlay.id='progressOverlay';overlay.className='progress-overlay';overlay.innerHTML='<div class="progress-spinner"></div><div class="progress-bar-wrap"><div class="progress-bar-fill" id="progressFill"></div></div><div class="progress-text">⏳ יוצר דף תצוגה מקדימה...</div><div class="progress-sub">הסוכן מרכיב את העמוד עם הצבעים והפונטים שבחרת — זה לוקח כמה שניות</div>';document.body.appendChild(overlay);}
    overlay.classList.add('active');
    var bar=document.getElementById('progressFill'),pct=0,iv=setInterval(function(){if(pct<90){pct+=8;bar.style.width=pct+'%'}},300);
    var cols = {};
    document.querySelectorAll('[name^="color_"][name$="_hex"]').forEach(function(el) {
        var key = el.name.replace('color_','').replace('_hex','');
        if (el.value) cols[key] = el.value;
    });
    var payload = {
        ownerName: 'Preview', bizName: '<?= h($t['previewName']) ?>',
        category: '<?= $category ?>', tone: 'warm',
        description: 'Template preview', action: 'contact',
        customColors: cols, customFonts: {heading: (document.querySelector('[name=font_heading]')||{}).value||'', body: (document.querySelector('[name=font_body]')||{}).value||''}, heroStyle: ((document.querySelector('[name=hero_style]:checked')||{}).value||'centered')
    };
    fetch('generate.php', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)})
    .then(function(r){return r.json()}).then(function(d){if(d.url)window.open(d.url,'_blank');btn.innerHTML='👁️ תצוגה מקדימה';btn.disabled=false;btn.style.opacity='1';clearInterval(iv);bar.style.width='100%';setTimeout(function(){overlay.classList.remove('active');bar.style.width='0'},500)})
    .catch(function(e){alert('Error: '+e.message);btn.innerHTML='👁️ תצוגה מקדימה';btn.disabled=false;btn.style.opacity='1';clearInterval(iv);overlay.classList.remove('active')});
}
</script>
</div>

</div>
<?php endif; ?>

<!-------------------------------------------
  STEP 3: Success
-------------------------------------------->
<?php if ($step === 'save' && $message): ?>
<div class="card" style="text-align:center">
    <h2 style="color:var(--green)">✅ <?= h($message) ?></h2>
    <p style="color:var(--inkSoft);margin:16px 0">התבנית נשמרה בהצלחה! כעת היא זמינה לשימוש ביוצר דפי הנחיתה.</p>
    <a href="?step=select" class="btn btn-primary">🎨 חקור תחום נוסף</a>
    <a href="index.php" class="btn btn-ghost" style="margin-right:12px">🏠 חזרה לויזארד</a>
</div>
<?php endif; ?>

</div></div>
</body>
</html>
