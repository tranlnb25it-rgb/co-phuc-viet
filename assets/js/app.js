/* Cổ Phục Việt — front-end behaviour. Pages are rendered by PHP; this file only adds Ajax and small UI touches. */
(function () {
  'use strict';

  const BASE = (document.body.dataset.base || '/').replace(/\/$/, '');

  /**
   * Ajax GET to the PHP API. Returns parsed JSON or throws an Error with the server's message.
   * Usage: ajax('/api/products', { featured: 1, format: 'html' })
   */
  async function ajax(path, params = {}, options = {}) {
    const qs = new URLSearchParams();
    Object.entries(params).forEach(([k, v]) => {
      if (v !== undefined && v !== null && v !== '') qs.set(k, v);
    });
    const res = await fetch(BASE + path + (qs.toString() ? '?' + qs : ''), {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
      ...options,
    });
    const data = await res.json().catch(() => null);
    if (!res.ok) throw new Error((data && data.error) || res.statusText);
    return data;
  }
  /** Headers for JSON requests that change state: PHP rejects them without the session's CSRF token. */
  const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const jsonHeaders = () => ({
    'Content-Type': 'application/json', Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': CSRF,
  });

  /** POST/PATCH/DELETE JSON to the PHP API. Resolves {ok, status, data}; never throws on 4xx. */
  async function send(method, path, body) {
    const res = await fetch(path.startsWith('http') || path.startsWith(BASE + '/') ? path : BASE + path, {
      method, headers: jsonHeaders(), credentials: 'same-origin',
      body: body === undefined ? undefined : JSON.stringify(body),
    });
    const data = await res.json().catch(() => ({ error: res.statusText }));
    return { ok: res.ok, status: res.status, data };
  }
  window.CPV = { ajax, send, BASE };

  // ---------- Cart drawer (contents are rendered by PHP, swapped in via Ajax) ----------
  const drawer = document.querySelector('[data-cart-drawer]');
  const cart = {
    open() {
      if (!drawer) return;
      drawer.classList.remove('pointer-events-none');
      drawer.setAttribute('aria-hidden', 'false');
      drawer.querySelector('[data-cart-backdrop]').classList.replace('opacity-0', 'opacity-100');
      drawer.querySelector('[data-cart-panel]').classList.replace('translate-x-full', 'translate-x-0');
      document.body.classList.add('overflow-hidden');
      setTimeout(() => drawer.querySelector('[data-cart-close]')?.focus(), 50);
    },
    close() {
      if (!drawer || drawer.getAttribute('aria-hidden') === 'true') return;
      drawer.classList.add('pointer-events-none');
      drawer.setAttribute('aria-hidden', 'true');
      drawer.querySelector('[data-cart-backdrop]').classList.replace('opacity-100', 'opacity-0');
      drawer.querySelector('[data-cart-panel]').classList.replace('translate-x-0', 'translate-x-full');
      document.body.classList.remove('overflow-hidden');
    },
    /** Apply a /api/cart response: new drawer HTML, header badge, optional toast. */
    apply(data) {
      if (!drawer) return;
      drawer.querySelector('[data-cart-content]').innerHTML = data.html;
      document.querySelectorAll('[data-cart-count]').forEach((el) => {
        el.textContent = data.count || '';
        el.classList.toggle('hidden', !data.count);
      });
      if (data.message) this.toast(data.message);
      document.dispatchEvent(new CustomEvent('cpv:cart-updated', { detail: data }));
    },
    toast(text) {
      const t = drawer.querySelector('[data-cart-toast]');
      t.textContent = text;
      t.classList.remove('hidden');
      clearTimeout(this._toast);
      this._toast = setTimeout(() => t.classList.add('hidden'), 2600);
    },
    busy(on) { drawer?.querySelector('[data-cart-content]').classList.toggle('opacity-50', on); },
  };

  if (drawer) {
    // Header cart icon is a link to /thanh-toan without JS; with JS it opens the drawer.
    document.addEventListener('click', (e) => {
      if (e.target.closest('[data-cart-open]')) { e.preventDefault(); cart.open(); }
      else if (e.target.closest('[data-cart-close]') && drawer.contains(e.target)) cart.close();
    });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') cart.close(); });

    // Quantity / remove buttons inside the drawer
    drawer.addEventListener('click', async (e) => {
      const qtyBtn = e.target.closest('[data-cart-qty]');
      const rmBtn = e.target.closest('[data-cart-remove]');
      if (!qtyBtn && !rmBtn) return;
      const onCheckout = !!document.querySelector('[data-order-summary]');
      cart.busy(true);
      try {
        const { ok, data } = qtyBtn
          ? await send('PATCH', '/api/cart/' + encodeURIComponent(qtyBtn.dataset.cartQty), { qty: +qtyBtn.dataset.qtyValue, summary: onCheckout })
          : await send('DELETE', '/api/cart/' + encodeURIComponent(rmBtn.dataset.cartRemove) + (onCheckout ? '?view=summary' : ''));
        if (ok) cart.apply(data); else cart.toast(data.error);
      } finally {
        cart.busy(false);
      }
    });
  }

  // ---------- Header shadow on scroll ----------
  const header = document.getElementById('site-header');
  if (header) {
    const onScroll = () => header.classList.toggle('shadow-md', window.scrollY > 8);
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  // ---------- Mobile menu ----------
  const menu = document.querySelector('[data-menu]');
  const setMenu = (open) => {
    if (!menu) return;
    menu.classList.toggle('hidden', !open);
    document.body.classList.toggle('overflow-hidden', open);
  };
  document.querySelectorAll('[data-menu-open]').forEach((b) => b.addEventListener('click', () => setMenu(true)));
  document.querySelectorAll('[data-menu-close]').forEach((b) => b.addEventListener('click', () => setMenu(false)));
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') setMenu(false); });

  // ---------- Scroll reveal ----------
  const revealEls = document.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach((en) => {
        if (en.isIntersecting) { en.target.classList.add('is-visible'); io.unobserve(en.target); }
      });
    }, { rootMargin: '0px 0px -10% 0px' });
    revealEls.forEach((el) => io.observe(el));
  } else {
    revealEls.forEach((el) => el.classList.add('is-visible'));
  }

  // ---------- Home: hero slideshow (photos rendered by PHP, this only cross-fades them) ----------
  const heroSlider = document.querySelector('[data-hero-slider]');
  if (heroSlider) {
    const bgs = [...heroSlider.querySelectorAll('[data-hero-bg]')];
    const frames = [...heroSlider.querySelectorAll('[data-hero-frame]')];
    const dots = [...heroSlider.querySelectorAll('[data-hero-dot]')];
    const caption = heroSlider.querySelector('[data-hero-caption]');
    const n = bgs.length;
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let cur = 0;
    let timer = null;

    const fade = (img, on) => { img.classList.toggle('opacity-100', on); img.classList.toggle('opacity-0', !on); };
    const show = (i) => {
      cur = (i + n) % n;
      bgs.forEach((img, k) => fade(img, k === cur));
      // left arch = previous photo, centre = current, right = next
      frames.forEach((frame) => {
        const idx = (cur + +frame.dataset.heroFrame + n) % n;
        frame.querySelectorAll('img').forEach((img, k) => {
          fade(img, k === idx);
          img.alt = frame.dataset.heroFrame === '0' && k === idx ? dots[k].dataset.caption : '';
        });
      });
      dots.forEach((d, k) => d.setAttribute('aria-pressed', k === cur ? 'true' : 'false'));
      if (caption) caption.textContent = dots[cur].dataset.caption;
    };
    const stop = () => { clearInterval(timer); timer = null; };
    const start = () => {
      stop();
      if (!reduceMotion && !document.hidden) timer = setInterval(() => show(cur + 1), +heroSlider.dataset.interval || 5500);
    };

    dots.forEach((d) => d.addEventListener('click', () => { show(+d.dataset.heroDot); start(); }));
    // pause while the visitor is looking at / interacting with the hero, or the tab is in the background
    heroSlider.addEventListener('mouseenter', stop);
    heroSlider.addEventListener('mouseleave', start);
    heroSlider.addEventListener('focusin', stop);
    heroSlider.addEventListener('focusout', start);
    document.addEventListener('visibilitychange', () => (document.hidden ? stop() : start()));
    start();
  }

  // ---------- Home: featured products by dynasty (Ajax, server-rendered cards) ----------
  const tabs = document.querySelector('[data-featured-tabs]');
  const grid = document.getElementById('featured-grid');
  if (tabs && grid) {
    const cache = { '': grid.innerHTML };   // first tab is already rendered by PHP
    let controller = null;

    tabs.addEventListener('click', async (e) => {
      const btn = e.target.closest('[data-dynasty]');
      if (!btn || btn.classList.contains('is-active')) return;

      tabs.querySelectorAll('[data-dynasty]').forEach((b) => {
        const on = b === btn;
        b.classList.toggle('is-active', on);
        b.setAttribute('aria-selected', on ? 'true' : 'false');
      });

      const dynasty = btn.dataset.dynasty;
      if (cache[dynasty] !== undefined) { grid.innerHTML = cache[dynasty]; return; }

      if (controller) controller.abort();
      controller = new AbortController();
      grid.classList.add('opacity-40', 'pointer-events-none');
      try {
        const data = await ajax('/api/products', { featured: 1, dynasty, per_page: 8, format: 'html' },
          { signal: controller.signal });
        cache[dynasty] = data.html;
        grid.innerHTML = data.html;
      } catch (err) {
        if (err.name === 'AbortError') return;
        grid.innerHTML = '<p class="col-span-full py-16 text-center text-crimson">' + grid.dataset.error + '</p>';
      } finally {
        grid.classList.remove('opacity-40', 'pointer-events-none');
      }
    });
  }

  // ---------- Catalog: filters, real-time search, sort, pagination (Ajax) ----------
  const form = document.getElementById('catalog-filters');
  const results = document.getElementById('catalog-results');
  if (form && results) {
    const $ = (sel) => document.querySelector(sel);
    const search = $('[data-catalog-search]');
    const spinner = $('[data-catalog-spinner]');
    const panel = $('[data-filter-panel]');
    let controller = null;
    let debounce = null;

    /** Current form state → URLSearchParams (empty values dropped, so URLs stay clean). */
    const formParams = () => {
      const params = new URLSearchParams();
      for (const [k, v] of new FormData(form)) if (v !== '') params.append(k, v);
      return params;
    };

    /** Put URL params back into the form (used for chips, pagination links and Back/Forward). */
    const syncForm = (params) => {
      form.querySelectorAll('input[type=radio]').forEach((el) => {
        el.checked = (params.get(el.name) || '') === el.value;
      });
      form.querySelectorAll('input[type=checkbox]').forEach((el) => {
        el.checked = params.getAll(el.name).includes(el.value);
      });
      if (search) search.value = params.get('q') || '';
      const sort = document.querySelector('select[name=sort]');
      if (sort) sort.value = params.get('sort') || 'newest';
    };

    /**
     * Fetch the server-rendered results block and swap it in.
     * mode: 'push' (new history entry), 'replace' (typing), 'none' (Back/Forward).
     */
    const load = async (params, mode = 'push', scroll = false) => {
      if (controller) controller.abort();
      controller = new AbortController();
      results.classList.add('opacity-50');
      spinner && spinner.classList.replace('hidden', 'flex');

      const query = Object.fromEntries([...params.keys()].map((k) => [k, params.getAll(k)]));
      const qs = new URLSearchParams(params);
      qs.set('format', 'catalog');

      try {
        const res = await fetch(BASE + '/api/products?' + qs, {
          headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          signal: controller.signal,
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || res.statusText);

        results.innerHTML = data.html;
        document.querySelectorAll('[data-catalog-count]').forEach((el) => (el.textContent = data.count_text));
        document.querySelectorAll('[data-catalog-total]').forEach((el) => (el.textContent = data.total));
        $('[data-catalog-title]').textContent = data.title;
        $('[data-catalog-desc]').textContent = data.description;
        document.title = data.title + (results.dataset.titleSuffix || '');
        const badge = $('[data-filter-badge]');
        if (badge) { badge.textContent = data.active_count; badge.classList.toggle('hidden', !data.active_count); }

        if (mode === 'push') history.pushState(query, '', data.url);
        if (mode === 'replace') history.replaceState(query, '', data.url);
        if (scroll && results.getBoundingClientRect().top < 0) results.scrollIntoView({ behavior: 'smooth' });
      } catch (err) {
        if (err.name === 'AbortError') return;
        results.innerHTML = '<p class="py-16 text-center text-crimson">' + results.dataset.error + '</p>';
      } finally {
        results.classList.remove('opacity-50');
        spinner && spinner.classList.replace('flex', 'hidden');
      }
    };

    // Any filter / sort change → reload from page 1
    form.addEventListener('change', () => load(formParams()));
    document.querySelector('select[name=sort]')?.addEventListener('change', () => load(formParams()));

    // Real-time search, debounced; typing replaces the history entry instead of adding one per key
    search?.addEventListener('input', () => {
      clearTimeout(debounce);
      debounce = setTimeout(() => load(formParams(), 'replace'), 300);
    });
    search?.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') { e.preventDefault(); clearTimeout(debounce); load(formParams()); }
    });
    form.addEventListener('submit', (e) => { e.preventDefault(); load(formParams()); });

    // Chips, "clear all" and pagination links inside the results block
    results.addEventListener('click', (e) => {
      const a = e.target.closest('a[data-ajax-link]');
      if (!a || e.metaKey || e.ctrlKey || e.shiftKey) return;
      e.preventDefault();
      const params = new URL(a.href).searchParams;
      syncForm(params);
      load(params, 'push', true);
    });

    // Back / Forward
    window.addEventListener('popstate', () => {
      const params = new URLSearchParams(location.search);
      syncForm(params);
      load(params, 'none');
    });

    // Mobile slide-over filter panel
    const setPanel = (open) => {
      if (!panel) return;
      panel.classList.toggle('hidden', !open);
      document.body.classList.toggle('overflow-hidden', open);
    };
    document.querySelectorAll('[data-filter-open]').forEach((b) => b.addEventListener('click', () => setPanel(true)));
    document.querySelectorAll('[data-filter-close]').forEach((b) => b.addEventListener('click', () => setPanel(false)));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') setPanel(false); });
  }

  // ---------- Dialogs (size guide) ----------
  document.querySelectorAll('[data-dialog-open]').forEach((btn) => {
    const dlg = document.getElementById(btn.dataset.dialogOpen);
    if (!dlg || typeof dlg.showModal !== 'function') return;
    btn.addEventListener('click', () => dlg.showModal());
    dlg.addEventListener('click', (e) => { if (e.target === dlg || e.target.closest('[data-dialog-close]')) dlg.close(); });
  });

  // ---------- Product page: gallery ----------
  const gallery = document.querySelector('[data-gallery]');
  if (gallery) {
    const slides = [...gallery.querySelectorAll('[data-gallery-slide]')];
    const thumbs = [...gallery.querySelectorAll('[data-gallery-thumb]')];
    let current = 0;

    const show = (i) => {
      current = (i + slides.length) % slides.length;
      slides.forEach((s, n) => {
        const on = n === current;
        s.classList.toggle('opacity-0', !on);
        s.classList.toggle('pointer-events-none', !on);
        s.toggleAttribute('aria-hidden', !on);
      });
      thumbs.forEach((t, n) => {
        const on = n === current;
        t.setAttribute('aria-selected', on ? 'true' : 'false');
        t.classList.toggle('border-crimson', on);
        t.classList.toggle('border-transparent', !on);
      });
    };
    thumbs.forEach((t) => t.addEventListener('click', () => show(+t.dataset.galleryThumb)));
    gallery.querySelector('[data-gallery-prev]')?.addEventListener('click', () => show(current - 1));
    gallery.querySelector('[data-gallery-next]')?.addEventListener('click', () => show(current + 1));
    gallery.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowLeft') show(current - 1);
      if (e.key === 'ArrowRight') show(current + 1);
    });

    // Hover zoom (desktop pointers only) — follows the cursor for embroidery close-ups
    const stage = gallery.querySelector('[data-gallery-stage]');
    if (stage && window.matchMedia('(hover: hover)').matches) {
      stage.addEventListener('mousemove', (e) => {
        const r = stage.getBoundingClientRect();
        const z = slides[current].querySelector('[data-zoom]');
        z.style.transformOrigin = `${((e.clientX - r.left) / r.width) * 100}% ${((e.clientY - r.top) / r.height) * 100}%`;
        z.style.transform = 'scale(1.6)';
      });
      stage.addEventListener('mouseleave', () => {
        slides.forEach((s) => { const z = s.querySelector('[data-zoom]'); z.style.transform = ''; });
      });
    }
  }

  // ---------- Product page: options, live price (Ajax quote), custom tailoring ----------
  const pform = document.getElementById('product-form');
  if (pform) {
    const q = (sel) => document.querySelector(sel);
    const customPanel = q('[data-custom-panel]');
    const addBtn = q('[data-add-to-cart]');
    const message = q('[data-form-message]');
    const qtyInput = pform.querySelector('input[name=qty]');
    const dotClasses = { in_stock: 'bg-emerald-600', low: 'bg-gold', out: 'bg-crimson', made_to_order: 'bg-ink' };
    let controller = null;
    let timer = null;

    /** Form → JSON body for the quote endpoint. */
    const payload = (check = false) => {
      const fd = new FormData(pform);
      const measurements = {};
      for (const [k, v] of fd) {
        const m = k.match(/^measurements\[(\w+)\]$/);
        if (m) measurements[m[1]] = v;
      }
      return {
        fabric: fd.get('fabric'), color: fd.get('color'), size: fd.get('size'),
        qty: fd.get('qty'), measurements, check,
      };
    };

    const isCustom = () => (new FormData(pform).get('size') || '') === 'CUSTOM';

    const showMessage = (text, ok) => {
      message.textContent = text;
      message.classList.remove('hidden', 'text-crimson', 'text-emerald-700');
      message.classList.add(ok ? 'text-emerald-700' : 'text-crimson');
    };
    const clearErrors = () => {
      pform.querySelectorAll('[data-error-for]').forEach((el) => (el.textContent = ''));
      pform.querySelectorAll('[aria-invalid]').forEach((el) => el.removeAttribute('aria-invalid'));
    };

    /** Apply a quote returned by PHP to the page. */
    const render = (data) => {
      q('[data-quote-unit]').textContent = data.unit_price_text;
      const orig = q('[data-quote-original]');
      if (orig) { orig.textContent = data.original_unit_price_text || ''; orig.classList.toggle('hidden', !data.on_sale); }
      q('[data-quote-breakdown]').innerHTML = data.breakdown_html;
      q('[data-status-text]').textContent = data.status_text;
      const dot = q('[data-status-dot]');
      Object.values(dotClasses).forEach((c) => dot.classList.remove(c));
      dot.classList.add(dotClasses[data.status]);
      if (data.color_name) q('[data-color-name]').textContent = data.color_name;
      addBtn.disabled = !data.available;

      // strike through sizes that are out of stock for this fabric + colour
      document.querySelectorAll('[data-size-chip]').forEach((chip) => {
        chip.toggleAttribute('data-oos', (data.size_stock[chip.dataset.sizeChip] || 0) <= 0);
      });

      const suggest = q('[data-suggest]');
      if (suggest) {
        const s = data.suggested_size;
        suggest.classList.toggle('hidden', !s);
        suggest.classList.toggle('flex', !!s);
        if (s) suggest.textContent = suggest.dataset.template.replace('__SIZE__', s);
      }
    };

    const requestQuote = async (check = false) => {
      if (controller) controller.abort();
      controller = new AbortController();
      const res = await fetch(pform.dataset.quoteUrl, {
        method: 'POST',
        headers: jsonHeaders(),
        body: JSON.stringify(payload(check)),
        signal: controller.signal,
      });
      const data = await res.json();
      return { ok: res.ok, data };
    };

    const refresh = async () => {
      try {
        const { ok, data } = await requestQuote(false);
        if (ok) render(data);
      } catch (err) {
        if (err.name !== 'AbortError') console.error(err);
      }
    };
    const refreshSoon = (delay = 150) => { clearTimeout(timer); timer = setTimeout(refresh, delay); };

    pform.addEventListener('change', (e) => {
      if (customPanel && e.target.name === 'size') customPanel.classList.toggle('hidden', !isCustom());
      message.classList.add('hidden');
      refreshSoon(0);
    });
    // measurements typed → update the size suggestion
    pform.addEventListener('input', (e) => {
      if (e.target.name && e.target.name.startsWith('measurements[')) {
        e.target.removeAttribute('aria-invalid');
        refreshSoon(400);
      }
    });

    // Quantity stepper
    pform.querySelectorAll('[data-qty]').forEach((b) => b.addEventListener('click', () => {
      const max = +qtyInput.max || 10;
      qtyInput.value = Math.min(max, Math.max(1, (+qtyInput.value || 1) + +b.dataset.qty));
      qtyInput.dispatchEvent(new Event('change', { bubbles: true }));
    }));

    // Add to cart: PHP validates the selection (stock, measurements) and stores it in the session cart
    pform.addEventListener('submit', async (e) => {
      e.preventDefault();
      clearErrors();
      message.classList.add('hidden');
      const label = addBtn.querySelector('span');
      addBtn.disabled = true;
      label.textContent = addBtn.dataset.busyLabel;
      try {
        const { ok, data } = await send('POST', '/api/cart', { ...payload(), slug: pform.dataset.slug });
        if (!ok) {
          const details = data.details || {};
          Object.entries(details).forEach(([field, msg]) => {
            const slot = pform.querySelector(`[data-error-for="${field}"]`);
            if (slot) slot.textContent = msg;
            pform.querySelector(`[name="measurements[${field}]"]`)?.setAttribute('aria-invalid', 'true');
          });
          showMessage(details._stock || data.error, false);
          pform.querySelector('[aria-invalid=true]')?.focus();
          return;
        }
        cart.apply(data);
        cart.open();
      } catch (err) {
        showMessage(err.message, false);
      } finally {
        label.textContent = addBtn.dataset.label;
        addBtn.disabled = false;
        refreshSoon(0);   // re-sync the button state with current availability
      }
    });
  }

  // ---------- Checkout: Ajax submit with field errors; summary follows cart edits ----------
  const cform = document.getElementById('checkout-form');
  if (cform) {
    const btn = cform.querySelector('[data-place-order]');
    const label = btn.querySelector('span');
    const errorBox = cform.querySelector('[data-checkout-error]');

    const setError = (text) => {
      errorBox.querySelector('span').textContent = text || '';
      errorBox.classList.toggle('hidden', !text);
      errorBox.classList.toggle('flex', !!text);
    };

    cform.addEventListener('input', (e) => {
      if (!e.target.name) return;
      e.target.removeAttribute('aria-invalid');
      const slot = cform.querySelector(`[data-error-for="${e.target.name}"]`);
      if (slot) slot.textContent = '';
    });

    cform.addEventListener('submit', async (e) => {
      e.preventDefault();
      setError('');
      cform.querySelectorAll('[data-error-for]').forEach((el) => (el.textContent = ''));
      cform.querySelectorAll('[aria-invalid]').forEach((el) => el.removeAttribute('aria-invalid'));

      btn.disabled = true;
      label.textContent = btn.dataset.busyLabel;
      try {
        const body = Object.fromEntries(new FormData(cform));
        const { ok, data } = await send('POST', cform.dataset.api, body);
        if (ok) { window.location.href = data.redirect; return; }

        const details = data.details || {};
        Object.entries(details).forEach(([field, msg]) => {
          const slot = cform.querySelector(`[data-error-for="${field}"]`);
          if (slot) slot.textContent = msg;
          cform.querySelector(`[name="${field}"]`)?.setAttribute('aria-invalid', 'true');
        });
        setError(details._cart || (Object.keys(details).length ? '' : data.error));
        (cform.querySelector('[aria-invalid=true]') || errorBox).scrollIntoView({ behavior: 'smooth', block: 'center' });
        cform.querySelector('[aria-invalid=true]')?.focus({ preventScroll: true });
      } catch (err) {
        setError(err.message);
      } finally {
        label.textContent = btn.dataset.label;
        btn.disabled = false;
      }
    });

    // Cart edited in the drawer while on checkout → refresh the sidebar and total
    document.addEventListener('cpv:cart-updated', (e) => {
      const d = e.detail;
      if (!d.count) { window.location.reload(); return; }   // emptied: show the empty state
      if (d.summary_html) document.querySelector('[data-order-summary]').innerHTML = d.summary_html;
      const total = cform.querySelector('[data-order-total]');
      if (total) total.textContent = new Intl.NumberFormat('vi-VN').format(d.total) + ' ₫';
    });
  }

  // ---------- Sale countdown (product page) ----------
  document.querySelectorAll('[data-countdown]').forEach((box) => {
    const end = Date.parse(box.dataset.countdown);
    const units = JSON.parse(box.dataset.units || '{}');
    const out = box.querySelector('[data-countdown-text]');
    if (!end || !out) return;
    const tick = () => {
      let s = Math.max(0, Math.floor((end - Date.now()) / 1000));
      if (s === 0) { box.classList.add('hidden'); clearInterval(timer); return; }
      const d = Math.floor(s / 86400); s %= 86400;
      const h = Math.floor(s / 3600); s %= 3600;
      const m = Math.floor(s / 60); s %= 60;
      const pad = (n) => String(n).padStart(2, '0');
      out.textContent = (d ? `${d} ${units.d} ` : '') + `${pad(h)} ${units.h} ${pad(m)} ${units.m} ${pad(s)} ${units.s}`;
    };
    const timer = setInterval(tick, 1000);
    tick();
  });

  // ---------- Coupon code (checkout summary) ----------
  document.addEventListener('submit', async (e) => {
    const form = e.target.closest('[data-coupon-form]');
    if (!form) return;
    e.preventDefault();
    const box = form.closest('[data-coupon-box]');
    const input = form.querySelector('[name=code]');
    const err = box.querySelector('[data-error-for="coupon"]');
    const btn = form.querySelector('button');
    btn.disabled = true;
    err.textContent = '';
    input.removeAttribute('aria-invalid');
    try {
      const { ok, data } = await send('POST', box.dataset.applyUrl, { code: input.value });
      if (!ok) {
        err.textContent = (data.details && data.details.coupon) || data.error;
        input.setAttribute('aria-invalid', 'true');
        input.focus();
        return;
      }
      cart.apply(data);   // re-renders drawer + checkout summary (via cpv:cart-updated)
    } finally {
      btn.disabled = false;
    }
  });
  document.addEventListener('click', async (e) => {
    const rm = e.target.closest('[data-coupon-remove]');
    if (!rm) return;
    const box = rm.closest('[data-coupon-box]');
    rm.disabled = true;
    const { ok, data } = await send('DELETE', box.dataset.applyUrl);
    if (ok) cart.apply(data); else rm.disabled = false;
  });

  // =====================================================================
  //  Phase 9 — customer accounts, wishlist, order lookup
  // =====================================================================

  // ---------- Site toast (also shows one-off PHP flash messages) ----------
  const siteToast = document.querySelector('[data-site-toast]');
  const toast = (text) => {
    if (!siteToast || !text) return;
    siteToast.textContent = text;
    siteToast.classList.remove('hidden');
    clearTimeout(toast.timer);
    toast.timer = setTimeout(() => siteToast.classList.add('hidden'), 2800);
  };
  if (siteToast && !siteToast.classList.contains('hidden')) toast.timer = setTimeout(() => siteToast.classList.add('hidden'), 3500);

  /** Clear / show PHP validation errors in a form ([data-error-for] slots + aria-invalid). */
  const clearFormErrors = (form) => {
    form.querySelectorAll('[data-error-for]').forEach((el) => (el.textContent = ''));
    form.querySelectorAll('[aria-invalid]').forEach((el) => el.removeAttribute('aria-invalid'));
    const box = errorBoxOf(form);
    if (box) { box.textContent = ''; box.classList.add('hidden'); }
  };
  const errorBoxOf = (form) => (form.dataset.errorBox ? document.querySelector(form.dataset.errorBox) : form.querySelector('[data-form-error]'));
  const showFormErrors = (form, data) => {
    const details = data.details || {};
    let matched = 0;
    Object.entries(details).forEach(([field, msg]) => {
      const slot = form.querySelector(`[data-error-for="${field}"]`);
      if (slot) { slot.textContent = msg; matched++; }
      const input = form.querySelector(`[name="${field}"]`);
      if (input) input.setAttribute('aria-invalid', 'true');
    });
    const general = details._login || details._lookup || (matched ? '' : data.error);
    const box = errorBoxOf(form);
    if (box && general) { box.textContent = general; box.classList.remove('hidden'); }
    else if (general) toast(general);
    form.querySelector('[aria-invalid=true]')?.focus();
  };
  /** Button busy state: swaps the <span> label for data-busy-label while waiting. */
  const busy = (btn, on) => {
    if (!btn) return;
    btn.disabled = on;
    const span = btn.querySelector('span');
    if (span && btn.dataset.busyLabel) span.textContent = on ? btn.dataset.busyLabel : (btn.dataset.label || span.textContent);
  };
  /** Swap server-rendered HTML into a target (inner by default, or the whole element). */
  const swap = (selector, html, mode) => {
    const target = selector && document.querySelector(selector);
    if (!target || html === undefined) return;
    if (mode === 'outer') target.outerHTML = html; else target.innerHTML = html;
  };

  // ---------- Generic Ajax forms: sign in, register, profile, password, addresses, measurements, lookup ----------
  document.addEventListener('submit', async (e) => {
    const form = e.target.closest('[data-ajax-form]');
    if (!form) return;
    e.preventDefault();
    clearFormErrors(form);
    const btn = form.querySelector('button:not([type=button])');
    if (btn && !btn.dataset.label) btn.dataset.label = btn.querySelector('span')?.textContent || '';
    busy(btn, true);

    const body = {};
    for (const [k, v] of new FormData(form)) body[k] = v;
    try {
      const { ok, status, data } = await send(form.dataset.method || 'POST', form.dataset.api || form.dataset.url || form.action, body);
      if (!ok) {
        if (status === 401) { window.location.href = BASE + '/dang-nhap?next=' + encodeURIComponent(location.pathname.replace(BASE, '')); return; }
        showFormErrors(form, data);
        return;
      }
      if (data.redirect) { window.location.href = data.redirect; return; }
      swap(form.dataset.target, data.html);
      if (data.hello) document.querySelectorAll('[data-account-name]').forEach((el) => (el.textContent = data.hello));
      if (form.hasAttribute('data-reset')) form.reset();
      form.closest('dialog')?.close();
      if (form.hasAttribute('data-scroll-result')) document.querySelector(form.dataset.target)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
      toast(data.message);
    } catch (err) {
      toast(err.message);
    } finally {
      busy(btn, false);
    }
  });

  // ---------- Buttons that call the API and re-render a block (delete, set default, cancel order) ----------
  document.addEventListener('click', async (e) => {
    const el = e.target.closest('[data-ajax-action]');
    if (!el) return;
    if (el.dataset.confirm && !window.confirm(el.dataset.confirm)) return;
    const [method, url] = el.dataset.ajaxAction.split(' ');
    if (!el.dataset.label) el.dataset.label = el.querySelector('span')?.textContent || '';
    busy(el, true);
    try {
      const { ok, data } = await send(method, url);
      if (!ok) { toast(data.error); return; }
      swap(el.dataset.target, data.html, el.dataset.swap);
      toast(data.message);
    } finally {
      if (el.isConnected) busy(el, false);
    }
  });

  // ---------- Add / edit dialogs: one <dialog> form, filled from the clicked button ----------
  document.addEventListener('click', (e) => {
    const opener = e.target.closest('[data-form-dialog]');
    if (opener) {
      const dlg = document.getElementById(opener.dataset.formDialog);
      const form = dlg?.querySelector('form');
      if (!form || typeof dlg.showModal !== 'function') return;
      form.reset();
      clearFormErrors(form);
      form.dataset.url = opener.dataset.url;
      form.dataset.method = opener.dataset.method || 'POST';
      const heading = dlg.querySelector('[data-dialog-heading]');
      if (heading && opener.dataset.heading) heading.textContent = opener.dataset.heading;
      const values = opener.dataset.values ? JSON.parse(opener.dataset.values) : {};
      Object.entries(values).forEach(([k, v]) => {
        const input = form.elements[k];
        if (!input) return;
        if (input.type === 'checkbox') input.checked = !!v; else input.value = v ?? '';
      });
      dlg.showModal();
      form.querySelector('input:not([type=hidden]):not([type=checkbox])')?.focus();
      return;
    }
    // close buttons and backdrop clicks for any dialog
    const closer = e.target.closest('dialog [data-dialog-close]');
    if (closer) closer.closest('dialog').close();
    else if (e.target.tagName === 'DIALOG') e.target.close();
  });

  // (show / hide password buttons are added to every password field by common.js)

  // ---------- My account: switch sections over Ajax (PHP renders each section) ----------
  const accBody = document.querySelector('[data-account-body]');
  if (accBody) {
    const navOn = ['border-crimson', 'bg-crimson', 'text-ivory'];
    const navOff = ['border-line', 'bg-white/60', 'hover:border-gold', 'hover:text-crimson', 'lg:border-transparent', 'lg:bg-transparent'];
    const setActive = (section) => {
      document.querySelectorAll('[data-account-nav] [data-account-link]').forEach((a) => {
        const on = a.dataset.accountLink === section;
        navOn.forEach((c) => a.classList.toggle(c, on));
        navOff.forEach((c) => a.classList.toggle(c, !on));
        if (on) a.setAttribute('aria-current', 'page'); else a.removeAttribute('aria-current');
      });
    };
    let ctrl = null;
    const loadSection = async (href, push = true) => {
      const u = new URL(href, location.href);
      u.searchParams.set('fragment', '1');
      if (ctrl) ctrl.abort();
      ctrl = new AbortController();
      accBody.classList.add('opacity-50');
      try {
        const res = await fetch(u, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, signal: ctrl.signal });
        if (!res.ok) { window.location.href = href; return; }   // e.g. session expired → let PHP redirect
        const data = await res.json();
        accBody.innerHTML = data.html;
        document.title = data.title;
        setActive(data.section);
        if (push) history.pushState({ account: true }, '', href);
        if (accBody.getBoundingClientRect().top < 0) accBody.scrollIntoView({ behavior: 'smooth' });
      } catch (err) {
        if (err.name !== 'AbortError') window.location.href = href;
      } finally {
        accBody.classList.remove('opacity-50');
      }
    };
    document.addEventListener('click', (e) => {
      const a = e.target.closest('a[data-account-link]');
      if (!a || a.hasAttribute('data-full-load') || e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) return;
      e.preventDefault();
      loadSection(a.href);
    });
    window.addEventListener('popstate', () => {
      if (location.pathname.startsWith(BASE + '/tai-khoan')) loadSection(location.href, false);
    });

    // Order list: tabs + pagination
    document.addEventListener('click', async (e) => {
      const a = e.target.closest('[data-order-list] a[data-order-tab], [data-order-list] a[data-order-page]');
      if (!a || e.metaKey || e.ctrlKey) return;
      e.preventDefault();
      const box = a.closest('[data-order-list]');
      box.classList.add('opacity-50');
      try {
        const params = Object.fromEntries(new URL(a.href).searchParams);
        const data = await ajax(box.dataset.orderList.replace(BASE, ''), params);
        box.innerHTML = data.html;
        history.pushState({ account: true }, '', data.url);
      } catch (err) {
        toast(err.message);
      } finally {
        box.classList.remove('opacity-50');
      }
    });
  }

  // ---------- Wishlist hearts (cards, product page, wishlist page) ----------
  document.addEventListener('click', async (e) => {
    const b = e.target.closest('[data-wishlist]');
    if (!b) return;
    e.preventDefault();
    const id = b.dataset.wishlist;
    const page = document.querySelector('[data-wishlist-page]');
    b.disabled = true;
    try {
      const { ok, data } = await send('POST', '/api/wishlist/' + id, page ? { view: 'page' } : {});
      if (!ok) { toast(data.error); return; }
      document.querySelectorAll(`[data-wishlist="${id}"]`).forEach((x) => {
        x.setAttribute('aria-pressed', data.saved ? 'true' : 'false');
        const label = data.saved ? x.dataset.labelRemove : x.dataset.labelAdd;
        x.setAttribute('aria-label', label);
        x.title = label;
      });
      b.animate?.([{ transform: 'scale(1.35)' }, { transform: 'scale(1)' }], { duration: 280, easing: 'ease-out' });
      document.querySelectorAll('[data-wishlist-count]').forEach((el) => {
        el.textContent = data.count || '';
        el.classList.toggle('hidden', !data.count);
      });
      if (page && data.html !== undefined) page.innerHTML = data.html;
      toast(data.message);
    } finally {
      b.disabled = false;
    }
  });

  // ---------- Checkout: pick a saved address ----------
  const picker = document.querySelector('[data-address-picker]');
  if (picker && cform) {
    const fields = ['customer_name', 'phone', 'address', 'city'];
    const saveBox = cform.querySelector('[data-save-address]');
    const showSave = (on) => { if (saveBox) { saveBox.classList.toggle('hidden', !on); saveBox.classList.toggle('flex', on); } };
    picker.addEventListener('change', (e) => {
      const r = e.target.closest('input[name=address_id]');
      if (!r) return;
      if (r.dataset.fill) {
        const v = JSON.parse(r.dataset.fill);
        fields.forEach((f) => {
          const input = cform.querySelector(`[name="${f}"]`);
          if (!input) return;
          input.value = v[f] || '';
          input.removeAttribute('aria-invalid');
          const slot = cform.querySelector(`[data-error-for="${f}"]`);
          if (slot) slot.textContent = '';
        });
      } else {
        ['address', 'city'].forEach((f) => { const input = cform.querySelector(`[name="${f}"]`); if (input) input.value = ''; });
        cform.querySelector('[name=address]')?.focus();
      }
      showSave(!r.value);
    });
    // typing over a saved address turns it into a new one
    cform.addEventListener('input', (e) => {
      if (!fields.includes(e.target.name)) return;
      const checked = picker.querySelector('input[name=address_id]:checked');
      if (checked && checked.value) {
        picker.querySelector('input[name=address_id][value=""]').checked = true;
        showSave(true);
      }
    });
  }

  // ---------- Product page: saved measurement profiles ----------
  const savedBox = document.querySelector('[data-saved-measurements]');
  if (savedBox && pform) {
    let profiles = JSON.parse(savedBox.dataset.profiles || '[]');
    const select = savedBox.querySelector('[data-saved-select]');
    const picker2 = savedBox.querySelector('[data-saved-picker]');
    const row = savedBox.querySelector('[data-save-row]');
    const fieldEl = (k) => pform.querySelector(`[name="measurements[${k}]"]`);

    select?.addEventListener('change', () => {
      const p = profiles.find((x) => String(x.id) === select.value);
      if (!p) return;
      ['height', 'weight', 'bust', 'waist', 'hip', 'note'].forEach((k) => {
        const input = fieldEl(k);
        if (!input) return;
        input.value = p.values[k] ?? '';
        input.removeAttribute('aria-invalid');
        const slot = pform.querySelector(`[data-error-for="${k}"]`);
        if (slot) slot.textContent = '';
      });
      fieldEl('height')?.dispatchEvent(new Event('input', { bubbles: true }));   // refresh the size suggestion
    });

    savedBox.querySelector('[data-save-measurements]')?.addEventListener('click', () => {
      row.classList.toggle('hidden');
      row.classList.toggle('flex');
      if (!row.classList.contains('hidden')) row.querySelector('[data-save-name]').focus();
    });
    const saveProfile = async () => {
      const nameInput = row.querySelector('[data-save-name]');
      const body = { name: nameInput.value };
      ['height', 'weight', 'bust', 'waist', 'hip', 'note'].forEach((k) => { body[k] = fieldEl(k)?.value || ''; });
      const btn = row.querySelector('[data-save-confirm]');
      btn.disabled = true;
      pform.querySelectorAll('[data-error-for]').forEach((el) => (el.textContent = ''));
      try {
        const { ok, status, data } = await send('POST', row.dataset.url, body);
        if (!ok) {
          if (status === 401) { toast(data.error); return; }
          const d = data.details || {};
          Object.entries(d).forEach(([k, msg]) => {
            const slot = pform.querySelector(`[data-error-for="${k}"]`);
            if (slot) slot.textContent = msg;
            fieldEl(k)?.setAttribute('aria-invalid', 'true');
          });
          if (d.name) { nameInput.setAttribute('aria-invalid', 'true'); nameInput.focus(); }
          toast(d.name || data.error);
          return;
        }
        profiles = data.profiles;
        select.innerHTML = select.options[0].outerHTML + profiles.map((p) => `<option value="${p.id}">${p.name.replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]))}</option>`).join('');
        select.value = String(data.id);
        picker2.classList.remove('hidden');
        nameInput.value = '';
        nameInput.removeAttribute('aria-invalid');
        row.classList.add('hidden');
        row.classList.remove('flex');
        toast(data.message);
      } finally {
        btn.disabled = false;
      }
    };
    row?.querySelector('[data-save-confirm]').addEventListener('click', saveProfile);
    row?.querySelector('[data-save-name]').addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); saveProfile(); } });
  }

  // =====================================================================
  //  Phase 10 — product reviews
  // =====================================================================
  const reviewsBox = document.querySelector('[data-reviews]');
  if (reviewsBox) {
    const list = reviewsBox.querySelector('[data-review-list]');
    const state = { rating: '', photos: '', sort: 'newest' };

    /** Load a page of reviews rendered by PHP; page 1 replaces the list, later pages are appended. */
    const fetchReviews = async (page = 1, append = false) => {
      list.classList.add('opacity-50');
      try {
        const data = await ajax(reviewsBox.dataset.reviews.replace(BASE, ''), { ...state, page });
        if (append) {
          list.querySelector('[data-review-more-wrap]')?.remove();
          list.insertAdjacentHTML('beforeend', data.html);
        } else {
          list.innerHTML = data.html;
        }
      } catch (err) {
        toast(err.message);
      } finally {
        list.classList.remove('opacity-50');
      }
    };
    const syncChips = () => reviewsBox.querySelectorAll('[data-review-toolbar] [data-review-filter]').forEach((b) => {
      const kind = b.dataset.reviewFilter;
      const on = kind === 'all' ? !state.rating && !state.photos
        : kind === 'rating' ? state.rating === b.dataset.value : !!state.photos;
      b.setAttribute('aria-pressed', on ? 'true' : 'false');
    });

    reviewsBox.addEventListener('click', async (e) => {
      // filters: chips in the toolbar and the rating bars in the summary
      const f = e.target.closest('[data-review-filter]');
      if (f) {
        const inToolbar = !!f.closest('[data-review-toolbar]');
        const kind = f.dataset.reviewFilter;
        if (kind === 'all') { state.rating = ''; state.photos = ''; }
        if (kind === 'rating') { state.rating = inToolbar && state.rating === f.dataset.value ? '' : f.dataset.value; state.photos = ''; }
        if (kind === 'photos') { state.photos = state.photos ? '' : '1'; state.rating = ''; }
        syncChips();
        fetchReviews(1);
        if (!inToolbar) list.scrollIntoView({ behavior: 'smooth', block: 'start' });
        return;
      }
      const more = e.target.closest('[data-review-more]');
      if (more) { more.disabled = true; fetchReviews(+more.dataset.reviewMore, true); return; }

      const help = e.target.closest('[data-review-helpful]');
      if (help) {
        help.disabled = true;
        const { ok, data } = await send('POST', help.dataset.reviewHelpful);
        if (!ok) { help.disabled = false; toast(data.error); return; }
        help.querySelector('span').textContent = data.label;
        help.setAttribute('aria-pressed', 'true');
        toast(data.message);
        return;
      }
      const img = e.target.closest('[data-review-image]');
      if (img) {
        const dlg = document.getElementById('review-lightbox');
        if (!dlg || typeof dlg.showModal !== 'function') { window.open(img.dataset.reviewImage, '_blank', 'noopener'); return; }
        dlg.querySelector('[data-lightbox-img]').src = img.dataset.reviewImage;
        dlg.showModal();
      }
    });
    reviewsBox.querySelector('[data-review-sort]')?.addEventListener('change', (e) => { state.sort = e.target.value; fetchReviews(1); });

    // ----- write a review (multipart: photos are sent with the text) -----
    const rform = reviewsBox.querySelector('[data-review-form]');
    if (rform) {
      const word = rform.querySelector('[data-star-word]');
      rform.addEventListener('change', (e) => {
        if (e.target.name !== 'rating') return;
        word.textContent = e.target.dataset.word;
        rform.querySelector('[data-error-for="rating"]').textContent = '';
      });

      const body = rform.querySelector('[name=body]');
      const counterOut = rform.querySelector('[data-counter-out]');
      const updateCounter = () => { counterOut.textContent = body.dataset.counter.replace('__N__', body.value.length); };
      body.addEventListener('input', () => { updateCounter(); body.removeAttribute('aria-invalid'); });
      updateCounter();

      // photo picker with previews; files are kept in an array so they can be removed one by one
      const input = rform.querySelector('[data-photo-input]');
      const previews = rform.querySelector('[data-photo-previews]');
      const addTile = rform.querySelector('[data-photo-add]');
      const submitBtn = rform.querySelector('button:not([type=button])');
      const max = +input.dataset.max || 3;
      let files = [];
      const renderPreviews = () => {
        previews.querySelectorAll('[data-photo-tile]').forEach((t) => { URL.revokeObjectURL(t.dataset.url); t.remove(); });
        files.forEach((file, i) => {
          const url = URL.createObjectURL(file);
          const tile = document.createElement('div');
          tile.className = 'relative h-20 w-20 overflow-hidden border border-line';
          tile.dataset.photoTile = '';
          tile.dataset.url = url;
          const im = document.createElement('img');
          im.src = url; im.alt = ''; im.className = 'h-full w-full object-cover';
          const rm = document.createElement('button');
          rm.type = 'button';
          rm.dataset.photoRemove = i;
          rm.className = 'absolute right-0.5 top-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-ink/80 text-xs text-ivory';
          rm.setAttribute('aria-label', submitBtn.dataset.removeLabel);
          rm.textContent = '×';
          tile.append(im, rm);
          previews.insertBefore(tile, addTile);
        });
        addTile.classList.toggle('hidden', files.length >= max);
        addTile.classList.toggle('flex', files.length < max);
      };
      input.addEventListener('change', () => {
        files = files.concat([...input.files].filter((f) => /^image\/(jpeg|png|webp)$/.test(f.type))).slice(0, max);
        input.value = '';
        rform.querySelector('[data-error-for="images"]').textContent = '';
        renderPreviews();
      });
      previews.addEventListener('click', (e) => {
        const rm = e.target.closest('[data-photo-remove]');
        if (!rm) return;
        files.splice(+rm.dataset.photoRemove, 1);
        renderPreviews();
      });

      rform.addEventListener('submit', async (e) => {
        e.preventDefault();
        clearFormErrors(rform);
        busy(submitBtn, true);
        const fd = new FormData(rform);
        files.forEach((f) => fd.append('images[]', f));
        try {
          const res = await fetch(rform.dataset.reviewForm, {
            method: 'POST', body: fd, credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': CSRF },
          });
          const data = await res.json().catch(() => ({ error: res.statusText }));
          if (!res.ok) { showFormErrors(rform, data); return; }
          const done = document.createElement('div');
          done.className = 'flex items-start gap-3 border border-emerald-600/40 bg-emerald-50 p-4 text-sm text-emerald-800';
          done.setAttribute('role', 'status');
          done.textContent = data.message;
          rform.replaceWith(done);
          toast(data.message);
          if (data.status === 'approved') setTimeout(() => { location.hash = 'reviews'; location.reload(); }, 1500);
        } catch (err) {
          toast(err.message);
        } finally {
          if (submitBtn.isConnected) busy(submitBtn, false);
        }
      });
    }
  }

  // ---------- Copy buttons (bank transfer details) ----------
  document.addEventListener('click', async (e) => {
    const b = e.target.closest('[data-copy]');
    if (!b) return;
    try {
      await navigator.clipboard.writeText(b.dataset.copy);
      const old = b.textContent;
      b.textContent = b.dataset.copied;
      setTimeout(() => (b.textContent = old), 1500);
    } catch { /* clipboard blocked — the value is visible anyway */ }
  });
})();
