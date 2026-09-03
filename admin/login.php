<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once INCLUDES_PATH . '/icons.php';
if (current_admin()) { redirect('admin/index.php'); }
$error = '';
if (is_post()) {
    csrf_check();
    if (!rate_limit('admin_login', 6, 60)) { $error = 'Trop de tentatives. Patientez une minute.'; }
    else { $a = Database::first('SELECT * FROM admins WHERE email = ?', [input('email')]);
        if ($a && password_verify($_POST['password'] ?? '', $a['password'])) { login_admin($a); redirect('admin/index.php'); }
        $error = 'Identifiants incorrects.'; }
}
?><!doctype html>
<html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Administration · La Bibliothèque</title>
<link rel="icon" href="<?php h(asset('images/favicon.svg')); ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php h(asset('css/admin.css')); ?>">
</head><body class="admin"><div class="a-login"><div class="a-login-card">
  <div class="a-logo" style="justify-content:center;color:var(--a-ink);padding-bottom:6px"><span class="mark" style="width:36px;height:36px;border-radius:9px;background:linear-gradient(135deg,#8a5a34,#6b4726);display:grid;place-items:center;color:#c9a959"><?= icon('library') ?></span> La Bibliothèque</div>
  <p style="text-align:center;color:var(--a-muted);margin:0 0 24px">Espace d'administration</p>
  <?php if ($error): ?><div class="a-alert" style="background:#f2d6d2;color:#a3302a"><?php h($error); ?></div><?php endif; ?>
  <form method="post"><?= csrf_field() ?>
    <div class="a-field"><label>E-mail</label><input type="email" name="email" required value="admin@bibliotheque-numerique.dz"></div>
    <div class="a-field"><label>Mot de passe</label><input type="password" name="password" required></div>
    <button class="a-btn" style="width:100%;justify-content:center;padding:13px">Se connecter</button>
  </form>
  <p style="text-align:center;color:var(--a-muted);font-size:.8rem;margin:16px 0 0">💡 admin@bibliotheque-numerique.dz / admin123</p>
</div></div></body></html>
