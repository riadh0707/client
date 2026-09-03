<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once INCLUDES_PATH . '/components.php';
require_login();
$u = current_user();
if (is_post()) {
    csrf_check();
    if (input('form') === 'profile') {
        Database::run('UPDATE customers SET first_name=?, last_name=?, phone=?, wilaya=? WHERE id=?', [input('first_name'), input('last_name'), input('phone'), input('wilaya'), $u['id']]);
        flash('Profil mis à jour.', 'success');
    } elseif (input('form') === 'password') {
        $full = Database::first('SELECT password FROM customers WHERE id=?', [$u['id']]);
        if (!password_verify($_POST['current'] ?? '', $full['password'])) { flash('Mot de passe actuel incorrect.', 'error'); }
        elseif (strlen($_POST['new'] ?? '') < 6) { flash('Nouveau mot de passe trop court.', 'error'); }
        else { Database::run('UPDATE customers SET password=? WHERE id=?', [password_hash($_POST['new'], PASSWORD_DEFAULT), $u['id']]); flash('Mot de passe modifié.', 'success'); }
    }
    redirect('customer/profile.php');
}
$pageTitle = 'Mon profil';
require_once INCLUDES_PATH . '/header.php';
?>
<section class="section-sm"><div class="container">
  <div class="account-layout">
    <?php $active = 'profile'; require INCLUDES_PATH . '/account_nav.php'; ?>
    <div style="display:grid;gap:22px">
      <div class="card-panel"><h2 style="margin-bottom:16px">Informations personnelles</h2>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="form" value="profile">
          <div class="form-row"><div class="field"><label>Prénom</label><input name="first_name" value="<?= e($u['first_name']) ?>" required></div><div class="field"><label>Nom</label><input name="last_name" value="<?= e($u['last_name']) ?>" required></div></div>
          <div class="form-row"><div class="field"><label>E-mail</label><input value="<?= e($u['email']) ?>" disabled style="opacity:.7"></div><div class="field"><label>Téléphone</label><input name="phone" value="<?= e($u['phone']) ?>"></div></div>
          <div class="field"><label>Wilaya</label><input name="wilaya" value="<?= e($u['wilaya'] ?? '') ?>"></div>
          <button class="btn">Enregistrer</button>
        </form>
      </div>
      <div class="card-panel"><h2 style="margin-bottom:16px">Changer le mot de passe</h2>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="form" value="password">
          <div class="field"><label>Mot de passe actuel</label><input type="password" name="current" required></div>
          <div class="form-row"><div class="field"><label>Nouveau</label><input type="password" name="new" required></div><div class="field"><label>Confirmer</label><input type="password" name="confirm" required></div></div>
          <button class="btn">Modifier</button>
        </form>
      </div>
    </div>
  </div>
</div></section>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
