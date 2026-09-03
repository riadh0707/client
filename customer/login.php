<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once INCLUDES_PATH . '/icons.php';
if (is_logged_in()) { redirect('customer/library.php'); }
$error = '';
if (is_post()) {
    csrf_check();
    if (!rate_limit('login', 8, 60)) { $error = 'Trop de tentatives. Réessayez dans une minute.'; }
    else {
        $u = Database::first('SELECT * FROM customers WHERE email = ? AND deleted_at IS NULL', [input('email')]);
        if ($u && password_verify($_POST['password'] ?? '', $u['password'])) {
            login_user($u); flash('Bienvenue ' . $u['first_name'] . ' !', 'success');
            $to = $_SESSION['redirect_after_login'] ?? 'customer/library.php'; unset($_SESSION['redirect_after_login']);
            redirect(str_starts_with($to, 'http') ? 'customer/library.php' : $to);
        }
        $error = 'E-mail ou mot de passe incorrect.';
    }
}
$pageTitle = 'Connexion';
require_once INCLUDES_PATH . '/header.php';
?>
<section class="section"><div class="container" style="max-width:1000px">
  <div class="auth-wrap">
    <div class="auth-visual"><div style="position:relative;z-index:2">
      <span class="eyebrow" style="color:#f8f2e4">Bon retour</span><h2>Votre bibliothèque vous attend</h2>
      <p>Connectez-vous pour retrouver vos livres et les lire en ligne à tout moment.</p>
      <div class="perk"><?= icon('library') ?> Tous vos livres au même endroit</div>
      <div class="perk"><?= icon('unlock') ?> Lecture en ligne immédiate</div>
      <div class="perk"><?= icon('receipt') ?> Suivi de vos commandes</div>
    </div></div>
    <div class="auth-form">
      <h1 style="margin-bottom:6px">Connexion</h1>
      <p style="color:var(--muted);margin-bottom:20px">Pas encore de compte ? <a href="<?php h(url('customer/register.php')); ?>" class="text-coffee" style="font-weight:600">Inscrivez-vous</a></p>
      <?php if ($error): ?><div class="chip" style="background:#f2d6d2;color:#a3302a;margin-bottom:16px;width:100%"><?= icon('close') ?> <?php h($error); ?></div><?php endif; ?>
      <form method="post"><?= csrf_field() ?>
        <div class="field"><label>Adresse e-mail</label><input type="email" name="email" required value="<?= e(input('email')) ?>"></div>
        <div class="field"><label>Mot de passe</label><input type="password" name="password" required></div>
        <div style="display:flex;justify-content:flex-end;margin-bottom:18px"><a href="<?php h(url('customer/forgot-password.php')); ?>" class="text-coffee" style="font-size:.85rem">Mot de passe oublié ?</a></div>
        <button class="btn btn-block btn-lg">Se connecter</button>
      </form>
      <div class="chip" style="margin-top:18px;width:100%;justify-content:center">💡 Démo : client@bibliotheque-numerique.dz / client123</div>
    </div>
  </div>
</div></section>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
