<?php
/** @var array  $allMethods */
/** @var string $selectedClass */
/** @var string $selectedMethod */
/** @var array  $currentClass */
/** @var array  $classes */
?>
<nav id="method-list">
  <div id="method-header">
    <?= $currentClass ? htmlspecialchars($selectedClass) . ' Methods' : 'All Methods' ?>
  </div>

  <?php if ($currentClass): ?>
    <?php foreach ($allMethods as $mn => $m): ?>
      <?php $isInh = isset($m['inherited_from']); ?>
      <a class="method-item<?= $mn === $selectedMethod ? ' active' : '' ?><?= $isInh ? ' inherited' : '' ?>"
         href="?class=<?= urlencode($selectedClass) ?>&method=<?= urlencode($mn) ?>">
        <span class="<?= $isInh ? 'inh-dot' : 'own-dot' ?>"></span>
        <?= htmlspecialchars($mn) ?>
      </a>
    <?php endforeach; ?>
  <?php else: ?>
    <?php
      $seen = [];
      foreach ($classes as $className => $cls) {
          foreach ($cls['methods'] as $mn => $m) {
              if (isset($seen[$mn])) continue;
              $seen[$mn] = $className;
              echo '<a class="method-item" href="?class=' . urlencode($className) . '&method=' . urlencode($mn) . '">'
                 . '<span class="own-dot"></span>'
                 . htmlspecialchars($mn)
                 . '<span style="font-size:11px;color:var(--muted);margin-left:4px;">' . htmlspecialchars($className) . '</span>'
                 . '</a>';
          }
      }
    ?>
  <?php endif; ?>
</nav>