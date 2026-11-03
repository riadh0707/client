<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once INCLUDES_PATH . '/icons.php';

if (is_logged_in()) { redirect('customer/account.php'); }

$errors = [];
if (is_post()) {
    csrf_check();
    $fn = input('first_name'); $ln = input('last_name');
    $email = input('email');   $phone = input('phone');
    $pass = $_POST['password'] ?? ''; $pass2 = $_POST['password2'] ?? '';

    if (mb_strlen($fn) < 2) { $errors[] = 'Prénom requis.'; }
    if (mb_strlen($ln) < 2) { $errors[] = 'Nom requis.'; }
    if (!valid_email($email)) { $errors[] = 'E-mail invalide.'; }
    if (strlen($pass) < 6) { $errors[] = 'Le mot de passe doit contenir au moins 6 caractères.'; }
    if ($pass !== $pass2) { $errors[] = 'Les mots de passe ne correspondent pas.'; }
    if (Database::first('SELECT id FROM customers WHERE email = ?', [$email])) { $errors[] = 'Un compte existe déjà avec cet e-mail.'; }

    if (!$errors) {
        $id = Database::insert(
            'INSERT INTO customers (first_name, last_name, email, phone, password) VALUES (?,?,?,?,?)',
            [$fn, $ln, $email, $phone, password_hash($pass, PASSWORD_DEFAULT)]
        );
        login_user(['id' => $id]);
        flash('Bienvenue chez Bella Rosa, ' . $fn . ' !', 'success');
        redirect('customer/account.php');
    }
}

$pageTitle = 'Inscription';
require_once INCLUDES_PATH . '/header.php';
?>
<section class="section">
  <div class="container" style="max-width:1000px">
    <div class="auth-wrap">
      <div class="auth-visual">
        <span class="blob b2" style="opacity:.3"></span>
        <div style="position:relative;z-index:2">
          <span class="eyebrow" style="color:#fff">Rejoignez le cercle Bella Rosa</span>
          <h2>-10% dès votre inscription</h2>
          <p>Créez votre compte en 30 secondes et profitez d'avantages exclusifs.</p>
          <div class="perk"><?= icon('gift') ?> -10% sur votre première commande</div>
          <div class="perk"><?= icon('truck') ?> Suivi de livraison simplifié</div>
          <div class="perk"><?= icon('sparkles') ?> Offres et avant-premières VIP</div>
        </div>
      </div>
      <div class="auth-form">
        <h1 style="margin-bottom:6px">Créer un compte</h1>
        <p style="color:var(--muted);margin-bottom:20px">Déjà inscrite ? <a href="<?php h(url('customer/login.php')); ?>" class="text-rose" style="font-weight:600">Connectez-vous</a></p>
        <?php foreach ($errors as $er): ?><div class="chip" style="background:#fbdcda;color:#b33;margin-bottom:10px;width:100%"><?= icon('close') ?> <?php h($er); ?></div><?php endforeach; ?>
        <form method="post">
          <?= csrf_field() ?>
          <div class="form-row">
            <div class="field"><label>Prénom</label><input name="first_name" required value="<?= e(input('first_name')) ?>"></div>
            <div class="field"><label>Nom</label><input name="last_name" required value="<?= e(input('last_name')) ?>"></div>
          </div>
          <div class="field"><label>E-mail</label><input type="email" name="email" required value="<?= e(input('email')) ?>"></div>
          <div class="field"><label>Téléphone</label><input name="phone" placeholder="0561234567" value="<?= e(input('phone')) ?>"></div>
          <div class="form-row">
            <div class="field"><label>Mot de passe</label><input type="password" name="password" required></div>
            <div class="field"><label>Confirmer</label><input type="password" name="password2" required></div>
          </div>
          <button class="btn btn-block btn-lg">Créer mon compte</button>
          <p style="color:var(--muted);font-size:.78rem;margin-top:12px">En vous inscrivant, vous acceptez nos conditions générales et notre politique de confidentialité.</p>
        </form>
      </div>
    </div>
  </div>
</section>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
