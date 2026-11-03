<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once INCLUDES_PATH . '/components.php';
require_login();
$u = current_user();

if (is_post()) {
    csrf_check();
    if (input('action') === 'add') {
        Database::run('INSERT INTO addresses (customer_id, label, line1, city, wilaya, phone) VALUES (?,?,?,?,?,?)',
            [$u['id'], input('label'), input('line1'), input('city'), input('wilaya'), input('phone')]);
        flash('Adresse ajoutée.', 'success');
    } elseif (input('action') === 'delete') {
        Database::run('DELETE FROM addresses WHERE id=? AND customer_id=?', [(int) input('id'), $u['id']]);
        flash('Adresse supprimée.', 'info');
    }
    redirect('customer/addresses.php');
}
$addresses = Database::all('SELECT * FROM addresses WHERE customer_id=? ORDER BY is_default DESC, id DESC', [$u['id']]);

$pageTitle = 'Mes adresses';
require_once INCLUDES_PATH . '/header.php';
?>
<section class="section-sm">
  <div class="container">
    <div class="account-layout">
      <?php $active = 'addresses'; require INCLUDES_PATH . '/account_nav.php'; ?>
      <div>
        <h1 style="margin-bottom:18px">Mes adresses</h1>
        <div class="stat-cards" style="grid-template-columns:repeat(2,1fr)">
          <?php foreach ($addresses as $a): ?>
          <div class="card-panel">
            <div style="display:flex;justify-content:space-between"><strong><?= icon('pin') ?> <?php h($a['label'] ?: 'Adresse'); ?></strong>
              <form method="post" onsubmit="return confirm('Supprimer cette adresse ?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $a['id'] ?>"><button class="cart-remove"><?= icon('trash') ?></button></form>
            </div>
            <p style="color:var(--muted);margin:10px 0 0"><?php h($a['line1']); ?><br><?php h($a['city']); ?>, <?php h($a['wilaya']); ?><br><?php h($a['phone']); ?></p>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="card-panel" style="margin-top:20px">
          <h3 style="margin-bottom:14px">Ajouter une adresse</h3>
          <form method="post">
            <?= csrf_field() ?><input type="hidden" name="action" value="add">
            <div class="form-row">
              <div class="field"><label>Libellé</label><input name="label" placeholder="Domicile, Bureau…"></div>
              <div class="field"><label>Téléphone</label><input name="phone" placeholder="0561234567"></div>
            </div>
            <div class="field"><label>Adresse</label><input name="line1" required placeholder="Rue, quartier"></div>
            <div class="form-row">
              <div class="field"><label>Ville</label><input name="city"></div>
              <div class="field"><label>Wilaya</label><input name="wilaya"></div>
            </div>
            <button class="btn">Ajouter l'adresse</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
