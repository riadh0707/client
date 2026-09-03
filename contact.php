<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once INCLUDES_PATH . '/components.php';
$sent = false;
if (is_post()) { csrf_check(); if (rate_limit('contact', 5, 120) && input('name') && valid_email(input('email')) && input('message')) { $sent = true; } }
$pageTitle = 'Contact';
require_once INCLUDES_PATH . '/header.php';
?>
<div class="container"><?= breadcrumb([['label' => 'Contact']]) ?></div>
<section class="section-sm"><div class="container">
  <div class="section-head"><span class="eyebrow">Nous écrire</span><h1>Une question ? Écrivez-nous</h1><p>Nous répondons avec plaisir sous 24h.</p><div class="divider-orn"></div></div>
  <div class="cart-layout">
    <div class="card-panel">
      <?php if ($sent): ?><div class="empty-state" style="padding:30px"><div class="ic" style="color:var(--success)"><?= icon('check') ?></div><h3>Message envoyé !</h3><p style="color:var(--muted)">Merci, je vous réponds très vite.</p></div>
      <?php else: ?>
      <h3 style="margin-bottom:16px">Envoyez-moi un message</h3>
      <form method="post"><?= csrf_field() ?>
        <div class="form-row"><div class="field"><label>Nom complet</label><input name="name" required value="<?= e(input('name')) ?>"></div><div class="field"><label>E-mail</label><input type="email" name="email" required value="<?= e(input('email')) ?>"></div></div>
        <div class="field"><label>Sujet</label><input name="subject" value="<?= e(input('subject')) ?>"></div>
        <div class="field"><label>Message</label><textarea name="message" required></textarea></div>
        <button class="btn btn-lg">Envoyer <?= icon('arrow') ?></button>
      </form>
      <?php endif; ?>
    </div>
    <aside style="display:grid;gap:16px">
      <div class="card-panel"><div style="display:flex;gap:12px"><span class="review-avatar"><?= icon('phone') ?></span><div><strong>Téléphone</strong><p style="color:var(--muted);margin:2px 0 0"><?php h(setting('contact_phone')); ?></p></div></div></div>
      <div class="card-panel"><div style="display:flex;gap:12px"><span class="review-avatar"><?= icon('mail') ?></span><div><strong>E-mail</strong><p style="color:var(--muted);margin:2px 0 0"><?php h(setting('contact_email')); ?></p></div></div></div>
      <div class="card-panel"><div style="display:flex;gap:12px"><span class="review-avatar"><?= icon('instagram') ?></span><div><strong>Réseaux</strong><p style="color:var(--muted);margin:2px 0 0">Suivez la bibliothèque sur Instagram, Facebook et TikTok.</p></div></div></div>
      <div class="card-panel"><div style="display:flex;gap:12px"><span class="review-avatar"><?= icon('wallet') ?></span><div><strong>Paiement</strong><p style="color:var(--muted);margin:2px 0 0">Par BaridiMob, validation sous 24h.</p></div></div></div>
    </aside>
  </div>
</div></section>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
