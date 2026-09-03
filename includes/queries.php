<?php
/**
 * queries.php - Accès aux données livres/catégories (requêtes préparées).
 */

const BOOK_SELECT = "
    SELECT b.*, c.name AS category_name, c.slug AS category_slug
    FROM books b
    LEFT JOIN categories c ON c.id = b.category_id
    WHERE b.deleted_at IS NULL AND b.status = 'active'";

function book_by_slug(string $slug): ?array
{
    return Database::first(BOOK_SELECT . ' AND b.slug = ?', [$slug]);
}

function book_by_id(int $id): ?array
{
    return Database::first(BOOK_SELECT . ' AND b.id = ?', [$id]);
}

/** flag : featured | new | bestseller | sale */
function books_by_flag(string $flag, int $limit = 8): array
{
    $col = ['featured' => 'is_featured', 'new' => 'is_new', 'bestseller' => 'is_bestseller', 'sale' => 'on_sale'][$flag] ?? 'is_featured';
    return Database::all(BOOK_SELECT . " AND b.$col = 1 ORDER BY b.rating DESC, b.reviews_count DESC LIMIT " . (int) $limit);
}

function books_popular(int $limit = 8): array
{
    return Database::all(BOOK_SELECT . ' ORDER BY b.reviews_count DESC, b.rating DESC LIMIT ' . (int) $limit);
}

function books_related(int $categoryId, int $excludeId, int $limit = 4): array
{
    return Database::all(BOOK_SELECT . ' AND b.category_id = ? AND b.id <> ? ORDER BY b.rating DESC LIMIT ' . (int) $limit, [$categoryId, $excludeId]);
}

function books_by_ids(array $ids): array
{
    $ids = array_filter(array_map('intval', $ids));
    if (!$ids) { return []; }
    $rows = Database::all(BOOK_SELECT . ' AND b.id IN (' . implode(',', $ids) . ')');
    $byId = [];
    foreach ($rows as $r) { $byId[$r['id']] = $r; }
    $ordered = [];
    foreach ($ids as $id) { if (isset($byId[$id])) { $ordered[] = $byId[$id]; } }
    return $ordered;
}

/** Recherche filtrée + triée + paginée (catalogue). */
function search_books(array $args): array
{
    $where = ["b.deleted_at IS NULL", "b.status = 'active'"];
    $params = [];

    if (!empty($args['cat'])) {
        $cat = Database::first('SELECT id FROM categories WHERE slug = ?', [$args['cat']]);
        if ($cat) { $where[] = 'b.category_id = ' . (int) $cat['id']; }
    }
    if (!empty($args['filter'])) {
        $map = ['new' => 'is_new', 'sale' => 'on_sale', 'bestseller' => 'is_bestseller', 'featured' => 'is_featured'];
        if (isset($map[$args['filter']])) { $where[] = 'b.' . $map[$args['filter']] . ' = 1'; }
    }
    if (!empty($args['min'])) { $where[] = 'b.price >= ?'; $params[] = (float) $args['min']; }
    if (!empty($args['max'])) { $where[] = 'b.price <= ?'; $params[] = (float) $args['max']; }
    if (!empty($args['q'])) {
        $where[] = '(b.title LIKE ? OR b.short_desc LIKE ? OR b.author LIKE ?)';
        $like = '%' . $args['q'] . '%';
        array_push($params, $like, $like, $like);
    }

    $order = [
        'price_asc' => 'b.price ASC', 'price_desc' => 'b.price DESC',
        'new' => 'b.created_at DESC, b.id DESC', 'rating' => 'b.rating DESC, b.reviews_count DESC',
        'popular' => 'b.reviews_count DESC, b.rating DESC',
    ][$args['sort'] ?? 'popular'] ?? 'b.reviews_count DESC';

    $whereSql = implode(' AND ', $where);
    $base = "FROM books b LEFT JOIN categories c ON c.id = b.category_id WHERE $whereSql";
    $total = (int) Database::scalar("SELECT COUNT(*) $base", $params);

    $perPage = (int) ($args['perPage'] ?? 12);
    $page = max(1, (int) ($args['page'] ?? 1));
    $offset = ($page - 1) * $perPage;

    $items = Database::all("SELECT b.*, c.name AS category_name, c.slug AS category_slug $base ORDER BY $order LIMIT $perPage OFFSET $offset", $params);
    return ['items' => $items, 'total' => $total, 'page' => $page, 'perPage' => $perPage];
}

function all_categories(): array
{
    return Database::all('SELECT * FROM categories WHERE deleted_at IS NULL ORDER BY position');
}

function reviews_for(int $bookId, string $status = 'approved'): array
{
    return Database::all('SELECT * FROM reviews WHERE book_id = ? AND status = ? ORDER BY created_at DESC', [$bookId, $status]);
}
