<?php
/**
 * seed_map.php — transforme demo_data() en un plan d'insertion :
 *   [ table => ['columns' => [...], 'rows' => [[...], ...]], ... ]
 * Consommé par install.php (INSERT préparés) et build_sql.php (génération SQL).
 */
function seed_map(array $d): array
{
    $map = [];

    // settings (clé/valeur)
    $rows = [];
    foreach ($d['settings'] as $k => $v) {
        $rows[] = [$k, $v];
    }
    $map['settings'] = ['columns' => ['key', 'value'], 'rows' => $rows];

    // admins
    $map['admins'] = ['columns' => ['name', 'email', 'password', 'role'], 'rows' =>
        array_map(fn($a) => [$a['name'], $a['email'], $a['password'], $a['role']], $d['admins'])];

    // brands
    $map['brands'] = ['columns' => ['id', 'name', 'slug', 'description', 'logo'], 'rows' =>
        array_map(fn($b) => [$b['id'], $b['name'], $b['slug'], $b['description'], $b['logo']], $d['brands'])];

    // categories
    $map['categories'] = ['columns' => ['id', 'parent_id', 'name', 'slug', 'icon', 'image', 'description', 'seo_title', 'position'], 'rows' =>
        array_map(fn($c) => [$c['id'], $c['parent_id'], $c['name'], $c['slug'], $c['icon'], $c['image'], $c['description'], $c['seo_title'], $c['position']], $d['categories'])];

    // products
    $map['products'] = ['columns' => [
        'id', 'name', 'slug', 'sku', 'brand_id', 'category_id', 'short_desc', 'long_desc',
        'ingredients', 'usage_tips', 'price', 'old_price', 'stock', 'weight', 'volume',
        'rating', 'reviews_count', 'is_featured', 'is_new', 'is_bestseller', 'on_sale', 'status', 'video_url',
    ], 'rows' => array_map(fn($p) => [
        $p['id'], $p['name'], $p['slug'], $p['sku'], $p['brand_id'], $p['category_id'], $p['short_desc'], $p['long_desc'],
        $p['ingredients'], $p['usage_tips'], $p['price'], $p['old_price'], $p['stock'], $p['weight'], $p['volume'],
        $p['rating'], $p['reviews_count'], $p['is_featured'], $p['is_new'], $p['is_bestseller'], $p['on_sale'], $p['status'], $p['video_url'],
    ], $d['products'])];

    // product_images (galeries photo)
    $map['product_images'] = ['columns' => ['product_id', 'file', 'position'], 'rows' =>
        array_map(fn($i) => [$i['product_id'], $i['file'], $i['position']], $d['productImages'] ?? [])];

    // reviews
    $map['reviews'] = ['columns' => ['id', 'product_id', 'customer_id', 'author_name', 'rating', 'title', 'body', 'status', 'created_at'], 'rows' =>
        array_map(fn($r) => [$r['id'], $r['product_id'], $r['customer_id'], $r['author_name'], $r['rating'], $r['title'], $r['body'], $r['status'], $r['created_at']], $d['reviews'])];

    // customers
    $map['customers'] = ['columns' => ['id', 'first_name', 'last_name', 'email', 'phone', 'password', 'wilaya', 'loyalty_points', 'created_at'], 'rows' =>
        array_map(fn($c) => [$c['id'], $c['first_name'], $c['last_name'], $c['email'], $c['phone'], $c['password'], $c['wilaya'], $c['loyalty_points'], $c['created_at']], $d['customers'])];

    // orders
    $map['orders'] = ['columns' => ['id', 'reference', 'customer_id', 'customer_name', 'email', 'phone', 'address', 'wilaya', 'subtotal', 'shipping', 'discount', 'total', 'payment_method', 'status', 'note', 'created_at'], 'rows' =>
        array_map(fn($o) => [$o['id'], $o['reference'], $o['customer_id'], $o['customer_name'], $o['email'], $o['phone'], $o['address'], $o['wilaya'], $o['subtotal'], $o['shipping'], $o['discount'], $o['total'], $o['payment_method'], $o['status'], $o['note'], $o['created_at']], $d['orders'])];

    // order_items
    $map['order_items'] = ['columns' => ['id', 'order_id', 'product_id', 'name', 'price', 'qty'], 'rows' =>
        array_map(fn($i) => [$i['id'], $i['order_id'], $i['product_id'], $i['name'], $i['price'], $i['qty']], $d['orderItems'])];

    // coupons
    $map['coupons'] = ['columns' => ['id', 'code', 'type', 'value', 'min_amount', 'description', 'expires_at', 'usage_limit', 'used', 'active'], 'rows' =>
        array_map(fn($c) => [$c['id'], $c['code'], $c['type'], $c['value'], $c['min_amount'], $c['desc'], $c['expires_at'], $c['usage_limit'], $c['used'], $c['active']], $d['coupons'])];

    // blog_posts
    $map['blog_posts'] = ['columns' => ['id', 'title', 'slug', 'category', 'excerpt', 'body', 'image', 'author', 'tags', 'status', 'views', 'published_at'], 'rows' =>
        array_map(fn($b) => [$b['id'], $b['title'], $b['slug'], $b['category'], $b['excerpt'], $b['body'], $b['image'], $b['author'], $b['tags'], $b['status'], $b['views'], $b['published_at']], $d['blog'])];

    // testimonials
    $map['testimonials'] = ['columns' => ['id', 'name', 'role', 'avatar', 'rating', 'body', 'position'], 'rows' =>
        array_map(fn($t) => [$t['id'], $t['name'], $t['role'], $t['avatar'], $t['rating'], $t['body'], $t['position']], $d['testimonials'])];

    return $map;
}
