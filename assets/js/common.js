/* Cổ Phục Việt — small UI touches shared by the shop and the admin:
   an eye button on every password field, and a "back to top" button. */
(function () {
  'use strict';

  const en = document.documentElement.lang === 'en';
  const L = en
    ? { show: 'Show password', hide: 'Hide password', top: 'Back to top' }
    : { show: 'Hiện mật khẩu', hide: 'Ẩn mật khẩu', top: 'Lên đầu trang' };
  const svg = (paths, cls = 'h-4.5 w-4.5') =>
    `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="${cls}" aria-hidden="true">${paths}</svg>`;
  const EYE = '<path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/>';
  const EYE_OFF = '<path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"/><path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"/><path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"/><path d="m2 2 20 20"/>';
  const ARROW_UP = '<path d="m5 12 7-7 7 7"/><path d="M12 19V5"/>';

  // ---------- Show / hide password ----------
  /** Wrap a password input and add an eye button inside its right edge. */
  const enhance = (input) => {
    if (input.dataset.pwToggle) return;
    input.dataset.pwToggle = '1';

    const wrap = document.createElement('span');
    wrap.className = 'relative block';
    // the input's top margin moves to the wrapper so the button stays vertically centred
    [...input.classList].filter((c) => /^mt-/.test(c)).forEach((c) => { input.classList.remove(c); wrap.classList.add(c); });
    input.parentNode.insertBefore(wrap, input);
    wrap.appendChild(input);
    input.classList.add('pr-11');

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'absolute right-1 top-1/2 flex -translate-y-1/2 items-center justify-center rounded-sm p-2 text-ink-soft transition hover:text-crimson focus-visible:text-crimson';
    const render = () => {
      const shown = input.type === 'text';
      btn.innerHTML = svg(shown ? EYE_OFF : EYE);
      btn.setAttribute('aria-label', shown ? L.hide : L.show);
      btn.setAttribute('aria-pressed', shown ? 'true' : 'false');
      btn.title = shown ? L.hide : L.show;
    };
    btn.addEventListener('click', (e) => {
      e.preventDefault();   // don't let the surrounding <label> steal the click
      const pos = input.selectionStart;
      input.type = input.type === 'password' ? 'text' : 'password';
      render();
      input.focus();
      try { input.setSelectionRange(pos, pos); } catch { /* not supported on this type */ }
    });
    // hide the password again before the form is sent, so the browser can still offer to save it
    input.form?.addEventListener('submit', () => { if (input.type === 'text') { input.type = 'password'; render(); } });
    render();
    wrap.appendChild(btn);
  };
  const scan = (root) => root.querySelectorAll?.('input[type=password]').forEach(enhance);
  scan(document);
  // sections loaded over Ajax (account pages, dialogs) get the button too
  new MutationObserver((list) => list.forEach((m) => m.addedNodes.forEach((n) => {
    if (n.nodeType !== 1) return;
    if (n.matches('input[type=password]')) enhance(n); else scan(n);
  }))).observe(document.body, { childList: true, subtree: true });

  // ---------- Back to top ----------
  const top = document.createElement('button');
  top.type = 'button';
  top.className = 'pointer-events-none fixed bottom-6 right-4 z-40 flex h-11 w-11 translate-y-4 items-center justify-center rounded-full bg-crimson text-ivory opacity-0 shadow-lg ring-2 ring-gold/60 transition duration-300 hover:-translate-y-0.5 hover:bg-crimson-dark sm:right-6';
  top.innerHTML = svg(ARROW_UP, 'h-5 w-5');
  top.setAttribute('aria-label', L.top);
  top.title = L.top;
  top.tabIndex = -1;
  document.body.appendChild(top);

  let visible = false;
  const onScroll = () => {
    const show = window.scrollY > 500;
    if (show === visible) return;
    visible = show;
    top.classList.toggle('opacity-0', !show);
    top.classList.toggle('translate-y-4', !show);
    top.classList.toggle('pointer-events-none', !show);
    top.tabIndex = show ? 0 : -1;
  };
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
  top.addEventListener('click', () => {
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' });
    document.querySelector('a[href], button, input')?.focus({ preventScroll: true });   // keyboard users continue from the top
  });
})();
