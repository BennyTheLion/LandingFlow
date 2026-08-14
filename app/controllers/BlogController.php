<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class BlogController extends Controller
{
    public function index(): string
    {
        $db = Database::getInstance()->getConnection();
        $posts = $db->query(
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM blog_posts p LEFT JOIN blog_categories c ON c.id = p.category_id
             WHERE p.status = 'published' AND (p.published_at IS NULL OR p.published_at <= NOW())
             ORDER BY p.published_at DESC, p.created_at DESC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        return $this->render('public/blog', [
            'pageTitle' => 'בלוג — טיפים ומדריכים לבעלי עסקים | LandingFlow',
            'pageDescription' => 'מדריכים מעשיים לבעלי עסקים על אתרים, נגישות, תמחור ובחירת ספק טכנולוגי — כתובים בעברית פשוטה, בלי ז\'רגון.',
            'posts' => $posts,
        ]);
    }

    public function show(string $slug): string
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM blog_posts p LEFT JOIN blog_categories c ON c.id = p.category_id
             WHERE p.slug = ? AND p.status = 'published' LIMIT 1"
        );
        $stmt->execute([$slug]);
        $post = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$post) throw new \App\Core\Exceptions\NotFoundException();

        return $this->render('public/blog-post', [
            'pageTitle' => $post['meta_title'] ?: ($post['title'] . ' — LandingFlow'),
            'pageDescription' => $post['meta_description'] ?: $post['excerpt'],
            'post' => $post,
        ]);
    }
}
