<?php
/**
 * auth.php — authentification clients & administrateurs.
 * Mots de passe hachés (password_hash), sessions séparées client/admin.
 */

/* ----------------------------- Clients ---------------------------------- */

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    static $cache = null;
    if ($cache === null) {
        $cache = Database::first(
            'SELECT id, first_name, last_name, email, phone, loyalty_points, created_at
             FROM customers WHERE id = ? AND deleted_at IS NULL',
            [$_SESSION['user_id']]
        );
    }
    return $cache ?: null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '';
        flash('Veuillez vous connecter pour continuer.', 'info');
        redirect('customer/login.php');
    }
}

function login_user(array $customer): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $customer['id'];
}

function logout_user(): void
{
    unset($_SESSION['user_id']);
}

/* ----------------------------- Admins ----------------------------------- */

function current_admin(): ?array
{
    if (empty($_SESSION['admin_id'])) {
        return null;
    }
    static $cache = null;
    if ($cache === null) {
        $cache = Database::first(
            'SELECT id, name, email, role FROM admins WHERE id = ?',
            [$_SESSION['admin_id']]
        );
    }
    return $cache ?: null;
}

function require_admin(): void
{
    if (current_admin() === null) {
        redirect('admin/login.php');
    }
}

function login_admin(array $admin): void
{
    session_regenerate_id(true);
    $_SESSION['admin_id'] = $admin['id'];
}

function logout_admin(): void
{
    unset($_SESSION['admin_id']);
}
