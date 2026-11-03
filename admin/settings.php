<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once INCLUDES_PATH . '/components.php';
require_admin();

$fields = [
    'Identité'   => ['site_name' => 'Nom du site', 'site_tagline' => 'Slogan', 'meta_description' => 'Description SEO (meta)'],
    'Coordonnées'=> ['contact_email' => 'E-mail', 'contact_phone' => 'Téléphone', 'contact_address' => 'Adresse', 'map_embed' => 'Coordonnées carte (lat,lng)'],
    'Livraison'  => ['free_shipping_min' => 'Livraison gratuite dès (DA)', 'shipping_flat' => 'Frais de livraison (DA)'],
    'Apparence'  => ['primary_color' => 'Couleur principale (hex)', 'accent_color' => 'Couleur d\'accent (hex)', 'announcement' => 'Bandeau d\'annonce'],
    'Réseaux sociaux' => ['social_instagram' => 'Instagram', 'social_facebook' => 'Facebook', 'social_tiktok' => 'TikTok'],
];

if (is_post()) {
    csrf_check();
    $upd = Database::pdo()->prepare('UPDATE settings SET value = ? WHERE `key` = ?');
    $ins = Database::pdo()->prepare('INSERT INTO settings (`key`, value) VALUES (?, ?)');
    foreach ($fields as $group) {
        foreach ($group as $key => $label) {
            $val = input($key);
            if (array_key_exists($key, $GLOBALS['settings'])) { $upd->execute([$val, $key]); }
            else { try { $ins->execute([$key, $val]); } catch (Throwable $e) {} }
        }
    }
    flash('Paramètres enregistrés.');
    redirect('admin/settings.php');
}

$adminActive = 'settings';
$adminTitle = 'Paramètres du site';
require_once INCLUDES_PATH . '/admin_layout.php';
?>
<form method="post" style="max-width:820px">
  <?= csrf_field() ?>
  <?php foreach ($fields as $group => $items): ?>
  <div class="a-panel">
    <h3 style="margin-bottom:16px"><?php h($group); ?></h3>
    <?php foreach ($items as $key => $label): ?>
      <div class="a-field">
        <label><?php h($label); ?></label>
        <?php if ($key === 'meta_description' || $key === 'announcement' || $key === 'contact_address'): ?>
          <textarea name="<?= $key ?>" style="min-height:60px"><?= e(setting($key)) ?></textarea>
        <?php elseif (str_contains($key, 'color')): ?>
          <div style="display:flex;gap:10px;align-items:center"><input type="color" name="<?= $key ?>" value="<?= e(setting($key, '#b76e79')) ?>" style="width:56px;height:44px;padding:4px"><input value="<?= e(setting($key)) ?>" oninput="this.previousElementSibling.value=this.value" name="<?= $key ?>_txt" style="flex:1" disabled></div>
        <?php else: ?>
          <input name="<?= $key ?>" value="<?= e(setting($key)) ?>">
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endforeach; ?>
  <div class="a-panel" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
    <div><strong>Logo & Favicon</strong><p style="color:var(--a-muted);margin:4px 0 0;font-size:.85rem">Le logo textuel et le favicon SVG sont personnalisables dans <code>assets/images/</code>. L'upload d'images est prévu dans l'architecture (dossier <code>/uploads</code> sécurisé).</p></div>
    <button class="a-btn" style="padding:14px 28px"><?= icon('check') ?> Enregistrer tous les paramètres</button>
  </div>
</form>
<?php admin_footer(); ?>
