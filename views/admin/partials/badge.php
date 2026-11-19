<?php /** @var array $map key => [label, classes] @var string $key */
[$label, $cls] = $map[$key] ?? [$key, 'bg-line text-ink']; ?>
<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium <?= $cls ?>"><?= e($label) ?></span>
