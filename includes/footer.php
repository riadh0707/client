</main>

<!-- Newsletter -->
<section class="section">
  <div class="container">
    <div class="newsletter reveal">
      <span class="eyebrow">Rejoignez le cercle Bella Rosa</span>
      <h2>-10% sur votre première commande</h2>
      <p>Inscrivez-vous à notre newsletter et recevez conseils beauté, avant-premières et offres exclusives.</p>
      <form id="newsletterForm" onsubmit="return Bella.subscribe(event)">
        <input type="email" name="email" placeholder="Votre adresse e-mail" required>
        <button class="btn" type="submit">Je m'inscris</button>
      </form>
    </div>
  </div>
</section>

<!-- Pied de page -->
<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
        <a href="<?php h(url('index.php')); ?>" class="logo"><span class="mark">✿</span> Bella<b>Rosa</b></a>
        <p><?php h(setting('meta_description', 'Boutique beauté premium en Algérie.')); ?></p>
        <div class="social">
          <a href="<?php h(setting('social_instagram', '#')); ?>" aria-label="Instagram"><?= icon('instagram') ?></a>
          <a href="<?php h(setting('social_facebook', '#')); ?>" aria-label="Facebook"><?= icon('facebook') ?></a>
          <a href="<?php h(setting('social_tiktok', '#')); ?>" aria-label="TikTok"><?= icon('tiktok') ?></a>
        </div>
      </div>
      <div class="footer-col">
        <h4>Boutique</h4>
        <a href="<?php h(url('shop.php')); ?>">Tous les produits</a>
        <a href="<?php h(url('shop.php?filter=new')); ?>">Nouveautés</a>
        <a href="<?php h(url('shop.php?filter=sale')); ?>">Promotions</a>
        <a href="<?php h(url('shop.php?filter=bestseller')); ?>">Meilleures ventes</a>
        <a href="<?php h(url('wishlist.php')); ?>">Mes favoris</a>
      </div>
      <div class="footer-col">
        <h4>Aide</h4>
        <a href="<?php h(url('faq.php')); ?>">FAQ</a>
        <a href="<?php h(url('about.php')); ?>">À propos</a>
        <a href="<?php h(url('contact.php')); ?>">Contact</a>
        <a href="<?php h(url('page.php?p=livraison')); ?>">Livraison</a>
        <a href="<?php h(url('page.php?p=paiement')); ?>">Paiement</a>
      </div>
      <div class="footer-col">
        <h4>Contact</h4>
        <ul class="footer-contact">
          <li><?= icon('pin') ?> <span><?php h(setting('contact_address', 'Alger, Algérie')); ?></span></li>
          <li><?= icon('phone') ?> <span><?php h(setting('contact_phone', '')); ?></span></li>
          <li><?= icon('mail') ?> <span><?php h(setting('contact_email', '')); ?></span></li>
          <li><?= icon('clock') ?> <span>Sam – Jeu : 9h – 18h</span></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© <?= date('Y') ?> <?php h(setting('site_name', 'Bella Rosa')); ?>. Tous droits réservés. Conçu avec ♥ en Algérie.</span>
      <div class="pay-icons">
        <span>BaridiMob</span><span>CCP</span><span>Edahabia</span><span>CIB</span><span>Visa</span><span>Cash</span>
      </div>
    </div>
  </div>
</footer>

<div class="toast-wrap" id="toastWrap"></div>
<button class="to-top" id="toTop" aria-label="Haut de page"><?= icon('chevron', 'up') ?></button>

<script src="<?php h(asset('js/main.js')); ?>" defer></script>
</body>
</html>
