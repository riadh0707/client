<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once INCLUDES_PATH . '/components.php';
require_admin();

$fields = [
    'Identité du site' => ['site_name' => 'Nom du site', 'site_name_ar' => 'Nom en arabe', 'site_tagline' => 'Slogan', 'meta_description' => 'Description SEO'],
    'Coordonnées'      => ['contact_email' => 'E-mail', 'contact_phone' => 'Téléphone', 'contact_address' => 'Adresse'],
    'Aperçu des titres'=> ['default_preview_pages' => 'Pages / diapositives d\'aperçu par défaut (nouveaux titres)'],
    'Paiement BaridiMob' => ['baridimob_rip' => 'RIP / Compte BaridiMob', 'baridimob_name' => 'Nom du bénéficiaire', 'baridimob_note' => 'Message affiché au client', 'baridimob_api_enabled' => 'API automatique active (0 = manuel, 1 = API)'],
    'Réseaux sociaux'  => ['social_instagram' => 'Instagram', 'social_facebook' => 'Facebook', 'social_tiktok' => 'TikTok'],
    'Bandeau'          => ['announcement' => 'Bandeau d\'annonce'],
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
        <?php if (in_array($key, ['meta_description', 'announcement', 'baridimob_note', 'contact_address'], true)): ?>
          <textarea name="<?= $key ?>" style="min-height:60px"><?= e(setting($key)) ?></textarea>
        <?php else: ?>
          <input name="<?= $key ?>" value="<?= e(setting($key)) ?>">
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endforeach; ?>
  <div class="a-panel" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
    <div><strong>Logo & couverture</strong><p style="color:var(--a-muted);margin:4px 0 0;font-size:.85rem">Le logo et le favicon sont dans <code>assets/images/</code>. L'API BaridiMob s'activera en passant « API automatique » à 1 une fois vos accès obtenus (voir <code>includes/payment.php</code>).</p></div>
    <button class="a-btn" style="padding:14px 28px"><?= icon('check') ?> Enregistrer</button>
  </div>
</form>
<?php admin_footer(); ?>
