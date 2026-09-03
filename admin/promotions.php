<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once INCLUDES_PATH . '/components.php';
require_admin();
if (is_post()) {
    csrf_check();
    if (input('action') === 'save') {
        $cid = (int) input('id');
        $data = [strtoupper(input('code')), input('type', 'percent'), (float) input('value'), (float) input('min_amount'), input('description'), input('expires_at') ?: null, (int) input('usage_limit'), isset($_POST['active']) ? 1 : 0];
        if ($cid) { Database::run('UPDATE coupons SET code=?, type=?, value=?, min_amount=?, description=?, expires_at=?, usage_limit=?, active=? WHERE id=?', array_merge($data, [$cid])); flash('Coupon mis à jour.'); }
        else { Database::run('INSERT INTO coupons (code, type, value, min_amount, description, expires_at, usage_limit, active) VALUES (?,?,?,?,?,?,?,?)', $data); flash('Coupon créé.'); }
    } elseif (input('action') === 'delete') { Database::run('DELETE FROM coupons WHERE id = ?', [(int) input('id')]); flash('Coupon supprimé.'); }
    redirect('admin/promotions.php');
}
$edit = (int) input('edit') ? Database::first('SELECT * FROM coupons WHERE id = ?', [(int) input('edit')]) : null;
$coupons = Database::all('SELECT * FROM coupons ORDER BY id DESC');
$adminActive = 'promotions'; $adminTitle = 'Promotions & Coupons';
require_once INCLUDES_PATH . '/admin_layout.php';
?>
<div class="a-grid-2">
  <div class="a-panel"><div class="a-panel-head"><h3>Codes promo</h3></div>
    <div class="a-table-wrap"><table class="a-table"><thead><tr><th>Code</th><th>Réduction</th><th>Min.</th><th>Utilisé</th><th></th></tr></thead><tbody>
    <?php foreach ($coupons as $c): ?><tr><td><strong style="font-family:monospace;background:var(--a-bg);padding:3px 8px;border-radius:6px"><?php h($c['code']); ?></strong></td><td><?= $c['type']==='percent' ? (int)$c['value'].' %' : money($c['value']) ?></td><td><?= money($c['min_amount']) ?></td><td><?= (int) $c['used'] ?>/<?= (int) $c['usage_limit'] ?></td>
      <td style="white-space:nowrap"><a class="a-iconlink" href="?edit=<?= $c['id'] ?>"><?= icon('edit') ?></a><form method="post" style="display:inline" onsubmit="return confirm('Supprimer ?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $c['id'] ?>"><button class="a-iconlink" style="border:0;background:none;cursor:pointer"><?= icon('trash') ?></button></form></td></tr><?php endforeach; ?>
    </tbody></table></div>
  </div>
  <div class="a-panel"><h3 style="margin-bottom:16px"><?= $edit ? 'Modifier' : 'Nouveau coupon' ?></h3>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
      <div class="a-field"><label>Code *</label><input name="code" required style="text-transform:uppercase" value="<?= e($edit['code'] ?? '') ?>"></div>
      <div class="a-row"><div class="a-field"><label>Type</label><select name="type"><option value="percent" <?= ($edit['type']??'')==='percent'?'selected':'' ?>>Pourcentage (%)</option><option value="fixed" <?= ($edit['type']??'')==='fixed'?'selected':'' ?>>Montant fixe (DA)</option></select></div><div class="a-field"><label>Valeur *</label><input type="number" step="1" name="value" required value="<?= e($edit['value'] ?? '') ?>"></div></div>
      <div class="a-row"><div class="a-field"><label>Min. (DA)</label><input type="number" name="min_amount" value="<?= e($edit['min_amount'] ?? '0') ?>"></div><div class="a-field"><label>Limite</label><input type="number" name="usage_limit" value="<?= e($edit['usage_limit'] ?? '100') ?>"></div></div>
      <div class="a-field"><label>Description</label><input name="description" value="<?= e($edit['description'] ?? '') ?>"></div>
      <div class="a-field"><label>Expiration</label><input type="date" name="expires_at" value="<?= e($edit['expires_at'] ?? '') ?>"></div>
      <label style="display:flex;gap:10px;align-items:center;margin-bottom:16px"><input type="checkbox" name="active" <?= !isset($edit['active']) || $edit['active'] ? 'checked' : '' ?>> Actif</label>
      <button class="a-btn"><?= icon('check') ?> Enregistrer</button><?php if ($edit): ?><a href="<?php h(url('admin/promotions.php')); ?>" class="a-btn ghost">Annuler</a><?php endif; ?>
    </form>
  </div>
</div>
<?php admin_footer(); ?>
