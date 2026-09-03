<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once INCLUDES_PATH . '/icons.php';
$sent = false;
if (is_post()) { csrf_check(); rate_limit('forgot', 5, 60); $sent = valid_email(input('email')); }
$pageTitle = 'Mot de passe oublié';
require_once INCLUDES_PATH . '/header.php';
?>
<section class="section"><div class="container" style="max-width:480px">
  <div class="card-panel center" style="padding:40px">
    <div class="ic" style="width:72px;height:72px;margin:0 auto 18px;border-radius:50%;background:var(--surface-2);display:grid;place-items:center;color:var(--coffee)"><?= icon('mail') ?></div>
    <?php if ($sent): ?><h1>Vérifiez vos e-mails</h1><p style="color:var(--muted)">Si un compte existe, vous recevrez un lien de réinitialisation.</p><a href="<?php h(url('customer/login.php')); ?>" class="btn">Retour</a>
    <?php else: ?><h1>Mot de passe oublié ?</h1><p style="color:var(--muted)">Entrez votre e-mail pour recevoir un lien.</p>
      <form method="post" style="text-align:left;margin-top:16px"><?= csrf_field() ?><div class="field"><label>Adresse e-mail</label><input type="email" name="email" required></div><button class="btn btn-block">Envoyer le lien</button></form>
      <p style="margin-top:14px"><a href="<?php h(url('customer/login.php')); ?>" class="text-coffee">← Retour à la connexion</a></p>
    <?php endif; ?>
  </div>
</div></section>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
