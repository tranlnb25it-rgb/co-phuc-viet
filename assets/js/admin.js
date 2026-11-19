/* Cổ Phục Việt — admin. Pages are rendered by PHP; this adds Ajax for filters, toggles, stock, images and orders. */
(function () {
  'use strict';

  const BASE = (document.body.dataset.base || '/').replace(/\/$/, '');
  const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const abs = (path) => (path.startsWith(BASE + '/') || path.startsWith('http') ? path : BASE + path);

  /** fetch wrapper: CSRF header, JSON in/out, 401 → login page. Resolves {ok, status, data}. */
  async function send(method, url, body) {
    const headers = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': CSRF };
    const isForm = body instanceof FormData;
    if (body !== undefined && !isForm) headers['Content-Type'] = 'application/json';
    const res = await fetch(abs(url), {
      method, headers, credentials: 'same-origin',
      body: body === undefined ? undefined : isForm ? body : JSON.stringify(body),
    });
    if (res.status === 401) { window.location.href = BASE + '/admin/dang-nhap?next=' + encodeURIComponent(location.pathname.replace(BASE, '')); }
    const data = await res.json().catch(() => ({ error: 'Lỗi máy chủ (' + res.status + ')' }));
    return { ok: res.ok, status: res.status, data };
  }

  // ---------- Toast ----------
  const toastEl = document.querySelector('[data-admin-toast]');
  let toastTimer;
  function toast(msg, ok = true) {
    if (!toastEl || !msg) return;
    toastEl.textContent = msg;
    toastEl.classList.remove('hidden', 'bg-ink', 'bg-crimson');
    toastEl.classList.add(ok ? 'bg-ink' : 'bg-crimson');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toastEl.classList.add('hidden'), 3000);
  }

  /** Show {field: message} errors next to inputs inside `scope`. */
  function showErrors(scope, details = {}) {
    scope.querySelectorAll('[data-error-for]').forEach((el) => (el.textContent = ''));
    scope.querySelectorAll('[aria-invalid]').forEach((el) => el.removeAttribute('aria-invalid'));
    Object.entries(details).forEach(([field, msg]) => {
      const slot = scope.querySelector(`[data-error-for="${field}"]`);
      if (slot) slot.textContent = msg;
      scope.querySelector(`[name="${field}"]`)?.setAttribute('aria-invalid', 'true');
    });
    scope.querySelector('[aria-invalid=true]')?.focus();
  }

  // ---------- Ajax list pages (products, orders) ----------
  document.querySelectorAll('form[data-ajax-filter]').forEach((form) => {
    const target = document.querySelector(form.dataset.ajaxFilter);
    if (!target) return;
    let ctrl = null, timer = null;

    const paramsFromForm = () => {
      const p = new URLSearchParams();
      for (const [k, v] of new FormData(form)) if (v !== '') p.append(k, v);
      return p;
    };
    const syncForm = (p) => {
      form.querySelectorAll('input[type=radio]').forEach((el) => (el.checked = (p.get(el.name) || '') === el.value));
      form.querySelectorAll('input[type=search], input[type=date], select').forEach((el) => (el.value = p.get(el.name) || ''));
    };

    const load = async (params, mode = 'push') => {
      if (ctrl) ctrl.abort();
      ctrl = new AbortController();
      target.classList.add('opacity-50');
      try {
        const res = await fetch(target.dataset.endpoint + '?' + params, {
          headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, signal: ctrl.signal,
        });
        if (res.status === 401) { location.reload(); return; }
        const data = await res.json();
        target.innerHTML = data.html;
        if (data.counts) Object.entries(data.counts).forEach(([k, n]) => {
          const el = form.querySelector(`[data-count="${k}"]`);
          if (el) el.textContent = n;
        });
        const url = form.action + (params.toString() ? '?' + params : '');
        if (mode === 'push') history.pushState(null, '', url);
        if (mode === 'replace') history.replaceState(null, '', url);
        // keep "Xuất Excel" in sync with the filters (page is irrelevant for export)
        const exp = document.querySelector('[data-export-link]');
        if (exp) {
          const ep = new URLSearchParams(params); ep.delete('page');
          exp.href = exp.dataset.baseHref + (ep.toString() ? '?' + ep : '');
        }
        document.dispatchEvent(new CustomEvent('admin:list-loaded'));
      } catch (err) {
        if (err.name !== 'AbortError') toast('Không tải được dữ liệu', false);
      } finally {
        target.classList.remove('opacity-50');
      }
    };

    form.addEventListener('change', (e) => { if (e.target.type !== 'search') load(paramsFromForm()); });
    form.addEventListener('input', (e) => {
      if (e.target.type !== 'search') return;
      clearTimeout(timer);
      timer = setTimeout(() => load(paramsFromForm(), 'replace'), 300);
    });
    form.addEventListener('submit', (e) => { e.preventDefault(); load(paramsFromForm()); });
    target.addEventListener('click', (e) => {
      const a = e.target.closest('a[data-ajax-link]');
      if (!a) return;
      e.preventDefault();
      const p = new URL(a.href).searchParams;
      syncForm(p);
      load(p);
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
    window.addEventListener('popstate', () => {
      const p = new URLSearchParams(location.search);
      syncForm(p);
      load(p, 'none');
    });
  });

  // Whole table row is clickable (orders), except real links/buttons inside it
  document.addEventListener('click', (e) => {
    const row = e.target.closest('tr[data-href]');
    if (row && !e.target.closest('a, button, input, select')) window.location.href = row.dataset.href;
  });

  // ---------- Delegated actions ----------
  document.addEventListener('click', async (e) => {
    // Toggle switches (is_active / is_featured)
    const sw = e.target.closest('[data-toggle-url]');
    if (sw) {
      sw.disabled = true;
      const { ok, data } = await send('PATCH', sw.dataset.toggleUrl, { field: sw.dataset.field });
      sw.disabled = false;
      if (ok) { sw.setAttribute('aria-checked', data.value ? 'true' : 'false'); toast('Đã cập nhật'); }
      else toast(data.error, false);
      return;
    }

    // Delete product (list page)
    const del = e.target.closest('[data-delete-url]');
    if (del) {
      if (!confirm(del.dataset.confirm)) return;
      const { ok, data } = await send('DELETE', del.dataset.deleteUrl);
      if (ok) { del.closest('tr')?.remove(); toast(data.message); } else toast(data.error, false);
      return;
    }

    // Delete image
    const delImg = e.target.closest('[data-delete-image]');
    if (delImg) {
      if (!confirm('Xoá ảnh này?')) return;
      const { ok, data } = await send('DELETE', delImg.dataset.deleteImage);
      if (ok) { document.querySelector('[data-images]').innerHTML = data.html; toast(data.message); } else toast(data.error, false);
      return;
    }

    // Delete variant
    const delVar = e.target.closest('[data-delete-variant]');
    if (delVar) {
      if (!confirm(delVar.dataset.confirm)) return;
      const { ok, data } = await send('DELETE', delVar.dataset.deleteVariant);
      if (ok) { document.querySelector('[data-variants]').innerHTML = data.html; toast(data.message); } else toast(data.error, false);
    }
  });

  // ---------- Product form ----------
  const pform = document.getElementById('product-admin-form');
  if (pform) {
    const slugSrc = pform.querySelector('[data-slug-source]');
    const slugEl = pform.querySelector('[data-slug-target]');
    const slugify = (s) => s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/đ/g, 'd')
      .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 150);
    let slugTouched = slugEl.hasAttribute('data-slug-locked') && slugEl.value !== '';
    slugEl.addEventListener('input', () => { slugTouched = slugEl.value !== ''; });
    slugSrc.addEventListener('input', () => { if (!slugTouched) slugEl.value = slugify(slugSrc.value); });

    pform.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = pform.querySelector('[data-save]');
      const label = btn.querySelector('span');
      const errBox = pform.querySelector('[data-form-error]');
      btn.disabled = true;
      label.textContent = 'Đang lưu…';
      errBox.classList.add('hidden');
      try {
        const body = Object.fromEntries(new FormData(pform));
        const { ok, data } = await send(pform.dataset.method, pform.dataset.url, body);
        if (!ok) {
          showErrors(pform, data.details);
          errBox.textContent = data.error;
          errBox.classList.remove('hidden');
          return;
        }
        if (data.redirect) { window.location.href = data.redirect; return; }
        showErrors(pform, {});
        toast(data.message);
        slugEl.value = data.slug;
        const view = document.querySelector('[data-view-link]');
        if (view) view.href = data.view_url;
      } finally {
        btn.disabled = false;
        label.textContent = btn.dataset.label;
      }
    });
  }

  // ---------- Image upload ----------
  document.querySelectorAll('[data-image-upload]').forEach((form) => {
    const input = form.querySelector('input[type=file]');
    const label = form.querySelector('[data-upload-label]');
    input.addEventListener('change', async () => {
      if (!input.files.length) return;
      const fd = new FormData();
      [...input.files].forEach((f) => fd.append('images[]', f));
      fd.append('view_type', form.querySelector('[name=view_type]').value);
      label.textContent = 'Đang tải lên…';
      try {
        const { data } = await send('POST', form.dataset.imageUpload, fd);
        if (data.html) document.querySelector('[data-images]').innerHTML = data.html;
        const errs = (data.errors || []).join('\n');
        toast(errs ? (data.message + ' — ' + errs) : (data.message || data.error), !errs && !data.error);
      } finally {
        label.textContent = 'Chọn ảnh';
        input.value = '';
      }
    });
  });

  // ---------- Variants: inline stock edit + generator ----------
  const saveStock = async (input) => {
    if (input.value === input.dataset.saved) return;
    const { ok, data } = await send('PATCH', input.dataset.stockUrl, { stock: input.value });
    if (!ok) { toast(data.error, false); input.value = input.dataset.saved; return; }
    input.dataset.saved = String(data.stock);
    input.value = data.stock;
    const lowCls = ['border-crimson/50', 'bg-crimson/5', 'text-crimson'];
    const okCls = ['border-line', 'bg-white'];
    input.classList.remove(...lowCls, ...okCls);
    input.classList.add(...(data.low ? lowCls : okCls));
    const total = document.querySelector('[data-stock-total]');
    if (total) total.textContent = data.total;
    toast('Đã lưu tồn kho');
  };
  document.addEventListener('change', (e) => {
    if (e.target.matches('[data-stock-url]')) saveStock(e.target);
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && e.target.matches('[data-stock-url]')) { e.preventDefault(); e.target.blur(); }
  });

  document.addEventListener('submit', async (e) => {
    const form = e.target.closest('[data-variant-generator]');
    if (!form) return;
    e.preventDefault();
    const fd = new FormData(form);
    const body = {
      sizes: fd.getAll('sizes'), fabrics: fd.getAll('fabrics'),
      color_hex: fd.get('color_hex'), color_vi: fd.get('color_vi'), color_en: fd.get('color_en'), stock: fd.get('stock'),
    };
    const { ok, data } = await send('POST', form.dataset.variantGenerator, body);
    if (!ok) { showErrors(form, data.details); toast(data.error, false); return; }
    document.querySelector('[data-variants]').innerHTML = data.html;
    toast(data.message);
  });

  // ---------- Order detail: status changes ----------
  const orderBox = document.querySelector('[data-order-update]');
  if (orderBox) {
    orderBox.addEventListener('change', async (e) => {
      const sel = e.target.closest('select');
      if (!sel) return;
      if (sel.name === 'status' && sel.value === 'cancelled' &&
          !confirm('Huỷ đơn này? Tồn kho sản phẩm may sẵn sẽ được hoàn lại và đơn không thể mở lại.')) {
        sel.value = sel.dataset.current;
        return;
      }
      sel.disabled = true;
      const { ok, data } = await send('PATCH', orderBox.dataset.orderUpdate, { [sel.name]: sel.value });
      sel.disabled = false;
      if (!ok) { toast(data.error, false); sel.value = sel.dataset.current; return; }
      sel.dataset.current = sel.value;
      document.querySelector('[data-status-badge]').innerHTML = data.badge;
      orderBox.querySelector('[data-payment-badge]').innerHTML = data.payment_badge;
      document.querySelector('[data-timeline]').innerHTML = data.timeline_html;
      document.querySelector('[data-customer-card]').innerHTML = data.customer_html;   // "Sửa" disappears once final
      if (data.final) {
        orderBox.querySelector('select[name=status]').disabled = true;
        orderBox.querySelector('[data-final-note]').textContent = 'Đơn đã huỷ — tồn kho đã được hoàn lại, không thể mở lại.';
      }
      toast(data.message);
    });
  }

  // ---------- Order detail: internal notes + edit customer ----------
  document.addEventListener('submit', async (e) => {
    const noteForm = e.target.closest('[data-note-form]');
    const custForm = e.target.closest('[data-customer-form]');
    if (!noteForm && !custForm) return;
    e.preventDefault();
    const form = noteForm || custForm;
    const btn = form.querySelector('button:not([type=button])');
    btn.disabled = true;
    try {
      const body = Object.fromEntries(new FormData(form));
      const { ok, data } = noteForm
        ? await send('POST', form.dataset.noteForm, body)
        : await send('PATCH', form.dataset.customerForm, body);
      if (!ok) { showErrors(form, data.details); toast(data.error, false); return; }
      document.querySelector('[data-timeline]').innerHTML = data.timeline_html;
      document.querySelector('[data-customer-card]').innerHTML = data.customer_html;
      toast(data.message);
    } finally {
      btn.disabled = false;
    }
  });
  document.addEventListener('click', (e) => {
    const card = e.target.closest('[data-customer-card]');
    if (!card) return;
    const edit = e.target.closest('[data-edit-customer]');
    const cancel = e.target.closest('[data-edit-cancel]');
    if (!edit && !cancel) return;
    card.querySelector('[data-customer-view]').classList.toggle('hidden', !!edit);
    card.querySelector('[data-customer-form]').classList.toggle('hidden', !edit);
    if (edit) card.querySelector('[data-customer-form] input')?.focus();
  });

  // ---------- Orders list: select rows → bulk status / print ----------
  const bulkBar = document.querySelector('[data-bulk-bar]');
  if (bulkBar) {
    const selected = () => [...document.querySelectorAll('[data-select]:checked')].map((c) => c.value);
    const refreshBar = () => {
      const codes = selected();
      bulkBar.classList.toggle('hidden', !codes.length);
      bulkBar.classList.toggle('flex', !!codes.length);
      bulkBar.querySelector('[data-bulk-count]').textContent = codes.length;
      const all = document.querySelector('[data-select-all]');
      const boxes = document.querySelectorAll('[data-select]');
      if (all) { all.checked = boxes.length > 0 && codes.length === boxes.length; all.indeterminate = codes.length > 0 && codes.length < boxes.length; }
    };
    document.addEventListener('change', (e) => {
      if (e.target.matches('[data-select-all]')) {
        document.querySelectorAll('[data-select]').forEach((c) => (c.checked = e.target.checked));
      }
      if (e.target.matches('[data-select], [data-select-all]')) refreshBar();
    });
    document.addEventListener('admin:list-loaded', refreshBar);   // rows replaced → selection cleared

    bulkBar.querySelector('[data-bulk-clear]').addEventListener('click', () => {
      document.querySelectorAll('[data-select]').forEach((c) => (c.checked = false));
      refreshBar();
    });
    const reloadList = () => document.querySelector('form[data-ajax-filter]')?.requestSubmit();   // rows + counts

    // Orders: print selected
    bulkBar.querySelectorAll('[data-bulk-print]').forEach((b) => b.addEventListener('click', () => {
      const url = bulkBar.dataset.printUrl + '?type=' + b.dataset.bulkPrint + '&codes=' + encodeURIComponent(selected().join(','));
      window.open(url, '_blank', 'noopener');
    }));
    // Orders: change status of selected (only rendered for staff allowed to manage orders)
    bulkBar.querySelector('[data-bulk-apply]')?.addEventListener('click', async (ev) => {
      const codes = selected();
      const sel = bulkBar.querySelector('[data-bulk-status]');
      const label = sel.options[sel.selectedIndex].text;
      const warn = sel.value === 'cancelled' ? '\nĐơn bị huỷ sẽ được hoàn kho và không thể mở lại.' : '';
      if (!codes.length || !confirm(`Chuyển ${codes.length} đơn sang “${label}”?${warn}`)) return;
      ev.target.disabled = true;
      const { ok, data } = await send('POST', bulkBar.dataset.bulkUrl, { codes, status: sel.value });
      ev.target.disabled = false;
      toast(ok ? data.message + (data.skipped?.length ? ': ' + data.skipped.join('; ') : '') : data.error, ok && !data.skipped?.length);
      reloadList();
    });
    // Products: activate / hide / feature / unfeature / delete selected
    bulkBar.querySelectorAll('[data-bulk-action]').forEach((b) => b.addEventListener('click', async () => {
      const ids = selected();
      const action = b.dataset.bulkAction;
      const msg = action === 'delete'
        ? `Xoá vĩnh viễn ${ids.length} sản phẩm? Ảnh và tồn kho sẽ bị xoá; đơn hàng cũ vẫn giữ thông tin.`
        : `${b.textContent.trim()} ${ids.length} sản phẩm?`;
      if (!ids.length || !confirm(msg)) return;
      const { ok, data } = await send('POST', bulkBar.dataset.bulkUrl, { ids, action });
      toast(ok ? data.message : data.error, ok);
      reloadList();
    }));
  }

  // ---------- Product: duplicate, image order & type ----------
  document.addEventListener('click', async (e) => {
    const dup = e.target.closest('[data-duplicate]');
    if (!dup) return;
    if (!confirm('Tạo bản sao của sản phẩm này? Bản sao sẽ ở trạng thái ẩn, tồn kho bằng 0.')) return;
    dup.disabled = true;
    const { ok, data } = await send('POST', dup.dataset.duplicate);
    if (ok) window.location.href = data.redirect; else { dup.disabled = false; toast(data.error, false); }
  });
  document.addEventListener('change', async (e) => {
    const sel = e.target.closest('[data-image-type]');
    if (!sel) return;
    const { ok, data } = await send('PATCH', sel.dataset.imageType, { view_type: sel.value });
    if (ok) { document.querySelector('[data-images]').innerHTML = data.html; toast(data.message); } else toast(data.error, false);
  });
  // Drag & drop reordering (HTML5 DnD; the list is re-rendered by PHP after uploads/deletes, so delegate)
  let dragged = null;
  document.addEventListener('dragstart', (e) => {
    const li = e.target.closest?.('[data-image-id]');
    if (!li) return;
    dragged = li;
    li.classList.add('is-dragging');
    e.dataTransfer.effectAllowed = 'move';
  });
  document.addEventListener('dragover', (e) => {
    const over = e.target.closest?.('[data-image-id]');
    if (!dragged || !over || over === dragged || over.parentNode !== dragged.parentNode) return;
    e.preventDefault();
    const r = over.getBoundingClientRect();
    const after = (e.clientX - r.left) > r.width / 2;
    over.parentNode.insertBefore(dragged, after ? over.nextSibling : over);
  });
  document.addEventListener('dragend', async () => {
    if (!dragged) return;
    const list = dragged.closest('[data-image-list]');
    dragged.classList.remove('is-dragging');
    dragged = null;
    const ids = [...list.querySelectorAll('[data-image-id]')].map((li) => +li.dataset.imageId);
    const { ok, data } = await send('PATCH', list.dataset.imageList, { ids });
    toast(ok ? data.message : data.error, ok);
  });

  // ---------- Inventory: per-row adjustment ----------
  document.addEventListener('submit', async (e) => {
    const form = e.target.closest('[data-adjust-form]');
    if (!form) return;
    e.preventDefault();
    const { ok, data } = await send('POST', form.dataset.adjustForm, Object.fromEntries(new FormData(form)));
    if (!ok) { showErrors(form, data.details); toast(data.error, false); return; }
    toast(data.message);
    form.closest('details')?.removeAttribute('open');
    form.reset();
    document.querySelector('form[data-ajax-filter]')?.requestSubmit();   // refresh numbers & ordering
  });

  // ---------- Taxonomy (categories / eras / fabrics): inline rows ----------
  const taxonomyBox = document.querySelector('[data-taxonomy]');
  if (taxonomyBox) {
    taxonomyBox.addEventListener('submit', async (e) => {
      const form = e.target.closest('[data-tax-form]');
      if (!form) return;
      e.preventDefault();
      const { ok, data } = await send(form.dataset.method, form.dataset.taxForm, Object.fromEntries(new FormData(form)));
      if (!ok) { showErrors(form, data.details); toast(data.error, false); return; }
      taxonomyBox.innerHTML = data.html;
      toast(data.message);
    });
    taxonomyBox.addEventListener('click', async (e) => {
      const del = e.target.closest('[data-tax-delete]');
      if (!del || !confirm(del.dataset.confirm)) return;
      const { ok, data } = await send('DELETE', del.dataset.taxDelete);
      if (ok) { taxonomyBox.innerHTML = data.html; toast(data.message); } else toast(data.error, false);
    });
  }

  // ---------- Settings / staff / own password ----------
  document.addEventListener('submit', async (e) => {
    const form = e.target.closest('[data-settings-form], [data-staff-form], [data-password-form]');
    if (!form) return;
    e.preventDefault();
    const btn = form.querySelector('button:not([type=button])');
    btn.disabled = true;
    try {
      const body = Object.fromEntries(new FormData(form));
      let res;
      if (form.dataset.settingsForm) res = await send('PUT', form.dataset.settingsForm, body);
      else if (form.dataset.staffForm) res = await send(form.dataset.method, form.dataset.staffForm, body);
      else res = await send('POST', form.dataset.passwordForm, body);
      const { ok, data } = res;
      if (!ok) { showErrors(form, data.details); toast(data.error, false); return; }
      showErrors(form, {});
      toast(data.message);
      if (data.html) document.querySelector('[data-staff-list]').innerHTML = data.html;
      if (form.dataset.staffForm && form.dataset.method === 'POST') form.reset();
      if (form.dataset.passwordForm) form.reset();
    } finally {
      btn.disabled = false;
    }
  });
  document.addEventListener('click', async (e) => {
    const b = e.target.closest('[data-staff-password]');
    if (!b) return;
    const pw = prompt(`Mật khẩu mới cho ${b.dataset.email} (tối thiểu 8 ký tự):`);
    if (pw === null) return;
    const { ok, data } = await send('POST', b.dataset.staffPassword, { password: pw });
    toast(ok ? data.message + ' — hãy gửi mật khẩu cho nhân viên qua kênh riêng.' : data.error, ok);
  });

  // ---------- Dashboard: date range (Ajax, keeps the frame while loading) ----------
  const rangeBox = document.querySelector('[data-range-filter]');
  const dashBody = document.getElementById('dashboard-body');
  if (rangeBox && dashBody) {
    const loadRange = async (range, push = true) => {
      rangeBox.querySelectorAll('[data-range]').forEach((a) => a.setAttribute('aria-checked', a.dataset.range === String(range) ? 'true' : 'false'));
      dashBody.classList.add('opacity-50');
      try {
        const res = await fetch(dashBody.dataset.endpoint + '?range=' + range, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
        if (res.status === 401) { location.reload(); return; }
        const data = await res.json();
        dashBody.innerHTML = data.html;
        if (push) history.pushState({ range }, '', '?range=' + range);
      } catch { toast('Không tải được số liệu', false); }
      finally { dashBody.classList.remove('opacity-50'); }
    };
    rangeBox.addEventListener('click', (e) => {
      const a = e.target.closest('[data-range]');
      if (!a) return;
      e.preventDefault();
      loadRange(a.dataset.range);
    });
    window.addEventListener('popstate', () => loadRange(new URLSearchParams(location.search).get('range') || 30, false));
  }

  // ---------- Chart tooltips (columns): hover and keyboard focus ----------
  const showTip = (mark) => {
    const fig = mark.closest('[data-chart]');
    const tip = fig?.querySelector('[data-chart-tip]');
    if (!tip) return;
    tip.querySelector('[data-tip="value"]').textContent = mark.dataset.tipValue;
    tip.querySelector('[data-tip="title"]').textContent = mark.dataset.tipTitle;
    tip.querySelector('[data-tip="sub"]').textContent = mark.dataset.tipSub || '';
    tip.classList.remove('hidden');
    const f = fig.getBoundingClientRect(), m = mark.getBoundingClientRect();
    const x = Math.min(Math.max(m.left - f.left + m.width / 2 - tip.offsetWidth / 2, 0), f.width - tip.offsetWidth);
    tip.style.left = x + 'px';
    tip.style.top = '-8px';
  };
  const hideTip = (mark) => mark.closest('[data-chart]')?.querySelector('[data-chart-tip]')?.classList.add('hidden');
  document.addEventListener('pointerover', (e) => { const m = e.target.closest('[data-tip-value]'); if (m) showTip(m); });
  document.addEventListener('pointerout', (e) => { const m = e.target.closest('[data-tip-value]'); if (m && !m.contains(e.relatedTarget)) hideTip(m); });
  document.addEventListener('focusin', (e) => { if (e.target.matches?.('[data-tip-value]')) showTip(e.target); });
  document.addEventListener('focusout', (e) => { if (e.target.matches?.('[data-tip-value]')) hideTip(e.target); });

  // ---------- New-order bell: poll every 20s, chime + toast on new orders ----------
  const bell = document.querySelector('[data-bell]');
  if (bell) {
    const panel = bell.querySelector('[data-bell-panel]');
    const toggle = bell.querySelector('[data-bell-toggle]');
    const badge = bell.querySelector('[data-bell-count]');
    let latestId = +bell.dataset.latestId || 0;
    const baseTitle = document.title;
    let audio = null;

    // Browsers only allow sound after the user has interacted with the page
    const unlockAudio = () => { try { audio = audio || new (window.AudioContext || window.webkitAudioContext)(); audio.resume(); } catch { /* no audio */ } };
    document.addEventListener('pointerdown', unlockAudio, { once: true });
    document.addEventListener('keydown', unlockAudio, { once: true });
    const chime = () => {
      if (!audio || audio.state !== 'running') return;
      [880, 1318.5].forEach((freq, i) => {   // two soft notes
        const o = audio.createOscillator(), g = audio.createGain();
        o.type = 'sine'; o.frequency.value = freq;
        const t = audio.currentTime + i * 0.18;
        g.gain.setValueAtTime(0.0001, t);
        g.gain.exponentialRampToValueAtTime(0.18, t + 0.02);
        g.gain.exponentialRampToValueAtTime(0.0001, t + 0.35);
        o.connect(g).connect(audio.destination);
        o.start(t); o.stop(t + 0.4);
      });
    };

    const poll = async () => {
      try {
        const res = await fetch(bell.dataset.endpoint, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
        if (!res.ok) return;
        const data = await res.json();
        badge.textContent = data.count || '';
        badge.classList.toggle('hidden', !data.count);
        badge.classList.toggle('flex', !!data.count);
        document.title = (data.count ? `(${data.count}) ` : '') + baseTitle;
        if (!panel.classList.contains('hidden')) panel.innerHTML = data.html;
        // latestId starts at the newest order when the page was rendered, so only truly new orders chime
        if (data.latest_id > latestId) {
          latestId = data.latest_id;
          toast('🔔 Có đơn hàng mới!');
          chime();
        }
      } catch { /* offline — try again next tick */ }
    };

    toggle.addEventListener('click', async () => {
      const open = panel.classList.contains('hidden');
      panel.classList.toggle('hidden', !open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (open) { panel.innerHTML = '<p class="px-4 py-6 text-center text-sm text-ink-soft">Đang tải…</p>'; await poll(); }
    });
    document.addEventListener('click', (e) => {
      if (!bell.contains(e.target)) { panel.classList.add('hidden'); toggle.setAttribute('aria-expanded', 'false'); }
    });

    let timer = setInterval(poll, 20000);
    document.addEventListener('visibilitychange', () => {   // don't poll from background tabs; catch up on return
      clearInterval(timer);
      if (!document.hidden) { poll(); timer = setInterval(poll, 20000); }
    });
  }

  // ---------- Coupons: form (type-dependent fields, random code, save, delete) ----------
  const cform = document.querySelector('[data-coupon-admin]');
  if (cform) {
    const syncType = () => {
      const type = cform.querySelector('[data-coupon-type]:checked')?.value || 'percent';
      cform.querySelectorAll('[data-show-for]').forEach((el) => el.classList.toggle('hidden', !el.dataset.showFor.split(' ').includes(type)));
      const lbl = cform.querySelector('[data-value-label]');
      if (lbl) lbl.textContent = type === 'percent' ? 'Phần trăm giảm (%) *' : 'Số tiền giảm (₫) *';
    };
    cform.addEventListener('change', (e) => { if (e.target.matches('[data-coupon-type]')) syncType(); });
    syncType();

    cform.querySelector('[data-generate-code]')?.addEventListener('click', async (e) => {
      const res = await fetch(e.currentTarget.dataset.generateCode, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
      const data = await res.json();
      cform.querySelector('[name=code]').value = data.code;
    });

    cform.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = cform.querySelector('[data-save]');
      const label = btn.querySelector('span');
      const errBox = cform.querySelector('[data-form-error]');
      btn.disabled = true; label.textContent = 'Đang lưu…'; errBox.classList.add('hidden');
      try {
        const { ok, data } = await send(cform.dataset.method, cform.dataset.url, Object.fromEntries(new FormData(cform)));
        if (!ok) { showErrors(cform, data.details); errBox.textContent = data.error; errBox.classList.remove('hidden'); return; }
        if (data.redirect) { window.location.href = data.redirect; return; }
        showErrors(cform, {});
        toast(data.message);
      } finally {
        btn.disabled = false; label.textContent = btn.dataset.label;
      }
    });
  }
  document.addEventListener('click', async (e) => {
    const del = e.target.closest('[data-coupon-delete]');
    if (!del || !confirm('Xoá mã giảm giá này? (Nếu đã có đơn dùng, mã sẽ được tắt thay vì xoá.)')) return;
    const { ok, data } = await send('DELETE', del.dataset.couponDelete);
    if (!ok) { toast(data.error, false); return; }
    toast(data.message);
    setTimeout(() => (window.location.href = del.dataset.back), 900);
  });

  // ---------- Products: bulk "X% off" ----------
  document.addEventListener('click', async (e) => {
    const b = e.target.closest('[data-bulk-sale]');
    if (!b) return;
    const bar = b.closest('[data-bulk-bar]');
    const ids = [...document.querySelectorAll('[data-select]:checked')].map((c) => c.value);
    if (!ids.length) return;
    const pct = prompt(`Giảm bao nhiêu % cho ${ids.length} sản phẩm? (1–90)`, '10');
    if (pct === null) return;
    const ends = prompt('Kết thúc vào ngày nào? (định dạng NĂM-THÁNG-NGÀY, ví dụ 2026-12-31 — để trống nếu không hết hạn)', '');
    if (ends === null) return;
    const { ok, data } = await send('POST', bar.dataset.bulkUrl, { ids, action: 'sale', percent: pct, ends_at: ends ? ends + ' 23:59' : '' });
    toast(ok ? data.message : data.error, ok);
    document.querySelector('form[data-ajax-filter]')?.requestSubmit();
  });

  // ---------- Reviews: approve / hide / reply / delete, bulk ----------
  const reviewList = document.getElementById('review-list');
  if (reviewList) {
    const bulk = document.querySelector('[data-review-bulk]');
    const setCounts = (counts) => {
      if (!counts) return;
      Object.entries(counts).forEach(([k, n]) => {
        const el = document.querySelector(`[data-count="${k === '' ? 'all' : k}"]`);
        if (el) el.textContent = n;
      });
    };
    const checked = () => [...reviewList.querySelectorAll('[data-review-check]:checked')].map((c) => +c.value);
    const syncBulk = () => {
      const ids = checked();
      bulk.classList.toggle('hidden', !ids.length);
      bulk.classList.toggle('flex', !!ids.length);
      bulk.querySelector('[data-review-bulk-count]').textContent = ids.length;
    };
    const reloadReviews = () => document.querySelector('form[data-ajax-filter]')?.requestSubmit();
    document.addEventListener('admin:list-loaded', syncBulk);

    reviewList.addEventListener('change', (e) => {
      if (e.target.matches('[data-review-check-all]')) {
        reviewList.querySelectorAll('[data-review-check]').forEach((c) => (c.checked = e.target.checked));
      }
      if (e.target.matches('[data-review-check], [data-review-check-all]')) syncBulk();
    });

    reviewList.addEventListener('click', async (e) => {
      const st = e.target.closest('[data-review-status]');
      const del = e.target.closest('[data-review-delete]');
      if (st) {
        st.disabled = true;
        const { ok, data } = await send('PATCH', st.dataset.url, { status: st.dataset.reviewStatus });
        if (!ok) { st.disabled = false; toast(data.error, false); return; }
        st.closest('[data-review-card]').outerHTML = data.html;
        setCounts(data.counts);
        toast(data.message);
        syncBulk();
      } else if (del) {
        if (!confirm('Xoá vĩnh viễn đánh giá này (kèm ảnh)?')) return;
        const { ok, data } = await send('DELETE', del.dataset.reviewDelete);
        if (!ok) { toast(data.error, false); return; }
        del.closest('[data-review-card]').remove();
        setCounts(data.counts);
        toast(data.message);
        syncBulk();
      }
    });

    reviewList.addEventListener('submit', async (e) => {
      const form = e.target.closest('[data-review-reply]');
      if (!form) return;
      e.preventDefault();
      const btn = form.querySelector('button');
      btn.disabled = true;
      const { ok, data } = await send('PUT', form.dataset.reviewReply, { reply: form.reply.value });
      btn.disabled = false;
      if (!ok) { showErrors(form, data.details); toast(data.error, false); return; }
      form.closest('[data-review-card]').outerHTML = data.html;
      toast(data.message);
    });

    bulk?.addEventListener('click', async (e) => {
      if (e.target.closest('[data-review-bulk-clear]')) {
        reviewList.querySelectorAll('[data-review-check], [data-review-check-all]').forEach((c) => (c.checked = false));
        syncBulk();
        return;
      }
      const b = e.target.closest('[data-review-bulk-status]');
      if (!b) return;
      b.disabled = true;
      const { ok, data } = await send('POST', reviewList.dataset.endpoint.replace(BASE, '') + '/bulk', { ids: checked(), status: b.dataset.reviewBulkStatus });
      b.disabled = false;
      toast(ok ? data.message : data.error, ok);
      if (ok) reloadReviews();
    });
  }
})();
