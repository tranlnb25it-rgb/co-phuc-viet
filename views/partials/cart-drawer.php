<?php
/** Slide-over cart shell; its contents come from partials/cart-panel (server render + Ajax). */
use App\Core\View;
use App\Support\Cart;
?>
<div id="cart-drawer" class="pointer-events-none fixed inset-0 z-[60]" data-cart-drawer aria-hidden="true">
  <div class="palette-light absolute inset-0 bg-ink/40 opacity-0 transition-opacity duration-300" data-cart-backdrop data-cart-close></div>
  <aside role="dialog" aria-modal="true" aria-labelledby="cart-title"
         class="absolute inset-y-0 right-0 flex w-full max-w-md translate-x-full flex-col bg-ivory shadow-2xl transition-transform duration-500 ease-silk"
         data-cart-panel>
    <div class="relative flex min-h-0 flex-1 flex-col" data-cart-content>
      <?= View::partial('partials/cart-panel', ['cart' => Cart::summary(lang())]) ?>
    </div>
    <p class="pointer-events-none absolute inset-x-5 bottom-28 hidden rounded-sm bg-ink px-4 py-3 text-center text-sm text-ivory shadow-lg" data-cart-toast role="status"></p>
  </aside>
</div>
