<?php
/** Heart toggle (Ajax, see app.js). @var int $id product id @var bool $saved @var string $class @var bool $large */
$large ??= false;
?>
<button type="button" data-wishlist="<?= $id ?>" aria-pressed="<?= $saved ? 'true' : 'false' ?>"
        data-label-add="<?= e(t('wishlist.add')) ?>" data-label-remove="<?= e(t('wishlist.remove')) ?>"
        aria-label="<?= e(t($saved ? 'wishlist.remove' : 'wishlist.add')) ?>" title="<?= e(t($saved ? 'wishlist.remove' : 'wishlist.add')) ?>"
        class="group/wish flex items-center justify-center rounded-full border transition <?= $large ? 'h-12 w-12 border-line bg-white/60 hover:border-crimson' : 'h-9 w-9 border-transparent bg-ivory/90 shadow-sm hover:scale-110' ?> <?= e($class ?? '') ?>">
  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"
       class="<?= $large ? 'h-5 w-5' : 'h-4.5 w-4.5' ?> text-crimson transition fill-transparent group-aria-pressed/wish:fill-crimson"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
</button>
