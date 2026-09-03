<?php
/** schema_sqlite.php — équivalent SQLite (démo locale). */
function sqlite_schema(): array
{
    return [
        "CREATE TABLE settings (key TEXT PRIMARY KEY, value TEXT)",
        "CREATE TABLE admins (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT UNIQUE, password TEXT, role TEXT DEFAULT 'admin', created_at TEXT DEFAULT CURRENT_TIMESTAMP)",
        "CREATE TABLE categories (id INTEGER PRIMARY KEY AUTOINCREMENT, parent_id INTEGER, name TEXT, slug TEXT UNIQUE, icon TEXT, description TEXT, image TEXT, seo_title TEXT, position INTEGER DEFAULT 0, deleted_at TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP)",
        "CREATE TABLE books (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, slug TEXT UNIQUE, author TEXT, sku TEXT, category_id INTEGER, short_desc TEXT, long_desc TEXT, toc TEXT, price REAL DEFAULT 0, old_price REAL, on_sale INTEGER DEFAULT 0, pages_count INTEGER DEFAULT 0, preview_pages INTEGER DEFAULT 10, language TEXT, file_type TEXT DEFAULT 'pdf', pdf_file TEXT, preview_file TEXT, cover_image TEXT, is_featured INTEGER DEFAULT 0, is_new INTEGER DEFAULT 0, is_bestseller INTEGER DEFAULT 0, rating REAL DEFAULT 0, reviews_count INTEGER DEFAULT 0, status TEXT DEFAULT 'active', deleted_at TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP)",
        "CREATE TABLE customers (id INTEGER PRIMARY KEY AUTOINCREMENT, first_name TEXT, last_name TEXT, email TEXT UNIQUE, phone TEXT, password TEXT, wilaya TEXT, loyalty_points INTEGER DEFAULT 0, deleted_at TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP)",
        "CREATE TABLE reviews (id INTEGER PRIMARY KEY AUTOINCREMENT, book_id INTEGER, customer_id INTEGER, author_name TEXT, rating INTEGER DEFAULT 5, title TEXT, body TEXT, status TEXT DEFAULT 'pending', created_at TEXT DEFAULT CURRENT_TIMESTAMP)",
        "CREATE TABLE orders (id INTEGER PRIMARY KEY AUTOINCREMENT, reference TEXT UNIQUE, customer_id INTEGER, customer_name TEXT, email TEXT, phone TEXT, subtotal REAL DEFAULT 0, discount REAL DEFAULT 0, total REAL DEFAULT 0, payment_method TEXT DEFAULT 'baridimob', payment_ref TEXT, receipt_file TEXT, status TEXT DEFAULT 'pending', note TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP)",
        "CREATE TABLE order_items (id INTEGER PRIMARY KEY AUTOINCREMENT, order_id INTEGER, book_id INTEGER, title TEXT, price REAL DEFAULT 0)",
        "CREATE TABLE book_access (id INTEGER PRIMARY KEY AUTOINCREMENT, customer_id INTEGER, book_id INTEGER, order_id INTEGER, created_at TEXT DEFAULT CURRENT_TIMESTAMP, UNIQUE(customer_id, book_id))",
        "CREATE TABLE coupons (id INTEGER PRIMARY KEY AUTOINCREMENT, code TEXT UNIQUE, type TEXT DEFAULT 'percent', value REAL DEFAULT 0, min_amount REAL DEFAULT 0, description TEXT, expires_at TEXT, usage_limit INTEGER DEFAULT 0, used INTEGER DEFAULT 0, active INTEGER DEFAULT 1, created_at TEXT DEFAULT CURRENT_TIMESTAMP)",
        "CREATE TABLE blog_posts (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, slug TEXT UNIQUE, category TEXT, excerpt TEXT, body TEXT, image TEXT, author TEXT, tags TEXT, status TEXT DEFAULT 'published', views INTEGER DEFAULT 0, published_at TEXT, deleted_at TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP)",
        "CREATE TABLE testimonials (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, role TEXT, avatar TEXT, rating INTEGER DEFAULT 5, body TEXT, position INTEGER DEFAULT 0)",
        "CREATE TABLE wishlists (id INTEGER PRIMARY KEY AUTOINCREMENT, customer_id INTEGER, book_id INTEGER, created_at TEXT DEFAULT CURRENT_TIMESTAMP, UNIQUE(customer_id, book_id))",
        "CREATE TABLE newsletter (id INTEGER PRIMARY KEY AUTOINCREMENT, email TEXT UNIQUE, created_at TEXT DEFAULT CURRENT_TIMESTAMP)",
    ];
}
