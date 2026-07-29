<?php
header('Content-Type: application/json; charset=utf-8');
$tones      = require __DIR__ . '/config/tones.php';
$categories = require __DIR__ . '/config/categories.php';
require __DIR__ . '/config/unsplash.php';
require_once __DIR__ . '/knowledge-base/TemplateEngine.php';

$kb = new TemplateEngine();
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) { echo json_encode(['success'=>false,'error'=>'Invalid input'],JSON_UNESCAPED_UNICODE); exit; }

$ownerName   = trim($data['ownerName']   ?? '');
$bizName     = trim($data['bizName']     ?? 'העסק שלי');
$categoryKey = trim($data['category']    ?? 'general'); $catAliases = ['gym'=>'fitness','cafe'=>'restaurant','spa'=>'beauty','barber'=>'beauty','saas'=>'tech','lawyer'=>'professional','dentist'=>'professional','doctor'=>'professional','ecommerce'=>'retail','marketing'=>'professional','architecture'=>'professional','cleaning'=>'professional','moving'=>'professional']; $categoryKey = $catAliases[$categoryKey] ?? $categoryKey;
$toneKey     = trim($data['tone']        ?? 'modern');
$description = trim($data['description'] ?? '');
$actionType  = trim($data['action']      ?? 'contact');

$cat  = $categories[$categoryKey] ?? $categories['general'];
$cat['categoryKey'] = $categoryKey;
$tone = $tones[$toneKey]         ?? $tones['modern'];
// Auto-match tone to industry for better color fit
$toneMap = ['restaurant'=>'warm','professional'=>'minimal','tech'=>'modern','fitness'=>'bold','retail'=>'bold','realestate'=>'luxury','beauty'=>'luxury','education'=>'warm','construction'=>'bold','auto'=>'modern','gym'=>'bold'];
if (isset($toneMap[$categoryKey])) {
    $tone = $tones[$toneMap[$categoryKey]] ?? $tone;
}
// Template Studio preview: override colors from custom palette
if (!empty($data['customColors']) && is_array($data['customColors'])) {
    foreach ($data['customColors'] as $k => $v) {
        $tone['colors'][$k] = $v;
    }
    // Rebuild gradient if primary changed
    if (!empty($tone['colors']['primary']) && !empty($tone['colors']['secondary'])) {
        $tone['colors']['gradient'] = 'linear-gradient(135deg,'.$tone['colors']['primary'].' 0%,'.$tone['colors']['primaryDark'].' 40%,'.$tone['colors']['secondary'].' 100%)';
    }
}
$kbIndustry = $kb->resolveCategory($categoryKey);
$kbSpec     = $kb->build($kbIndustry);
$cat['sectionOrder'] = $kbSpec['sectionOrder'];

$slug    = slugify($bizName);
$outDir  = __DIR__ . '/output/' . $slug;
$outFile = $outDir . '/index.html';
$url     = 'output/' . $slug . '/index.html';
$c = $tone['colors'];
$actionLabels = ['call'=>'התקשר עכשיו','book'=>'קבע פגישה','order'=>'הזמן עכשיו','contact'=>'צור קשר'];
$actionLabel  = $actionLabels[$actionType] ?? 'צור קשר';
$actionHref   = ($actionType === 'call') ? 'tel:'.($cat['contact']['phone']??'') : '#contact';

$html = buildPage($cat, $tone, $bizName, $description, $actionLabel, $actionHref, $ownerName);
if (!is_dir($outDir)) mkdir($outDir, 0755, true);
file_put_contents($outFile, $html);
echo json_encode(['success'=>true,'url'=>$url,'slug'=>$slug], JSON_UNESCAPED_UNICODE);

