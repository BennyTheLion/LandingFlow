<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — הדף לא נמצא</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, sans-serif; background: #0f172a; color: #e2e8f0; display: flex; align-items: center; justify-content: center; min-height: 100vh; text-align: center; padding: 2rem; }
        h1 { font-size: 6rem; color: #38bdf8; margin-bottom: 0.5rem; }
        h2 { font-size: 1.5rem; margin-bottom: 1rem; }
        p { color: #94a3b8; max-width: 500px; margin: 0 auto 2rem; }
        a { color: #38bdf8; }
    </style>
</head>
<body>
    <div>
        <h1>404</h1>
        <h2>הדף שחיפשת לא נמצא</h2>
        <p>אולי הקישור שגוי, או שהדף הוסר. אפשר לחזור לדף הבית ולהמשיך משם.</p>
        <p><a href="<?= defined('APP_URL') ? APP_URL : '/' ?>">→ חזרה לדף הבית</a></p>
    </div>
</body>
</html>
