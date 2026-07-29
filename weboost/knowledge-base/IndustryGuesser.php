<?php
class IndustryGuesser {
    private int $bestScore = 0;
    private array $industries;
    public function __construct() {
        $this->industries = [
            "restaurant" => ["מסעדה","בית קפה","קפה","אוכל","פיצה","המבורגר","שף","מטבח","מאפה","קייטרינג","ארוח","מנה","restaurant","cafe","pizza","food","coffee","chef","kitchen","burger","bakery","catering"],
            "lawyer" => ["עורך דין","עורכת דין","משרד עורכי","משפט","חוזה","תביעה","בורר","גישור","נוטריון","lawyer","attorney","legal","law firm","contract","litigation"],
            "doctor" => ["רופא","רופאה","מרפאה","קליניקה","רפואי","בריאות","מטופל","doctor","clinic","medical","health","patient"],
            "dentist" => ["שן","שיניים","רופא שיניים","מרפאת שיניים","יישור","כתר","סתימה","dentist","dental","tooth","teeth","orthodontist","crown","filling"],
            "accounting" => ["חשבונאות","רואה חשבון","רו\"ח","מס","הנהלת חשבונות","תשלום","דוח","חשבונית","accounting","accountant","cpa","tax","bookkeeping","payroll","invoice"],
            "insurance" => ["ביטוח","פוליסה","כיסוי","החזר","תביעה","סוכן","insurance","policy","coverage","claim","agent"],
            "gym" => ["כושר","חדר כושר","אימון","מכון","ספורט","משקל","שריר","gym","fitness","training","workout","exercise","muscle","weight"],
            "personal-trainer" => ["מאמן אישי","מאמנת","אימון אישי","personal trainer","coach","pt"],
            "beauty-salon" => ["יופי","קוסמטיקה","פן","איפור","ציפורן","מניקור","פדיקור","סלון","שער","beauty","salon","cosmetics","hair","nails","makeup","manicure","pedicure"],
            "barber" => ["ספר","תספורת","זקן","גילוח","תספור","barber","haircut","beard","shave"],
            "spa" => ["ספא","מסאג","עיסוי","גקוזי","סאונה","פנים","טיפול","spa","massage","jacuzzi","sauna","facial","treatment"],
            "construction" => ["בניה","שיפוץ","קבלן","בניין","שיפצ","שיפוצ","בונה","טיח","רצף","חשמל","אינסטלטור","construction","renovation","contractor","builder","renovate","plaster","electric","plumb"],
            "electrician" => ["חשמל","חשמלאי","חיבור","מתח","לוח","קצר","electrician","electrical","wiring","voltage","panel","short"],
            "plumber" => ["אינסטלטור","צינור","ביוב","נזילה","ברז","מיםחם","plumber","plumbing","pipe","leak","faucet","drain"],
            "cleaning" => ["נקיון","מנקה","ניקוי","שטיפה","אבק","חלון","cleaning","cleaner","janitor","maid","dust","window","mop"],
            "moving" => ["הובלה","מעבר","אריזה","פריקה","העמסה","moving","relocation","packing","unloading","truck"],
            "auto-repair" => ["מוסך","רכב","מכונית","תיקון","טיפול","מנוע","גיר","צמיג","auto","car","repair","mechanic","garage","engine","tire","brake"],
            "photography" => ["צילום","צלם","מצלמה","תמונה","סטודיו","אלבום","photography","photographer","camera","photo","studio","shoot"],
            "wedding" => ["חתונה","כלה","חתן","זוג","אירוע","שמלה","wedding","bride","groom","couple","event","planner"],
            "saas" => ["תוכנה","אפליקציה","מערכת","פלטפורמה","ענן","saas","software","app","platform","cloud","api","saas"],
            "real-estate" => ["נדלן","דירה","בית","מכירה","השכרה","נכס","מתווך","שכונ","real estate","property","house","apartment","sale","rent","agent","broker","neighborhood"],
            "ecommerce" => ["חנות","מכירה","מוצרים","קניה","משלוח","הזמנה","אונליין","ecommerce","shop","store","products","online","retail","buy"],
            "online-course" => ["קורס","לימוד","שיעור","הדרכה","הכשרה","סדנה","סטודנט","למידה","course","class","training","workshop","student","learn","education","online"],
            "veterinary" => ["וטרינר","חיה","כלב","חתול","חיית","טיפול","veterinary","vet","animal","dog","cat","pet"],
            "nonprofit" => ["עמותה","תרומה","התנדבות","קהילה","צדקה","nonprofit","charity","donation","volunteer","community","ngo"],
        ];
    }
    public function guess(string $desc): string {
        $this->bestScore = 0;
        if (empty($desc)) return "";
        $best = ""; $desc = mb_strtolower($desc);
        foreach ($this->industries as $industry => $keywords) {
            $score = 0;
            foreach ($keywords as $kw) {
                if (mb_stripos($desc, $kw) !== false) { $score += mb_strlen($kw); }
            }
            if ($score > $this->bestScore) { $this->bestScore = $score; $best = $industry; }
        }
        return $best ?: "";
    }
    public function getConfidence(): int { return $this->bestScore; }
}