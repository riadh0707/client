/* ============================================================================
   La Bibliothèque Numérique — interactions (Vanilla JS)
   ========================================================================== */
'use strict';

const Biblio = (() => {
  const base = (window.BIBLIO && window.BIBLIO.base) || '';
  const csrf = (window.BIBLIO && window.BIBLIO.csrf) || '';

  function toast(message, type = 'success', title = '') {
    const wrap = document.getElementById('toastWrap');
    if (!wrap) return;
    const icons = { success: '<path d="M20 6L9 17l-5-5"/>', error: '<path d="M6 6l12 12M18 6L6 18"/>', info: '<circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/>' };
    const el = document.createElement('div');
    el.className = 'toast ' + type;
    el.innerHTML = '<span class="ti"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' + (icons[type] || icons.success) + '</svg></span><div>' + (title ? '<strong>' + title + '</strong>' : '') + '<span>' + message + '</span></div>';
    wrap.appendChild(el);
    setTimeout(() => { el.classList.add('hide'); setTimeout(() => el.remove(), 350); }, 3200);
  }

  async function api(path, data) {
    const opts = { method: data ? 'POST' : 'GET', headers: { 'X-Requested-With': 'XMLHttpRequest' } };
    if (data) { opts.headers['Content-Type'] = 'application/json'; opts.headers['X-CSRF-Token'] = csrf; opts.body = JSON.stringify(data); }
    const res = await fetch(base + path, opts);
    return res.json();
  }

  async function addToCart(id, btn) {
    if (btn) { btn.disabled = true; btn.style.opacity = '.7'; }
    try {
      const r = await api('/api/cart.php', { action: 'add', id });
      if (r.ok) { updateCount('cartCount', r.count); toast(r.message || 'Livre ajouté au panier', 'success', 'Panier'); pulse(document.getElementById('cartCount')); }
      else { toast(r.error || 'Erreur', 'error'); }
    } catch (e) { toast('Connexion impossible', 'error'); }
    if (btn) { btn.disabled = false; btn.style.opacity = ''; }
  }
  async function removeFromCart(id) {
    const r = await api('/api/cart.php', { action: 'remove', id });
    if (r.ok) { updateCount('cartCount', r.count); if (window.__onCartChange) window.__onCartChange(r); }
    return r;
  }

  async function toggleWish(id, btn) {
    const r = await api('/api/wishlist.php', { action: 'toggle', id });
    if (r.ok) {
      updateCount('wishCount', r.count);
      document.querySelectorAll('.wish-btn[data-id="' + id + '"]').forEach(b => b.classList.toggle('active', r.active));
      toast(r.active ? 'Ajouté à ma liste de lecture' : 'Retiré de ma liste', 'success', 'Ma liste');
    }
  }

  async function subscribe(e) {
    e.preventDefault();
    const email = e.target.querySelector('input[type=email]').value;
    const r = await api('/api/newsletter.php', { email });
    toast(r.message || (r.ok ? 'Merci !' : 'Erreur'), r.ok ? 'success' : 'error', 'Newsletter');
    if (r.ok) e.target.reset();
    return false;
  }

  async function quickView(slug) {
    const overlay = ensureModal();
    const modal = overlay.querySelector('.modal');
    modal.querySelector('.modal-body').innerHTML = skel();
    overlay.classList.add('open'); document.body.style.overflow = 'hidden';
    try { const r = await api('/api/quickview.php?slug=' + encodeURIComponent(slug)); if (r.ok) modal.querySelector('.modal-body').innerHTML = r.html; } catch (e) {}
  }
  function ensureModal() {
    let o = document.getElementById('qModal');
    if (!o) { o = document.createElement('div'); o.className = 'modal-overlay'; o.id = 'qModal'; o.innerHTML = '<div class="modal"><button class="modal-close" aria-label="Fermer">✕</button><div class="modal-body"></div></div>'; document.body.appendChild(o); o.addEventListener('click', ev => { if (ev.target === o || ev.target.closest('.modal-close')) closeModal(); }); }
    return o;
  }
  function closeModal() { const o = document.getElementById('qModal'); if (o) o.classList.remove('open'); document.body.style.overflow = ''; }
  function skel() { return '<div style="display:grid;grid-template-columns:.8fr 1fr;gap:24px;padding:24px"><div class="skeleton" style="aspect-ratio:3/4.2;border-radius:8px"></div><div><div class="skeleton" style="height:28px;width:75%;margin-bottom:14px"></div><div class="skeleton" style="height:16px;width:40%;margin-bottom:20px"></div><div class="skeleton" style="height:70px;margin-bottom:16px"></div><div class="skeleton" style="height:44px;width:60%"></div></div></div>'; }

  function updateCount(id, n) { const el = document.getElementById(id); if (!el) return; el.textContent = n; el.style.display = n > 0 ? '' : 'none'; }
  function pulse(el) { if (el) el.animate([{ transform: 'scale(1)' }, { transform: 'scale(1.4)' }, { transform: 'scale(1)' }], { duration: 400 }); }

  return { toast, api, addToCart, removeFromCart, toggleWish, subscribe, quickView, closeModal, updateCount };
})();
window.Biblio = Biblio;

