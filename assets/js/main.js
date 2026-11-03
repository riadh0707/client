/* ============================================================================
   Bella Rosa — Interactions front-end (Vanilla JS)
   ========================================================================== */
'use strict';

const Bella = (() => {
  const base = (window.BELLA && window.BELLA.base) || '';
  const csrf = (window.BELLA && window.BELLA.csrf) || '';

  /* ---------------------------------------------------------------- Toasts */
  function toast(message, type = 'success', title = '') {
    const wrap = document.getElementById('toastWrap');
    if (!wrap) return;
    const icons = {
      success: '<path d="M20 6L9 17l-5-5"/>',
      error: '<path d="M6 6l12 12M18 6L6 18"/>',
      info: '<path d="M12 8v5M12 16h.01"/><circle cx="12" cy="12" r="9"/>'
    };
    const el = document.createElement('div');
    el.className = 'toast ' + type;
    el.innerHTML =
      '<span class="ti"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
      (icons[type] || icons.success) + '</svg></span>' +
      '<div>' + (title ? '<strong>' + title + '</strong>' : '') + '<span>' + message + '</span></div>';
    wrap.appendChild(el);
    setTimeout(() => { el.classList.add('hide'); setTimeout(() => el.remove(), 350); }, 3200);
  }

  /* ------------------------------------------------------------- API helper */
  async function api(path, data) {
    const opts = { method: data ? 'POST' : 'GET', headers: { 'X-Requested-With': 'XMLHttpRequest' } };
    if (data) {
      opts.headers['Content-Type'] = 'application/json';
      opts.headers['X-CSRF-Token'] = csrf;
      opts.body = JSON.stringify(data);
    }
    const res = await fetch(base + path, opts);
    return res.json();
  }

  /* ------------------------------------------------------------------ Cart */
  async function addToCart(id, qty = 1, btn) {
    if (btn) { btn.disabled = true; btn.style.opacity = '.7'; }
    try {
      const r = await api('/api/cart.php', { action: 'add', id, qty });
      if (r.ok) {
        updateCount('cartCount', r.count);
        toast(r.message || 'Produit ajouté au panier', 'success', 'Panier');
        pulse(document.getElementById('cartCount'));
      } else { toast(r.error || 'Erreur', 'error'); }
    } catch (e) { toast('Connexion impossible', 'error'); }
    if (btn) { btn.disabled = false; btn.style.opacity = ''; }
  }

  async function updateCartQty(id, qty) {
    const r = await api('/api/cart.php', { action: 'update', id, qty });
    if (r.ok && window.__onCartChange) window.__onCartChange(r);
    return r;
  }
  async function removeFromCart(id) {
    const r = await api('/api/cart.php', { action: 'remove', id });
    if (r.ok) { updateCount('cartCount', r.count); if (window.__onCartChange) window.__onCartChange(r); }
    return r;
  }

  /* -------------------------------------------------------------- Wishlist */
  async function toggleWish(id, btn) {
    const r = await api('/api/wishlist.php', { action: 'toggle', id });
    if (r.ok) {
      updateCount('wishCount', r.count);
      document.querySelectorAll('.wish-btn[data-id="' + id + '"]').forEach(b => b.classList.toggle('active', r.active));
      toast(r.active ? 'Ajouté à vos favoris' : 'Retiré des favoris', 'success', 'Favoris');
    }
  }

  /* --------------------------------------------------------------- Compare */
  function toggleCompare(id) {
    let list = JSON.parse(localStorage.getItem('compare') || '[]');
    if (list.includes(id)) { list = list.filter(x => x !== id); toast('Retiré du comparateur', 'info'); }
    else { if (list.length >= 4) { toast('Maximum 4 produits à comparer', 'error'); return; } list.push(id); toast('Ajouté au comparateur', 'success', 'Comparateur'); }
    localStorage.setItem('compare', JSON.stringify(list));
    renderCompareBar();
  }
  function renderCompareBar() {
    const list = JSON.parse(localStorage.getItem('compare') || '[]');
    let bar = document.getElementById('compareBar');
    if (!list.length) { if (bar) bar.remove(); return; }
    if (!bar) {
      bar = document.createElement('div');
      bar.id = 'compareBar';
      bar.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:var(--surface);box-shadow:var(--shadow-lg);border-radius:999px;padding:10px 12px 10px 22px;display:flex;align-items:center;gap:14px;z-index:150;font-size:.9rem';
      document.body.appendChild(bar);
    }
    bar.innerHTML = '<strong>' + list.length + '</strong> produit(s) à comparer <a href="' + base + '/compare.php" class="btn btn-sm">Comparer</a> <button onclick="localStorage.removeItem(\'compare\');Bella.renderCompareBar()" class="icon-btn" aria-label="Vider la comparaison" style="width:34px;height:34px"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg></button>';
  }

  /* ------------------------------------------------------------ Newsletter */
  async function subscribe(e) {
    e.preventDefault();
    const email = e.target.querySelector('input[type=email]').value;
    const r = await api('/api/newsletter.php', { email });
    toast(r.message || (r.ok ? 'Merci pour votre inscription !' : 'Erreur'), r.ok ? 'success' : 'error', 'Newsletter');
    if (r.ok) e.target.reset();
    return false;
  }

  /* ----------------------------------------------------------- Quick view */
  async function quickView(slug) {
    const overlay = ensureModal();
    const modal = overlay.querySelector('.modal');
    modal.querySelector('.modal-body').innerHTML = skeletonQuick();
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
    try {
      const r = await api('/api/quickview.php?slug=' + encodeURIComponent(slug));
      if (r.ok) modal.querySelector('.modal-body').innerHTML = r.html;
    } catch (e) {}
  }
  function ensureModal() {
    let overlay = document.getElementById('quickModal');
    if (!overlay) {
      overlay = document.createElement('div');
      overlay.className = 'modal-overlay'; overlay.id = 'quickModal';
      overlay.innerHTML = '<div class="modal"><button class="modal-close" aria-label="Fermer"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg></button><div class="modal-body"></div></div>';
      document.body.appendChild(overlay);
      overlay.addEventListener('click', ev => { if (ev.target === overlay || ev.target.closest('.modal-close')) closeModal(); });
    }
    return overlay;
  }
  function closeModal() { const o = document.getElementById('quickModal'); if (o) o.classList.remove('open'); document.body.style.overflow = ''; }
  function skeletonQuick() {
    return '<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;padding:24px">' +
      '<div class="skeleton" style="aspect-ratio:1;border-radius:16px"></div>' +
      '<div><div class="skeleton" style="height:28px;width:70%;margin-bottom:14px"></div>' +
      '<div class="skeleton" style="height:16px;width:40%;margin-bottom:20px"></div>' +
      '<div class="skeleton" style="height:60px;margin-bottom:16px"></div>' +
      '<div class="skeleton" style="height:44px;width:60%"></div></div></div>';
  }

  /* ------------------------------------------------------- Helpers divers */
  function updateCount(id, n) {
    const el = document.getElementById(id);
    if (!el) return;
    el.textContent = n;
    el.style.display = n > 0 ? '' : 'none';
  }
  function pulse(el) { if (!el) return; el.animate([{ transform: 'scale(1)' }, { transform: 'scale(1.4)' }, { transform: 'scale(1)' }], { duration: 400 }); }

  return { toast, api, addToCart, updateCartQty, removeFromCart, toggleWish, toggleCompare, renderCompareBar, subscribe, quickView, closeModal, updateCount };
})();
window.Bella = Bella;

