<?php
/**
 * queries.php — Accès aux données produits/catégories (requêtes préparées).
 * Toutes les lectures produits joignent la marque + l'image principale.
 */

const PRODUCT_SELECT = "
    SELECT p.*, b.name AS brand_name, b.slug AS brand_slug,
           c.name AS category_name, c.slug AS category_slug,
           (SELECT file FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.position LIMIT 1) AS primary_image,
           (SELECT file FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.position LIMIT 1 OFFSET 1) AS second_image
    FROM products p
    LEFT JOIN brands b ON b.id = p.brand_id
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE p.deleted_at IS NULL AND p.status = 'active'";

function product_by_slug(string $slug): ?array
{
    return Database::first(PRODUCT_SELECT . ' AND p.slug = ?', [$slug]);
}

function product_by_id(int $id): ?array
{
    return Database::first(PRODUCT_SELECT . ' AND p.id = ?', [$id]);
}

/** Produits par flag : featured | new | bestseller | sale. */
function products_by_flag(string $flag, int $limit = 8): array
{
    $col = [
        'featured'   => 'is_featured',
        'new'        => 'is_new',
        'bestseller' => 'is_bestseller',
        'sale'       => 'on_sale',
    ][$flag] ?? 'is_featured';
    return Database::all(PRODUCT_SELECT . " AND p.$col = 1 ORDER BY p.rating DESC, p.reviews_count DESC LIMIT " . (int) $limit);
}

/** Produits populaires (par note + nb d'avis). */
function products_popular(int $limit = 8): array
{
    return Database::all(PRODUCT_SELECT . ' ORDER BY p.reviews_count DESC, p.rating DESC LIMIT ' . (int) $limit);
}

/** Produits similaires (même catégorie, hors produit courant). */
function products_related(int $categoryId, int $excludeId, int $limit = 4): array
{
    return Database::all(
        PRODUCT_SELECT . ' AND p.category_id = ? AND p.id <> ? ORDER BY p.rating DESC LIMIT ' . (int) $limit,
        [$categoryId, $excludeId]
    );
}

function products_by_ids(array $ids): array
{
    $ids = array_filter(array_map('intval', $ids));
    if (!$ids) { return []; }
    $in = implode(',', $ids);
    $rows = Database::all(PRODUCT_SELECT . " AND p.id IN ($in)");
    // Conserver l'ordre fourni.
    $byId = [];
    foreach ($rows as $r) { $byId[$r['id']] = $r; }
    $ordered = [];
    foreach ($ids as $id) { if (isset($byId[$id])) { $ordered[] = $byId[$id]; } }
    return $ordered;
}

/**
 * Recherche filtrée + triée + paginée pour la page boutique.
 * $args : cat, brand, filter, min, max, avail, q, sort, page, perPage
 * Retourne ['items'=>[], 'total'=>int, 'page'=>..].
 */
function search_products(array $args): array
{
    $where  = ["p.deleted_at IS NULL", "p.status = 'active'"];
    $params = [];

    if (!empty($args['cat'])) {
        // Inclut les sous-catégories du parent.
        $cat = Database::first('SELECT id FROM categories WHERE slug = ?', [$args['cat']]);
        if ($cat) {
            $childIds = Database::all('SELECT id FROM categories WHERE parent_id = ?', [$cat['id']]);
            $ids = array_merge([$cat['id']], array_column($childIds, 'id'));
            $where[] = 'p.category_id IN (' . implode(',', array_map('intval', $ids)) . ')';
        }
    }
    if (!empty($args['brand'])) {
        $where[] = 'b.slug = ?';
        $params[] = $args['brand'];
    }
    if (!empty($args['filter'])) {
        $map = ['new' => 'is_new', 'sale' => 'on_sale', 'bestseller' => 'is_bestseller', 'featured' => 'is_featured'];
        if (isset($map[$args['filter']])) { $where[] = 'p.' . $map[$args['filter']] . ' = 1'; }
    }
    if (!empty($args['min'])) { $where[] = 'p.price >= ?'; $params[] = (float) $args['min']; }
    if (!empty($args['max'])) { $where[] = 'p.price <= ?'; $params[] = (float) $args['max']; }
    if (!empty($args['avail'])) { $where[] = 'p.stock > 0'; }
    if (!empty($args['q'])) {
        $where[] = '(p.name LIKE ? OR p.short_desc LIKE ? OR b.name LIKE ?)';
        $like = '%' . $args['q'] . '%';
        array_push($params, $like, $like, $like);
    }

    $order = [
        'price_asc'  => 'p.price ASC',
        'price_desc' => 'p.price DESC',
        'new'        => 'p.created_at DESC, p.id DESC',
        'rating'     => 'p.rating DESC, p.reviews_count DESC',
        'popular'    => 'p.reviews_count DESC, p.rating DESC',
    ][$args['sort'] ?? 'popular'] ?? 'p.reviews_count DESC';

    $whereSql = implode(' AND ', $where);
    $base = "FROM products p LEFT JOIN brands b ON b.id = p.brand_id LEFT JOIN categories c ON c.id = p.category_id WHERE $whereSql";

    $total = (int) Database::scalar("SELECT COUNT(*) $base", $params);

    $perPage = (int) ($args['perPage'] ?? 12);
    $page    = max(1, (int) ($args['page'] ?? 1));
    $offset  = ($page - 1) * $perPage;

    $sql = "SELECT p.*, b.name AS brand_name, b.slug AS brand_slug, c.name AS category_name, c.slug AS category_slug,
            (SELECT file FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.position LIMIT 1) AS primary_image,
           (SELECT file FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.position LIMIT 1 OFFSET 1) AS second_image
            $base ORDER BY $order LIMIT $perPage OFFSET $offset";
    $items = Database::all($sql, $params);

    return ['items' => $items, 'total' => $total, 'page' => $page, 'perPage' => $perPage];
}

function all_categories(): array
{
    return Database::all('SELECT * FROM categories WHERE deleted_at IS NULL ORDER BY position');
}

function all_brands(): array
{
    return Database::all('SELECT * FROM brands WHERE deleted_at IS NULL ORDER BY name');
}

function reviews_for(int $productId, string $status = 'approved'): array
{
    return Database::all(
        'SELECT * FROM reviews WHERE product_id = ? AND status = ? ORDER BY created_at DESC',
        [$productId, $status]
    );
}
