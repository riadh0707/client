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
require_once INCLUDES_PATH . '/payment.php';
require_once INCLUDES_PATH . '/captcha.php';
$sat = SatimGateway::config();
require_once INCLUDES_PATH . '/admin_layout.php';
?>
<div class="a-panel" style="max-width:820px">
  <h3 style="margin-bottom:14px"><?= icon('wallet') ?> Paiement en ligne SATIM (CIB / Edahabia)</h3>
  <table class="a-table"><tbody>
    <tr><td>État</td><td><?php if (SatimGateway::enabled()): ?><span class="a-pill st-paid">Activé</span><?php else: ?><span class="a-pill st-cancelled">Désactivé</span><?php endif; ?></td></tr>
    <tr><td>Environnement</td><td><?= SatimGateway::isMock() ? 'Simulateur (tests locaux)' : e($sat['base_url']) ?></td></tr>
    <tr><td>Username marchand</td><td><code><?= e($sat['username'] ?: '—') ?></code></td></tr>
    <tr><td>Identifiant terminal</td><td><code><?= e($sat['terminal_id'] ?: '—') ?></code></td></tr>
    <tr><td>Devise / Langue</td><td><?= e($sat['currency']) ?> · <?= e($sat['language']) ?></td></tr>
    <tr><td>Captcha</td><td><?= captcha_uses_recaptcha() ? 'reCAPTCHA v2' : 'Captcha intégré' ?></td></tr>
    <tr><td>Numéro vert</td><td><?= e($sat['green_number']) ?></td></tr>
  </tbody></table>
  <p style="color:var(--a-muted);font-size:.8rem;margin-top:12px">🔒 Les identifiants SATIM sont configurés dans <code>config/config.php</code> (jamais committé). Pour passer en production, mettez <code>base_url</code> sur le domaine fourni par la SATIM et <code>mock</code> à <code>false</code>.</p>
</div>

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
