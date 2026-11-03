<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once INCLUDES_PATH . '/icons.php';

if (is_logged_in()) { redirect('customer/account.php'); }

$error = '';
if (is_post()) {
    csrf_check();
    if (!rate_limit('login', 8, 60)) {
        $error = 'Trop de tentatives. Réessayez dans une minute.';
    } else {
        $email = input('email');
        $pass  = $_POST['password'] ?? '';
        $u = Database::first('SELECT * FROM customers WHERE email = ? AND deleted_at IS NULL', [$email]);
        if ($u && password_verify($pass, $u['password'])) {
            login_user($u);
            flash('Bienvenue ' . $u['first_name'] . ' !', 'success');
            $to = $_SESSION['redirect_after_login'] ?? 'customer/account.php';
            unset($_SESSION['redirect_after_login']);
            redirect(str_starts_with($to, 'http') ? 'customer/account.php' : $to);
        }
        $error = 'E-mail ou mot de passe incorrect.';
    }
}

$pageTitle = 'Connexion';
require_once INCLUDES_PATH . '/header.php';
?>
<section class="section">
  <div class="container" style="max-width:1000px">
    <div class="auth-wrap">
      <div class="auth-visual">
        <span class="blob b1" style="opacity:.3"></span>
        <div style="position:relative;z-index:2">
          <span class="eyebrow" style="color:#fff">Bon retour parmi nous</span>
          <h2>Votre beauté vous attend</h2>
          <p>Connectez-vous pour retrouver vos favoris, suivre vos commandes et cumuler vos points fidélité.</p>
          <div class="perk"><?= icon('heart') ?> Vos produits favoris sauvegardés</div>
          <div class="perk"><?= icon('box') ?> Suivi de commandes en temps réel</div>
          <div class="perk"><?= icon('award') ?> Programme de fidélité récompensé</div>
        </div>
      </div>
      <div class="auth-form">
        <h1 style="margin-bottom:6px">Connexion</h1>
        <p style="color:var(--muted);margin-bottom:20px">Pas encore de compte ? <a href="<?php h(url('customer/register.php')); ?>" class="text-rose" style="font-weight:600">Inscrivez-vous</a></p>
        <?php if ($error): ?><div class="chip" style="background:#fbdcda;color:#b33;margin-bottom:16px;width:100%"><?= icon('close') ?> <?php h($error); ?></div><?php endif; ?>
        <form method="post">
          <?= csrf_field() ?>
          <div class="field"><label>Adresse e-mail</label><input type="email" name="email" required value="<?= e(input('email')) ?>" placeholder="vous@email.com"></div>
          <div class="field"><label>Mot de passe</label><input type="password" name="password" required placeholder="••••••••"></div>
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px">
            <label style="display:flex;gap:8px;align-items:center;font-size:.85rem;color:var(--muted)"><input type="checkbox" name="remember" style="accent-color:var(--rose)"> Se souvenir de moi</label>
            <a href="<?php h(url('customer/forgot-password.php')); ?>" class="text-rose" style="font-size:.85rem">Mot de passe oublié ?</a>
          </div>
          <button class="btn btn-block btn-lg">Se connecter</button>
        </form>
        <div class="chip" style="margin-top:18px;width:100%;justify-content:center">💡 Démo : client@bella-rosa.dz / client123</div>
      </div>
    </div>
  </div>
</section>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