document.addEventListener('DOMContentLoaded', () => {
  (window.__flash || []).forEach(f => Biblio.toast(f.message, f.type));

  const header = document.getElementById('siteHeader');
  const toTop = document.getElementById('toTop');
  const onScroll = () => { if (header) header.classList.toggle('scrolled', window.scrollY > 20); if (toTop) toTop.classList.toggle('show', window.scrollY > 500); };
  window.addEventListener('scroll', onScroll, { passive: true }); onScroll();
  if (toTop) toTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));

  const toggleTheme = () => { const d = document.documentElement.getAttribute('data-theme') === 'dark'; const n = d ? 'light' : 'dark'; document.documentElement.setAttribute('data-theme', n); try { localStorage.setItem('theme', n); } catch (e) {} };
  document.getElementById('themeBtn')?.addEventListener('click', toggleTheme);
  document.getElementById('drawerTheme')?.addEventListener('click', e => { e.preventDefault(); toggleTheme(); });

  const drawer = document.getElementById('mobileDrawer'), backdrop = document.getElementById('drawerBackdrop');
  document.getElementById('menuToggle')?.addEventListener('click', () => { drawer.classList.add('open'); backdrop.classList.add('open'); });
  backdrop?.addEventListener('click', () => { drawer.classList.remove('open'); backdrop.classList.remove('open'); });

  const sOverlay = document.getElementById('searchOverlay'), sInput = document.getElementById('searchInput');
  document.getElementById('searchBtn')?.addEventListener('click', () => { sOverlay.classList.add('open'); setTimeout(() => sInput.focus(), 100); });
  document.getElementById('searchClose')?.addEventListener('click', () => sOverlay.classList.remove('open'));
  sOverlay?.addEventListener('click', e => { if (e.target === sOverlay) sOverlay.classList.remove('open'); });
  let st;
  sInput?.addEventListener('input', () => {
    clearTimeout(st); const q = sInput.value.trim(); const box = document.getElementById('searchResults');
    if (q.length < 2) { box.innerHTML = '<p class="search-hint">Tapez au moins 2 caractères…</p>'; return; }
    box.innerHTML = '<p class="search-hint">Recherche…</p>';
    st = setTimeout(async () => {
      const r = await Biblio.api('/api/search.php?q=' + encodeURIComponent(q));
      if (r.ok && r.items.length) {
        box.innerHTML = r.items.map(p => '<a class="search-result" href="' + r.base + '/livre.php?slug=' + p.slug + '"><img src="' + p.image + '" alt=""><div style="flex:1"><div class="r-name">' + p.name + '</div><div style="font-size:.78rem;color:var(--muted)">' + p.author + '</div></div><div class="r-price">' + p.price + '</div></a>').join('') + '<a class="search-hint" href="' + r.base + '/catalogue.php?q=' + encodeURIComponent(q) + '" style="display:block;text-align:center;color:var(--coffee);font-weight:600;padding:12px">Voir tous les résultats →</a>';
      } else { box.innerHTML = '<p class="search-hint">Aucun livre pour « ' + q + ' »</p>'; }
    }, 250);
  });

  document.addEventListener('click', e => {
    const add = e.target.closest('.add-cart-btn'); if (add) { e.preventDefault(); Biblio.addToCart(parseInt(add.dataset.id), add); return; }
    const wish = e.target.closest('.wish-btn'); if (wish) { e.preventDefault(); Biblio.toggleWish(parseInt(wish.dataset.id), wish); return; }
    const qv = e.target.closest('.quickview-btn'); if (qv) { e.preventDefault(); Biblio.quickView(qv.dataset.slug); return; }
  });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') { sOverlay?.classList.remove('open'); Biblio.closeModal(); drawer?.classList.remove('open'); backdrop?.classList.remove('open'); } });

  const cd = document.getElementById('countdown');
  if (cd) {
    const deadline = parseInt(cd.dataset.deadline) * 1000;
    const tick = () => { let diff = Math.max(0, deadline - Date.now()); const d = Math.floor(diff / 86400000); diff -= d * 86400000; const h = Math.floor(diff / 3600000); diff -= h * 3600000; const m = Math.floor(diff / 60000); diff -= m * 60000; const s = Math.floor(diff / 1000); const p = n => String(n).padStart(2, '0'); cd.querySelector('[data-d]').textContent = p(d); cd.querySelector('[data-h]').textContent = p(h); cd.querySelector('[data-m]').textContent = p(m); cd.querySelector('[data-s]').textContent = p(s); };
    tick(); setInterval(tick, 1000);
  }

  const reveals = document.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver(es => es.forEach(en => { if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); } }), { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
    reveals.forEach(el => io.observe(el));
  } else { reveals.forEach(el => el.classList.add('in')); }

  document.querySelectorAll('.tab-nav button').forEach(b => b.addEventListener('click', () => {
    b.parentElement.querySelectorAll('button').forEach(x => x.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(x => x.classList.remove('active'));
    b.classList.add('active'); document.querySelector('[data-panel="' + b.dataset.tab + '"]')?.classList.add('active');
  }));
});
