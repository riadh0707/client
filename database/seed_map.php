<?php
/** seed_map.php — demo_data() → plan d'insertion [table => [columns, rows]]. */
function seed_map(array $d): array
{
    $map = [];

    $rows = [];
    foreach ($d['settings'] as $k => $v) { $rows[] = [$k, $v]; }
    $map['settings'] = ['columns' => ['key', 'value'], 'rows' => $rows];

    $map['admins'] = ['columns' => ['name', 'email', 'password', 'role'],
        'rows' => array_map(fn($a) => [$a['name'], $a['email'], $a['password'], $a['role']], $d['admins'])];

    $map['categories'] = ['columns' => ['id', 'parent_id', 'name', 'slug', 'icon', 'description', 'seo_title', 'position'],
        'rows' => array_map(fn($c) => [$c['id'], $c['parent_id'], $c['name'], $c['slug'], $c['icon'], $c['description'], $c['seo_title'], $c['position']], $d['categories'])];

    $map['books'] = ['columns' => ['id', 'title', 'slug', 'author', 'sku', 'category_id', 'short_desc', 'long_desc', 'toc',
        'price', 'old_price', 'on_sale', 'pages_count', 'preview_pages', 'language', 'file_type', 'pdf_file', 'preview_file', 'cover_image',
        'is_featured', 'is_new', 'is_bestseller', 'rating', 'reviews_count', 'status'],
        'rows' => array_map(fn($b) => [$b['id'], $b['title'], $b['slug'], $b['author'], $b['sku'], $b['category_id'], $b['short_desc'], $b['long_desc'], $b['toc'],
            $b['price'], $b['old_price'], $b['on_sale'], $b['pages_count'], $b['preview_pages'], $b['language'], $b['file_type'], $b['pdf_file'], $b['preview_file'], $b['cover_image'],
            $b['is_featured'], $b['is_new'], $b['is_bestseller'], $b['rating'], $b['reviews_count'], $b['status']], $d['books'])];

    $map['reviews'] = ['columns' => ['id', 'book_id', 'customer_id', 'author_name', 'rating', 'title', 'body', 'status', 'created_at'],
        'rows' => array_map(fn($r) => [$r['id'], $r['book_id'], $r['customer_id'], $r['author_name'], $r['rating'], $r['title'], $r['body'], $r['status'], $r['created_at']], $d['reviews'])];

    $map['customers'] = ['columns' => ['id', 'first_name', 'last_name', 'email', 'phone', 'password', 'wilaya', 'loyalty_points', 'created_at'],
        'rows' => array_map(fn($c) => [$c['id'], $c['first_name'], $c['last_name'], $c['email'], $c['phone'], $c['password'], $c['wilaya'], $c['loyalty_points'], $c['created_at']], $d['customers'])];

    $map['orders'] = ['columns' => ['id', 'reference', 'customer_id', 'customer_name', 'email', 'phone', 'subtotal', 'discount', 'total', 'payment_method', 'payment_ref', 'receipt_file', 'status', 'note', 'created_at'],
        'rows' => array_map(fn($o) => [$o['id'], $o['reference'], $o['customer_id'], $o['customer_name'], $o['email'], $o['phone'], $o['subtotal'], $o['discount'], $o['total'], $o['payment_method'], $o['payment_ref'], $o['receipt_file'], $o['status'], $o['note'], $o['created_at']], $d['orders'])];

    $map['order_items'] = ['columns' => ['id', 'order_id', 'book_id', 'title', 'price'],
        'rows' => array_map(fn($i) => [$i['id'], $i['order_id'], $i['book_id'], $i['title'], $i['price']], $d['orderItems'])];

    $map['book_access'] = ['columns' => ['id', 'customer_id', 'book_id', 'order_id', 'created_at'],
        'rows' => array_map(fn($a) => [$a['id'], $a['customer_id'], $a['book_id'], $a['order_id'], $a['created_at']], $d['access'])];

    $map['coupons'] = ['columns' => ['id', 'code', 'type', 'value', 'min_amount', 'description', 'expires_at', 'usage_limit', 'used', 'active'],
        'rows' => array_map(fn($c) => [$c['id'], $c['code'], $c['type'], $c['value'], $c['min_amount'], $c['desc'], $c['expires_at'], $c['usage_limit'], $c['used'], $c['active']], $d['couponRows'])];

    $map['blog_posts'] = ['columns' => ['id', 'title', 'slug', 'category', 'excerpt', 'body', 'image', 'author', 'tags', 'status', 'views', 'published_at'],
        'rows' => array_map(fn($b) => [$b['id'], $b['title'], $b['slug'], $b['category'], $b['excerpt'], $b['body'], $b['image'], $b['author'], $b['tags'], $b['status'], $b['views'], $b['published_at']], $d['blog'])];

    $map['testimonials'] = ['columns' => ['id', 'name', 'role', 'avatar', 'rating', 'body', 'position'],
        'rows' => array_map(fn($t) => [$t['id'], $t['name'], $t['role'], $t['avatar'], $t['rating'], $t['body'], $t['position']], $d['testimonials'])];

    return $map;
}
