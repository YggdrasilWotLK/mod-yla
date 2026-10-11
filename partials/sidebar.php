<?php
/** @var array  $classes */
/** @var string $selectedClass */
/** @var array  $tree */
?>
<aside id="sidebar">
  <div id="class-panel">
    <div id="sidebar-header">Classes</div>
    <div id="sidebar-tree"></div>
  </div>
  <div id="method-panel">
    <?php include __DIR__ . '/method_list.php'; ?>
  </div>
  <button id="sidebar-collapse" aria-label="Collapse sidebar" title="Collapse sidebar">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="11 17 6 12 11 7"/><polyline points="18 17 13 12 18 7"/></svg>
    <span>Collapse sidebar</span>
  </button>
</aside>
<button id="sidebar-expand" aria-label="Expand sidebar" title="Expand sidebar">
  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="13 17 18 12 13 7"/><polyline points="6 17 11 12 6 7"/></svg>
</button>

<script>
  window.TREE_DATA      = <?= json_encode($tree, JSON_UNESCAPED_UNICODE) ?>;
  window.CLASSES_DATA   = <?= json_encode(
      array_map(fn($c) => [
          'name'        => $c['name'],
          'inherits'    => $c['inherits'],
          'methodCount' => count($c['methods']),
      ], $classes),
      JSON_UNESCAPED_UNICODE
  ) ?>;
  window.SELECTED_CLASS = <?= json_encode($selectedClass) ?>;
</script>