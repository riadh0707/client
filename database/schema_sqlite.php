<?php
/**
 * schema_sqlite.php — équivalent SQLite du schéma (pour la démo locale).
 * Retourne la liste des instructions CREATE TABLE.
 */
function sqlite_schema(): array
{
    return [
        "CREATE TABLE settings (key TEXT PRIMARY KEY, value TEXT)",
        "CREATE TABLE admins (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT UNIQUE, password TEXT, role TEXT DEFAULT 'admin', created_at TEXT DEFAULT CURRENT_TIMESTAMP)",
        "CREATE TABLE brands (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, slug TEXT UNIQUE, description TEXT, logo TEXT, deleted_at TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP)",
        "CREATE TABLE categories (id INTEGER PRIMARY KEY AUTOINCREMENT, parent_id INTEGER, name TEXT, slug TEXT UNIQUE, icon TEXT, description TEXT, image TEXT, seo_title TEXT, seo_desc TEXT, position INTEGER DEFAULT 0, deleted_at TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP)",
        "CREATE TABLE products (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, slug TEXT UNIQUE, sku TEXT UNIQUE, brand_id INTEGER, category_id INTEGER, short_desc TEXT, long_desc TEXT, ingredients TEXT, usage_tips TEXT, price REAL DEFAULT 0, old_price REAL, stock INTEGER DEFAULT 0, weight INTEGER, volume TEXT, rating REAL DEFAULT 0, reviews_count INTEGER DEFAULT 0, is_featured INTEGER DEFAULT 0, is_new INTEGER DEFAULT 0, is_bestseller INTEGER DEFAULT 0, on_sale INTEGER DEFAULT 0, status TEXT DEFAULT 'active', video_url TEXT, deleted_at TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP)",
        "CREATE TABLE product_images (id INTEGER PRIMARY KEY AUTOINCREMENT, product_id INTEGER, file TEXT, position INTEGER DEFAULT 0)",
        "CREATE TABLE customers (id INTEGER PRIMARY KEY AUTOINCREMENT, first_name TEXT, last_name TEXT, email TEXT UNIQUE, phone TEXT, password TEXT, wilaya TEXT, loyalty_points INTEGER DEFAULT 0, deleted_at TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP)",
        "CREATE TABLE addresses (id INTEGER PRIMARY KEY AUTOINCREMENT, customer_id INTEGER, label TEXT, line1 TEXT, city TEXT, wilaya TEXT, phone TEXT, is_default INTEGER DEFAULT 0, created_at TEXT DEFAULT CURRENT_TIMESTAMP)",
        "CREATE TABLE reviews (id INTEGER PRIMARY KEY AUTOINCREMENT, product_id INTEGER, customer_id INTEGER, author_name TEXT, rating INTEGER DEFAULT 5, title TEXT, body TEXT, status TEXT DEFAULT 'pending', created_at TEXT DEFAULT CURRENT_TIMESTAMP)",
        "CREATE TABLE orders (id INTEGER PRIMARY KEY AUTOINCREMENT, reference TEXT UNIQUE, customer_id INTEGER, customer_name TEXT, email TEXT, phone TEXT, address TEXT, wilaya TEXT, subtotal REAL DEFAULT 0, shipping REAL DEFAULT 0, discount REAL DEFAULT 0, total REAL DEFAULT 0, payment_method TEXT DEFAULT 'cod', status TEXT DEFAULT 'pending', note TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP)",
        "CREATE TABLE order_items (id INTEGER PRIMARY KEY AUTOINCREMENT, order_id INTEGER, product_id INTEGER, name TEXT, price REAL DEFAULT 0, qty INTEGER DEFAULT 1)",
        "CREATE TABLE coupons (id INTEGER PRIMARY KEY AUTOINCREMENT, code TEXT UNIQUE, type TEXT DEFAULT 'percent', value REAL DEFAULT 0, min_amount REAL DEFAULT 0, description TEXT, expires_at TEXT, usage_limit INTEGER DEFAULT 0, used INTEGER DEFAULT 0, active INTEGER DEFAULT 1, created_at TEXT DEFAULT CURRENT_TIMESTAMP)",
        "CREATE TABLE blog_posts (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, slug TEXT UNIQUE, category TEXT, excerpt TEXT, body TEXT, image TEXT, author TEXT, tags TEXT, seo_title TEXT, seo_desc TEXT, status TEXT DEFAULT 'published', views INTEGER DEFAULT 0, published_at TEXT, deleted_at TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP)",
        "CREATE TABLE testimonials (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, role TEXT, avatar TEXT, rating INTEGER DEFAULT 5, body TEXT, position INTEGER DEFAULT 0)",
        "CREATE TABLE wishlists (id INTEGER PRIMARY KEY AUTOINCREMENT, customer_id INTEGER, product_id INTEGER, created_at TEXT DEFAULT CURRENT_TIMESTAMP)",
        "CREATE TABLE newsletter (id INTEGER PRIMARY KEY AUTOINCREMENT, email TEXT UNIQUE, created_at TEXT DEFAULT CURRENT_TIMESTAMP)",
    ];
}
