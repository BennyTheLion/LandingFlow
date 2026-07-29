<?php
/**
 * LandingFlow — Shared page layout
 * Include head -> header -> $content -> footer
 *
 * Helpers are defined here so they always work,
 * even when included through nested output buffers.
 */
if (!isset($url)) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    $baseUrl = $scheme . '://' . $host . ($scriptDir === '/' ? '' : $scriptDir);
    $url = fn(string $path = '') => $baseUrl . '/' . ltrim($path, '/');
}
if (!isset($isAuthenticated)) {
    $isAuthenticated = fn() => isset($_SESSION['user']);
}
include __DIR__ . '/head.php';
include __DIR__ . '/header.php';
?>
<main><?= $content ?? '' ?></main>
<?php include __DIR__ . '/footer.php'; ?>
