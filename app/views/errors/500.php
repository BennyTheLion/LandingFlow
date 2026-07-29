<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 — שגיאת שרת</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, sans-serif; background: #0f172a; color: #e2e8f0; display: flex; align-items: center; justify-content: center; min-height: 100vh; text-align: center; padding: 2rem; }
        h1 { font-size: 5rem; color: #ef4444; margin-bottom: 0.5rem; }
        h2 { font-size: 1.5rem; margin-bottom: 1rem; }
        p { color: #94a3b8; max-width: 500px; margin: 0 auto 2rem; }
        a { color: #38bdf8; }
        .message { background: #1e293b; border: 1px solid #334155; border-radius: 0.5rem; padding: 1rem; margin: 1rem auto; max-width: 600px; text-align: left; direction: ltr; font-family: monospace; font-size: 0.85rem; color: #94a3b8; overflow-x: auto; }
    </style>
</head>
<body>
    <div>
        <h1>500</h1>
        <h2>שגיאת שרת פנימית</h2>
        <p>משהו השתבש. אנא נסה שוב מאוחר יותר.</p>
        <?php if (defined('APP_DEBUG') && APP_DEBUG && isset($message)): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <p><a href="<?= defined('APP_URL') ? APP_URL : '/' ?>">חזרה לדף הבית</a></p>
    </div>
</body>
</html>
