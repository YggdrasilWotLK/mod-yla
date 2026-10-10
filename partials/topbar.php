<?php
/** @var string $title */
/** @var array  $searchIndex */
?>
<header id="topbar">
  <button id="hamburger" aria-label="Toggle sidebar">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
    </svg>
  </button>
  <a id="logo" href="?">
    <svg width="36" height="36" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
      <circle cx="18" cy="18" r="18" fill="#6b21d6"/>
      <text x="18" y="13" text-anchor="middle" font-family="'Segoe UI',sans-serif"
        font-weight="700" font-size="6.5" fill="#e9d5ff" letter-spacing="0.3">LUA</text>
      <text x="18" y="23" text-anchor="middle" font-family="'Consolas',monospace"
        font-weight="700" font-size="7.5" fill="#f3e8ff" letter-spacing="0.5">API</text>
      <path d="M8 27 Q18 31 28 27" stroke="#c4b5fd" stroke-width="1.2" fill="none" stroke-linecap="round"/>
    </svg>
    <span><?= htmlspecialchars($title) ?></span>
  </a>
  <div id="search-wrap">
    <svg id="search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none"
      stroke="currentColor" stroke-width="2">
      <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
    </svg>
    <input id="search" type="text" placeholder="Search classes and methods…"
      autocomplete="off" spellcheck="false">
    <div id="search-results"></div>
  </div>
  <button id="theme-toggle" aria-label="Toggle theme">
    <svg id="theme-icon-sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <circle cx="12" cy="12" r="5"/>
      <line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/>
      <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
      <line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/>
      <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
    </svg>
    <svg id="theme-icon-moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none">
      <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
    </svg>
  </button>
</header>
<script>
  window.SEARCH_INDEX = <?= json_encode($searchIndex, JSON_UNESCAPED_UNICODE) ?>;
</script>