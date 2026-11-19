<?php
/** Cloud — lotus — cloud divider. @var string $class */
$cloud = '<svg viewBox="0 0 120 24" class="h-4 w-24 sm:w-32%s" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">'
       . '<path d="M2 16h46"/><path d="M48 16c0-6 5-10 10-10s9 4 9 8-3 6-6 6-5-2-5-4 2-3 3-3"/>'
       . '<path d="M67 14c3-5 9-7 14-4 4 2 5 7 3 10"/><path d="M84 20c6 0 10-3 12-7 3-5 9-7 14-5"/></svg>';
?>
<div class="flex items-center justify-center gap-3 text-gold <?= e($class ?? '') ?>" role="presentation">
  <?= sprintf($cloud, ' -scale-x-100') ?>
  <?= lotus('h-6 w-6') ?>
  <?= sprintf($cloud, '') ?>
</div>
