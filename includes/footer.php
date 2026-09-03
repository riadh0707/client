</main>

<section class="section">
  <div class="container">
    <div class="newsletter reveal">
      <span class="eyebrow">La lettre de la bibliothèque</span>
      <h2>Recevez nos nouveautés</h2>
      <p>Inscrivez-vous pour être informé des nouveaux titres, des promotions et de nos conseils de lecture.</p>
      <form id="newsletterForm" onsubmit="return Biblio.subscribe(event)">
        <input type="email" name="email" placeholder="Votre adresse e-mail" required>
        <button class="btn btn-gold" type="submit">Je m'inscris</button>
      </form>
    </div>
  </div>
</section>

<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
        <a href="<?php h(url('index.php')); ?>" class="logo"><span class="mark"><?= icon('library') ?></span><span class="txt"><b>La Bibliothèque</b><small style="color:#c9a959">المكتبة الرقمية</small></span></a>
        <p><?php h(setting('meta_description', 'Livres et présentations numériques : lecture en ligne immédiate.')); ?></p>
        <div class="social">
          <a href="<?php h(setting('social_instagram', '#')); ?>" aria-label="Instagram"><?= icon('instagram') ?></a>
          <a href="<?php h(setting('social_facebook', '#')); ?>" aria-label="Facebook"><?= icon('facebook') ?></a>
          <a href="<?php h(setting('social_tiktok', '#')); ?>" aria-label="TikTok"><?= icon('tiktok') ?></a>
        </div>
      </div>
      <div class="footer-col">
        <h4>La bibliothèque</h4>
        <a href="<?php h(url('catalogue.php')); ?>">Tout le catalogue</a>
        <a href="<?php h(url('catalogue.php?filter=new')); ?>">Nouveautés</a>
        <a href="<?php h(url('catalogue.php?filter=sale')); ?>">Promotions</a>
        <a href="<?php h(url('catalogue.php?filter=bestseller')); ?>">Meilleures ventes</a>
      </div>
      <div class="footer-col">
        <h4>Aide</h4>
        <a href="<?php h(url('faq.php')); ?>">FAQ</a>
        <a href="<?php h(url('about.php')); ?>">À propos</a>
        <a href="<?php h(url('contact.php')); ?>">Contact</a>
        <a href="<?php h(url('page.php?p=paiement')); ?>">Paiement BaridiMob</a>
      </div>
      <div class="footer-col">
        <h4>Contact</h4>
        <ul class="footer-contact">
          <li><?= icon('pin') ?> <span><?php h(setting('contact_address', 'Alger, Algérie')); ?></span></li>
          <li><?= icon('phone') ?> <span><?php h(setting('contact_phone', '')); ?></span></li>
          <li><?= icon('mail') ?> <span><?php h(setting('contact_email', '')); ?></span></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© <?= date('Y') ?> <?php h(setting('site_name', 'La Bibliothèque Numérique')); ?> — المكتبة الرقمية. Conçu avec ♥ en Algérie.</span>
      <div class="pay-icons"><span>BaridiMob</span><span>CCP</span><span>Edahabia</span></div>
    </div>
  </div>
</footer>

<div class="toast-wrap" id="toastWrap"></div>
<button class="to-top" id="toTop" aria-label="Haut de page"><?= icon('chevron') ?></button>
<script src="<?php h(asset('js/main.js')); ?>" defer></script>
</body>
</html>
