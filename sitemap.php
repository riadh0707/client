<?php
require_once __DIR__ . '/includes/bootstrap.php';
header('Content-Type: application/xml; charset=utf-8');
$urls = [
  ['loc' => url('index.php'), 'priority' => '1.0'],
  ['loc' => url('catalogue.php'), 'priority' => '0.9'],
  ['loc' => url('blog.php'), 'priority' => '0.7'],
  ['loc' => url('about.php'), 'priority' => '0.6'],
  ['loc' => url('contact.php'), 'priority' => '0.6'],
  ['loc' => url('faq.php'), 'priority' => '0.5'],
];
foreach (Database::all("SELECT slug FROM categories WHERE deleted_at IS NULL") as $c) { $urls[] = ['loc' => url('catalogue.php?cat=' . $c['slug']), 'priority' => '0.8']; }
foreach (Database::all("SELECT slug FROM books WHERE deleted_at IS NULL AND status='active'") as $b) { $urls[] = ['loc' => url('livre.php?slug=' . $b['slug']), 'priority' => '0.8']; }
foreach (Database::all("SELECT slug FROM blog_posts WHERE status='published' AND deleted_at IS NULL") as $p) { $urls[] = ['loc' => url('blog-post.php?slug=' . $p['slug']), 'priority' => '0.6']; }
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) { echo "  <url><loc>" . e($u['loc']) . "</loc><priority>{$u['priority']}</priority></url>\n"; }
echo '</urlset>';