function buildPage($cat,$tone,$bizName,$desc,$actionLabel,$actionHref,$ownerName){
    $c=$tone['colors'];$f=$tone['fonts'];$rad=$tone['borderRadius'];
    ob_start();
    echo '<!DOCTYPE html><html lang="he" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>',h($bizName),'</title>';
    if(str_contains($f['heading'],'Playfair'))echo'<link href="https://fonts.googleapis.com/css2?family=Assistant:wght@400;600;700;800&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">';
    elseif(str_contains($f['heading'],'Rubik'))echo'<link href="https://fonts.googleapis.com/css2?family=Assistant:wght@400;600;700;800&family=Rubik:wght@400;600;700&display=swap" rel="stylesheet">';
    else echo'<link href="https://fonts.googleapis.com/css2?family=Assistant:wght@400;600;700;800&display=swap" rel="stylesheet">';
    
    echo '<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:',$f['body'],';color:',$c['text'],';background:',$c['bg'],';line-height:1.7}
.container{max-width:1100px;margin:0 auto;padding:0 24px}
h1,h2,h3{font-family:',$f['heading'],';line-height:1.2}
.btn{display:inline-flex;align-items:center;gap:8px;padding:15px 36px;border-radius:',$rad,';text-decoration:none;font-weight:700;font-size:1rem;transition:all .3s ease;font-family:inherit;cursor:pointer;border:none}
.btn-primary{background:',$c['primary'],';color:#fff;box-shadow:0 4px 14px ',$c['primary'],'40}.btn-primary:hover{transform:translateY(-2px);box-shadow:0 8px 25px ',$c['primary'],'50}
.btn-outline{background:transparent;color:',$c['primary'],';border:2px solid ',$c['primary'],'}.btn-outline:hover{background:',$c['primary'],';color:#fff;transform:translateY(-2px)}
.section{padding:100px 0}@media(max-width:768px){.section{padding:60px 0}}
.section-title{font-size:clamp(1.8rem,4vw,2.6rem);font-weight:800;margin-bottom:14px;text-align:center;letter-spacing:-.01em}
.section-sub{color:',$c['textSoft'],';text-align:center;max-width:600px;margin:0 auto 56px;font-size:1.1rem;line-height:1.7}
.badge{display:inline-block;padding:6px 16px;border-radius:100px;font-size:.78rem;font-weight:700;background:',$c['primary'],'15;color:',$c['primary'],';margin-bottom:16px}
.card{background:',$c['surface'],';border-radius:',$rad,';overflow:hidden;transition:all .35s ease;box-shadow:',$tone['shadow'],'}.card:hover{transform:translateY(-6px);box-shadow:0 20px 50px rgba(0,0,0,.12)}
.card-img{width:100%;height:200px;object-fit:cover}.card-body{padding:24px}
@keyframes fadeUp{from{opacity:0;transform:translateY(40px)}to{opacity:1;transform:translateY(0)}}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
.reveal{opacity:0;transform:translateY(40px);transition:opacity .7s ease,transform .7s ease}.reveal.visible{opacity:1;transform:translateY(0)}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:30px;align-items:center}@media(max-width:768px){.grid2{grid-template-columns:1fr}}
.grid3{display:grid;grid-template-columns:repeat(3,1fr);gap:24px}@media(max-width:768px){.grid3{grid-template-columns:1fr}}
.grid4{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:20px}
.stat-num{font-size:2.8rem;font-weight:800;color:',$c['primary'],';line-height:1}.stat-lbl{color:',$c['textSoft'],';font-size:.9rem;margin-top:4px}
.hero{position:relative;min-height:85vh;display:flex;align-items:center;overflow:hidden}
.hero-bg{position:absolute;inset:0;z-index:0}.hero-bg img{width:100%;height:100%;object-fit:cover}
.hero-overlay{position:absolute;inset:0;z-index:1}
.hero-content{position:relative;z-index:2;text-align:center;max-width:800px;margin:0 auto;padding:120px 24px 80px}
.hero-content h1{font-size:clamp(2.2rem,6vw,4rem);font-weight:900;line-height:1.1;margin-bottom:20px;animation:fadeUp .8s ease both}
.hero-content p{font-size:1.2rem;opacity:.9;max-width:600px;margin:0 auto 32px;animation:fadeUp .8s ease .15s both}
.hero-content .btn{animation:fadeUp .8s ease .3s both}
.hero-shape{position:absolute;bottom:-2px;left:0;right:0;z-index:3}
@media(max-width:768px){.hero{min-height:70vh}.hero-content{padding:100px 20px 60px}}
@media(max-width:768px){.nav-links-desktop,.nav-cta-desktop{display:none!important}.nav-hamburger{display:block!important}#mobileNav.open{display:flex!important}}
@media(max-width:768px){.contact-grid{grid-template-columns:1fr!important;gap:24px!important}}
</style></head><body>';

    // Nav
    $secLabels = ['menu'=>'תפריט','gallery'=>'גלריה','team'=>'הצוות','specialties'=>'התמחויות','cases'=>'הצלחות','howItWorks'=>'איך זה עובד','integrations'=>'אינטגרציות','pricing'=>'מחירון','schedule'=>'שיעורים','trainers'=>'מאמנים','plans'=>'מסלולים','products'=>'קטלוג','locations'=>'סניפים','delivery'=>'משלוחים','properties'=>'נכסים','neighborhoods'=>'שכונות','agent'=>'הסוכן','treatments'=>'טיפולים','beforeAfter'=>'לפני/אחרי','courses'=>'קורסים','instructors'=>'מרצים','curriculum'=>'תכנית','enroll'=>'הרשמה'];
    $navLinks = '<a href="#hero" style="color:inherit;text-decoration:none">בית</a>';
    $navLinks .= '<a href="#features" style="color:inherit;text-decoration:none">שירותים</a>';
    foreach(($cat['customSections']??[]) as $sec){
        $anchor = $sec['type']; $label = $secLabels[$anchor] ?? ($sec['title']??'');
        $navLinks .= '<a href="#'.$anchor.'" style="color:inherit;text-decoration:none">'.$label.'</a>';
    }
    $navLinks .= '<a href="#about" style="color:inherit;text-decoration:none">אודות</a>';
    $navLinks .= '<a href="#contact" style="color:inherit;text-decoration:none">צור קשר</a>';
    
    echo '<nav style="position:fixed;top:0;right:0;left:0;z-index:100;display:flex;align-items:center;padding:10px 20px;background:rgba(255,255,255,.92);backdrop-filter:blur(12px);border-bottom:1px solid #e9edf2">';
    echo '<div style="font-weight:800;font-size:1.1rem;color:',$c['primary'],'">',h($bizName),'</div>';
    echo '<div class="nav-links-desktop" style="display:flex;gap:16px;font-size:.84rem;font-weight:500;color:',$c['textSoft'],';margin:0 20px">',$navLinks,'</div>';
    echo '<a href="#contact" class="nav-cta-desktop" style="margin-right:auto;background:',$c['primary'],';color:#fff;padding:7px 16px;border-radius:100px;text-decoration:none;font-size:.8rem;font-weight:600;white-space:nowrap">צור קשר</a>';
    echo '<button class="nav-hamburger" onclick="var m=document.getElementById(\'mobileNav\');m.classList.toggle(\'open\')" style="display:none;margin-right:auto;background:none;border:none;font-size:1.5rem;cursor:pointer;color:',$c['text'],';padding:4px">☰</button>';
    echo '</nav>';
    echo '<div id="mobileNav" style="display:none;position:fixed;top:48px;right:0;left:0;bottom:0;z-index:99;background:rgba(255,255,255,.98);padding:20px;flex-direction:column;gap:14px;font-size:1rem;font-weight:600;overflow-y:auto">',$navLinks,'</div>';

    // Hero
    $title=$cat['hero']['title'];$subtitle=$cat['hero']['subtitle'];
    $query=unsplashQuery($cat['categoryKey']??'general');
    $heroImg=fetchUnsplashImages($query,1);
    $imgUrl = !empty($heroImg) ? $heroImg[0]['url'] : '';
    echo '<section class="hero">';
    if($imgUrl)echo'<div class="hero-bg"><img src="',$imgUrl,'" alt=""></div>';
    echo '<div class="hero-overlay" style="background:',$c['gradient'],';opacity:',($imgUrl?'.55':'1'),'"></div>';
    echo '<div class="hero-content">';
    echo '<div class="badge" style="background:rgba(255,255,255,.15);color:#fff;margin-bottom:20px">',$cat['label'],'</div>';
    echo '<h1 style="color:#fff">',h($title),'</h1>';
    echo '<p style="color:rgba(255,255,255,.85)">',h($subtitle),'</p>';
    if($desc)echo'<p style="color:rgba(255,255,255,.7);font-size:1rem;max-width:500px;margin:-16px auto 32px">',h($desc),'</p>';
    echo '<div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">';
    echo '<a href="',$actionHref,'" class="btn" style="background:#fff;color:',$c['primaryDark'],';font-size:1.1rem;padding:16px 40px">',$actionLabel,'</a>';
    echo '<a href="#contact" class="btn btn-outline" style="border-color:rgba(255,255,255,.4);color:#fff">צור קשר</a>';
    echo '</div></div>';
    echo '<svg class="hero-shape" viewBox="0 0 1440 120" fill="',$c['bg'],'"><path d="M0 120V60C240 0 480 15 720 25s480 15 720-10v65H0z"/></svg>';
    echo '</section>';

    // Features
    if(!empty($cat['features'])){
        echo '<section id="features" class="section"><div class="container">';
        echo '<h2 class="section-title">השירותים שלנו</h2><p class="section-sub">כל מה שאתה צריך — במקום אחד</p>';
        echo '<div class="grid3">';
        foreach($cat['features'] as $it){
            echo '<div style="background:',$c['surface'],';border:1px solid #e9edf2;border-radius:',$rad,';padding:32px 24px;text-align:center;box-shadow:',$tone['shadow'],'">';
            echo '<div style="width:60px;height:60px;border-radius:14px;background:',$c['primary'],'15;display:flex;align-items:center;justify-content:center;font-size:1.6rem;margin:0 auto 16px">',$it['icon'],'</div>';
            echo '<h3 style="font-size:1.1rem;font-weight:700;margin-bottom:8px">',h($it['title']),'</h3>';
            echo '<p style="color:',$c['textSoft'],';font-size:.9rem;line-height:1.6">',h($it['body']),'</p>';
            echo '</div>';
        }
        echo '</div></div></section>';
    }

    // About
    if(!empty($cat['about'])){
        echo '<section id="about" class="section" style="background:',$c['surface'],'"><div class="container">';
        echo '<h2 class="section-title">קצת עלינו</h2>';
        echo '<p style="color:',$c['textSoft'],';font-size:1.05rem;max-width:700px;margin:0 auto;line-height:1.8;text-align:center">',h($cat['about']),'</p>';
        echo '</div></section>';
    }

    // Stats
    if(!empty($cat['stats'])){
        echo '<section class="section" style="background:',$c['primary'],';color:#fff"><div class="container">';
        echo '<div class="grid4" style="text-align:center">';
        foreach($cat['stats'] as $st){
            echo '<div class="reveal"><div class="stat-num" style="color:#fff">',$st['value'],'</div><div class="stat-lbl" style="color:rgba(255,255,255,.7)">',$st['label'],'</div></div>';
        }
        echo '</div></div></section>';
    }

    // Custom sections (menu, gallery, etc.)
    if(!empty($cat['customSections'])){
        foreach($cat['customSections'] as $sec){
            $type = $sec['type'];
            if($type==='menu'){
                $menuItems = $sec['items'] ?? $cat['menu_data'] ?? [];
                if(!empty($menuItems)){
                echo '<section id="menu" class="section"><div class="container">';
                echo '<h2 class="section-title">',h($sec['title']),'</h2><p class="section-sub">המנות האהובות שלנו</p>';
                echo '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px">';
                foreach($menuItems as $item){
                    echo '<div style="display:flex;justify-content:space-between;align-items:center;background:',$c['surface'],';border:1px solid #e9edf2;border-radius:',$rad,';padding:18px 20px;box-shadow:',$tone['shadow'],'">';
                    echo '<div><strong>',h($item['name']),'</strong><p style="color:',$c['textSoft'],';font-size:.82rem">',h($item['desc']),'</p></div>';
                    echo '<strong style="color:',$c['primary'],';white-space:nowrap;margin-right:12px">',h($item['price']),'</strong></div>';
                }
                echo '</div></div></section>';
                }
            }
            if($type==='gallery'){
                $gQuery = unsplashQuery($cat['categoryKey']??'general');
                $gImages = fetchUnsplashImages($gQuery, 6);
                // Category-specific overlay labels
                $gLabels = [
                    'restaurant' => ['המנות שלנו','המטבח','אווירה','הבר','קינוחים','חומרי גלם טריים'],
                    'cafe'       => ['קפה הבוקר','מאפים טריים','פינת ישיבה','התפריט','קינוחים','הבריסטה'],
                    'gym'        => ['חדר כושר','סטודיו','משקולות','קרדיו','מתיחות','אזור פונקציונלי'],
                    'construction'=> ['לפני השיפוץ','תוך כדי עבודה','התוצאה','פרטי גמר','חזית','פנים'],
                    'beauty-salon'=> ['טיפול פנים','מניקור','אווירה','תחנת עבודה','מוצרים','קבלה'],
                    'real-estate'=> ['סלון','מטבח','חדרי שינה','נוף','חזית','חצר'],
                ];
                $catKey = $cat['categoryKey'] ?? $cat['label'] ?? 'general';
                $labels = $gLabels[$catKey] ?? ['שירות מקצועי','איכות','צוות','תוצאה','פרטים','אווירה'];
                echo '<section id="gallery" class="section"><div class="container">';
                echo '<h2 class="section-title">',$sec['title'],'</h2><p class="section-sub">התרשמו מהעבודות שלנו</p>';
                echo '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px">';
                foreach($gImages as $i=>$gi){
                    $label = $labels[$i % count($labels)];
                    echo '<div class="card" style="aspect-ratio:4/3;background:url(',$gi['url'],') center/cover;position:relative;overflow:hidden">';
                    echo '<div style="position:absolute;bottom:0;left:0;right:0;background:linear-gradient(transparent,rgba(0,0,0,.7));padding:24px 12px 10px;color:#fff;font-weight:600;font-size:.9rem">',$label,'</div>';
                    echo '</div>';
                }
                echo '</div></div></section>';
            }
            if($type==='info' && !empty($cat['info_data'])){
                echo '<section id="info" class="section"><div class="container">';
                echo '<h2 class="section-title">',h($sec['title']),'</h2><p class="section-sub">כל מה שצריך לדעת לפני שמגיעים</p>';
                echo '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:16px">';
                foreach($cat['info_data'] as $info){
                    echo '<div style="display:flex;gap:14px;align-items:flex-start;background:',$c['surface'],';border:1px solid #e9edf2;border-radius:',$rad,';padding:22px 20px;box-shadow:',$tone['shadow'],'">';
                    echo '<div style="font-size:1.8rem;flex-shrink:0">',h($info['icon']),'</div>';
                    echo '<div><strong style="font-size:1rem">',h($info['title']),'</strong><p style="color:',$c['textSoft'],';font-size:.85rem;margin-top:4px;line-height:1.5">',h($info['body']),'</p></div>';
                    echo '</div>';
                }
                echo '</div></div></section>';
            }
        }
    }

    // Process / How it works
    $processSteps = [
        ['icon'=>'💬','title'=>'שיחת ייעוץ','desc'=>'נבין את הצרכים, המטרות והסגנון שלך'],
        ['icon'=>'🎨','title'=>'עיצוב ובנייה','desc'=>'נעצב אב-טיפוס ונתאים אותו בדיוק אליך'],
        ['icon'=>'🚀','title'=>'השקה מהירה','desc'=>'האתר שלך עולה לאוויר תוך ימים — מוכן להביא לקוחות'],
        ['icon'=>'📊','title'=>'ליווי וצמיחה','desc'=>'ממשיכים ללוות, לנטר ולשפר — כדי שתמשיך לגדול'],
    ];
    $pImages = fetchUnsplashImages('office meeting workspace team', 4);
    echo '<section class="section" style="background:',$c['surface'],'"><div class="container">';
    echo '<div class="badge">איך זה עובד</div>';
    echo '<h2 class="section-title">הדרך לאתר מנצח</h2><p class="section-sub">4 שלבים פשוטים — מאתגר לאתר חי ומביא לקוחות</p>';
    echo '<div class="grid4">';
    $pi = 0;
    foreach($processSteps as $ps){
        $pBg = !empty($pImages[$pi]) ? $pImages[$pi]['url'] : '';
        echo '<div class="card card-overlay reveal reveal-delay-'.($pi+1).'">';
        if($pBg)echo'<div class="card-img" style="height:220px;background:url('.$pBg.') center/cover"></div>';
        else echo'<div class="card-img" style="height:220px;background:',$c['gradient'],'"></div>';
        echo '<div class="card-body" style="text-align:center">';
        echo '<div style="font-size:2.5rem;margin-bottom:8px">',$ps['icon'],'</div>';
        echo '<h3 style="font-size:1.05rem;font-weight:700;margin-bottom:4px">',$ps['title'],'</h3>';
        echo '<p style="color:',$c['textSoft'],';font-size:.85rem">',$ps['desc'],'</p>';
        echo '</div></div>';
        $pi++;
    }
    echo '</div></div></section>';

    // Testimonials
    if(!empty($cat['testimonials'])){
        echo '<section class="section"><div class="container">';
        echo '<h2 class="section-title">מה הלקוחות אומרים</h2><p class="section-sub">אלפי לקוחות מרוצים</p>';
        echo '<div class="grid3">';
        foreach($cat['testimonials'] as $t){
            echo '<div class="card"><div class="card-body">';
            echo '<div style="color:#f59e0b;margin-bottom:8px">★★★★★</div>';
            echo '<p style="font-style:italic;margin-bottom:12px;line-height:1.7">"',h($t['quote']),'"</p>';
            echo '<strong>',h($t['name']),'</strong></div></div>';
        }
        echo '</div></div></section>';
    }

    // CTA
    if(!empty($cat['cta'])){
        $cta = $cat['cta'];
        echo '<section class="section" style="background:',$c['gradient'],';text-align:center;color:#fff"><div class="container">';
        echo '<h2 style="font-size:2rem;font-weight:800;margin-bottom:12px">',h($cta['title']),'</h2>';
        echo '<p style="opacity:.8;max-width:500px;margin:0 auto 28px">',h($cta['subtitle']),'</p>';
        echo '<a href="',$actionHref,'" class="btn" style="background:#fff;color:',$c['primaryDark'],';font-size:1.1rem;padding:16px 40px">',$actionLabel,'</a>';
        echo '</div></section>';
    }

    // Contact
    $contact = $cat['contact'] ?? ['email'=>'info@landingflow.co.il','phone'=>'052-8529448'];
    echo '<section id="contact" class="section"><div class="container">';
    echo '<h2 class="section-title">צרו קשר</h2><p class="section-sub">נשמח לשמוע מכם</p>';
    echo '<div class="grid2 contact-grid" style="max-width:800px;margin:0 auto">';
    echo '<div><div style="margin-bottom:16px"><strong>📞 טלפון:</strong><br>',h($contact['phone']),'</div><div style="margin-bottom:16px"><strong>📧 אימייל:</strong><br>',h($contact['email']),'</div><div><strong>📍 כתובת:</strong><br>ישראל</div></div>';
    echo '<form onsubmit="return false" style="display:flex;flex-direction:column;gap:14px"><input style="padding:14px;border:1px solid #d1d5db;border-radius:',$rad,';font-family:inherit;font-size:1rem" placeholder="שם מלא"><input style="padding:14px;border:1px solid #d1d5db;border-radius:',$rad,';font-family:inherit;font-size:1rem" placeholder="אימייל"><textarea style="padding:14px;border:1px solid #d1d5db;border-radius:',$rad,';font-family:inherit;height:100px" placeholder="הודעה"></textarea><button class="btn btn-primary" style="border:none;cursor:pointer">שלח הודעה</button></form>';
    echo '</div></div></section>';

    // Footer
    echo '<footer style="background:#111827;color:#fff;padding:60px 24px 30px;text-align:center"><div style="font-weight:800;font-size:1.2rem;margin-bottom:8px">',h($bizName),'</div><div style="color:rgba(255,255,255,.5);font-size:.85rem;margin-bottom:20px">הפתרון המושלם עבורך</div><div style="display:flex;justify-content:center;gap:24px;flex-wrap:wrap;font-size:.82rem;color:rgba(255,255,255,.4);margin-bottom:20px"><a href="#" style="color:inherit;text-decoration:none">בית</a><a href="#" style="color:inherit;text-decoration:none">אודות</a><a href="#" style="color:inherit;text-decoration:none">שירותים</a><a href="#" style="color:inherit;text-decoration:none">צור קשר</a></div><div style="color:rgba(255,255,255,.25);font-size:.75rem">© ',date('Y'),' ',h($bizName),'. כל הזכויות שמורות.</div></footer>';

    // WhatsApp CTA
    $waPhone = $cat['contact']['phone'] ?? '052-8529448';
    $waNum = preg_replace('/[^0-9]/', '', $waPhone);
    if (strlen($waNum) === 10 && str_starts_with($waNum, '0')) $waNum = '972' . substr($waNum, 1);
    echo '<a href="https://wa.me/',$waNum,'" target="_blank" rel="noopener" style="position:fixed;bottom:24px;left:24px;z-index:999;background:#25D366;color:#fff;padding:12px 22px;border-radius:60px;text-decoration:none;font-weight:700;font-size:.9rem;box-shadow:0 4px 20px rgba(37,211,102,.35);display:flex;align-items:center;gap:8px;transition:transform .2s;font-family:inherit" onmouseover="this.style.transform=\'scale(1.05)\'" onmouseout="this.style.transform=\'scale(1)\'">💬 רוצה כזה? צלצל עכשיו</a>';

    // Scroll reveal
    echo '<script>(function(){var r=document.querySelectorAll(".reveal");if(!r.length)return;var o=new IntersectionObserver(function(e){e.forEach(function(e){if(e.isIntersecting){e.target.classList.add("visible");o.unobserve(e.target)}})},{threshold:.15});r.forEach(function(e){o.observe(e)})})();</script>';

    echo '</body></html>';
    return ob_get_clean();
}

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
function slugify(string $s): string { $s = preg_replace('/[^\p{L}\p{N}\s-]/u', '', $s); $s = preg_replace('/\s+/', '-', trim($s)); return mb_substr($s, 0, 50) ?: 'landing-page'; }

function unsplashQuery(string $category): string {
    $queries = ['restaurant'=>'restaurant food dining','professional'=>'office lawyer professional','tech'=>'technology office workspace','fitness'=>'gym fitness workout','retail'=>'store shop retail','realestate'=>'house home real estate','beauty'=>'beauty salon spa','education'=>'school classroom education','general'=>'business office modern'];
    return $queries[$category] ?? 'business office modern';
}

function fetchUnsplashImages(string $query, int $count = 1): array {
    $key = defined('UNSPLASH_ACCESS_KEY') ? UNSPLASH_ACCESS_KEY : '5Dty3lfkHGsDpJkRRZAcE09so3hly-x1kNKI2jO2vqY';
    $url = "https://api.unsplash.com/photos/random?query=" . urlencode($query) . "&count=" . min($count, 6) . "&client_id=" . $key;
    $ctx = stream_context_create(['http' => ['timeout' => 5, 'header' => "Accept-Version: v1\r\n"]]);
    $r = @file_get_contents($url, false, $ctx);
    if (!$r) return [];
    $d = json_decode($r, true);
    if (!is_array($d)) return [];
    $images = [];
    foreach ($d as $img) {
        $images[] = ['url' => $img['urls']['regular'] ?? $img['urls']['small'] ?? '', 'credit' => $img['user']['name'] ?? 'Unsplash'];
    }
    return $images;
}