/* ============================================================== DOM ready */
document.addEventListener('DOMContentLoaded', () => {

  // Flashs serveur
  (window.__flash || []).forEach(f => Bella.toast(f.message, f.type));

  /* Header scrolled + scroll to top */
  const header = document.getElementById('siteHeader');
  const toTop = document.getElementById('toTop');
  const onScroll = () => {
    if (header) header.classList.toggle('scrolled', window.scrollY > 20);
    if (toTop) toTop.classList.toggle('show', window.scrollY > 500);
  };
  window.addEventListener('scroll', onScroll, { passive: true }); onScroll();
  if (toTop) toTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));

  /* Thème sombre */
  const toggleTheme = () => {
    const dark = document.documentElement.getAttribute('data-theme') === 'dark';
    const next = dark ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    try { localStorage.setItem('theme', next); } catch (e) {}
  };
  document.getElementById('themeBtn')?.addEventListener('click', toggleTheme);
  document.getElementById('drawerTheme')?.addEventListener('click', (e) => { e.preventDefault(); toggleTheme(); });

  /* Menu mobile */
  const drawer = document.getElementById('mobileDrawer');
  const backdrop = document.getElementById('drawerBackdrop');
  const toggle = document.getElementById('menuToggle');
  const openDrawer = () => { drawer.classList.add('open'); backdrop.classList.add('open'); };
  const closeDrawer = () => { drawer.classList.remove('open'); backdrop.classList.remove('open'); };
  if (toggle) toggle.addEventListener('click', openDrawer);
  if (backdrop) backdrop.addEventListener('click', closeDrawer);

  /* Recherche */
  const searchOverlay = document.getElementById('searchOverlay');
  const searchInput = document.getElementById('searchInput');
  document.getElementById('searchBtn')?.addEventListener('click', () => {
    searchOverlay.classList.add('open'); setTimeout(() => searchInput.focus(), 100);
  });
  document.getElementById('searchClose')?.addEventListener('click', () => searchOverlay.classList.remove('open'));
  searchOverlay?.addEventListener('click', e => { if (e.target === searchOverlay) searchOverlay.classList.remove('open'); });
  let searchTimer;
  searchInput?.addEventListener('input', () => {
    clearTimeout(searchTimer);
    const q = searchInput.value.trim();
    const box = document.getElementById('searchResults');
    if (q.length < 2) { box.innerHTML = '<p class="search-hint">Tapez au moins 2 caractères…</p>'; return; }
    box.innerHTML = '<p class="search-hint">Recherche…</p>';
    searchTimer = setTimeout(async () => {
      const r = await Bella.api('/api/search.php?q=' + encodeURIComponent(q));
      if (r.ok && r.items.length) {
        box.innerHTML = r.items.map(p =>
          '<a class="search-result" href="' + r.base + '/product.php?slug=' + p.slug + '">' +
          '<img src="' + p.image + '" alt=""><div style="flex:1"><div class="r-name">' + p.name + '</div>' +
          '<div style="font-size:.78rem;color:var(--muted)">' + p.brand + '</div></div>' +
          '<div class="r-price">' + p.price + '</div></a>').join('') +
          '<a class="search-hint" href="' + r.base + '/shop.php?q=' + encodeURIComponent(q) + '" style="display:block;text-align:center;color:var(--rose);font-weight:600;padding:12px">Voir tous les résultats</a>';
      } else {
        box.innerHTML = '<p class="search-hint">Aucun résultat pour « ' + q + ' »</p>';
      }
    }, 250);
  });

  /* Délégation : boutons produits */
  document.addEventListener('click', e => {
    const add = e.target.closest('.add-cart-btn');
    if (add) { e.preventDefault(); Bella.addToCart(parseInt(add.dataset.id), 1, add); return; }
    const wish = e.target.closest('.wish-btn');
    if (wish) { e.preventDefault(); Bella.toggleWish(parseInt(wish.dataset.id), wish); return; }
    const cmp = e.target.closest('.compare-btn');
    if (cmp) { e.preventDefault(); Bella.toggleCompare(parseInt(cmp.dataset.id)); return; }
    const qv = e.target.closest('.quickview-btn');
    if (qv) { e.preventDefault(); Bella.quickView(qv.dataset.slug); return; }
  });

  /* Escape ferme les overlays */
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { searchOverlay?.classList.remove('open'); Bella.closeModal(); closeDrawer(); }
  });

  /* Hero slider */
  const slides = document.querySelectorAll('.hero-slide');
  const dots = document.querySelectorAll('#heroDots button');
  if (slides.length > 1) {
    let cur = 0;
    const go = i => {
      slides.forEach((s, k) => { s.hidden = k !== i; s.classList.toggle('is-active', k === i); });
      dots.forEach((d, k) => d.classList.toggle('active', k === i));
      cur = i;
    };
    dots.forEach(d => d.addEventListener('click', () => go(parseInt(d.dataset.dot))));
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (!reduce) setInterval(() => go((cur + 1) % slides.length), 7000);
  }

  /* Compte à rebours */
  const cd = document.getElementById('countdown');
  if (cd) {
    const deadline = parseInt(cd.dataset.deadline) * 1000;
    const tick = () => {
      let diff = Math.max(0, deadline - Date.now());
      const d = Math.floor(diff / 86400000); diff -= d * 86400000;
      const h = Math.floor(diff / 3600000); diff -= h * 3600000;
      const m = Math.floor(diff / 60000); diff -= m * 60000;
      const s = Math.floor(diff / 1000);
      const pad = n => String(n).padStart(2, '0');
      cd.querySelector('[data-d]').textContent = pad(d);
      cd.querySelector('[data-h]').textContent = pad(h);
      cd.querySelector('[data-m]').textContent = pad(m);
      cd.querySelector('[data-s]').textContent = pad(s);
    };
    tick(); setInterval(tick, 1000);
  }

  /* Reveal on scroll (avec repli si IntersectionObserver indisponible) */
  const reveals = document.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach(en => { if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); } });
    }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
    reveals.forEach(el => io.observe(el));
  } else {
    reveals.forEach(el => el.classList.add('in'));
  }

  /* Compare bar init */
  Bella.renderCompareBar();
});
