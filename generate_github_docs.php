<?php
define('STATIC_BUILD', true);
define('CONFIG_FILE',  __DIR__ . '/config.php');
define('SOURCE_DIR',   __DIR__ . '/source');

$sources = [
    'yla' => [
        'url'         => 'https://github.com/YggdrasilWotLK/mod-yla/archive/refs/heads/master-shadows.zip',
        'title'       => 'YLA API',
        'headers_dir' => 'source/yla/src/LuaEngine/methods',
    ],
];

$repo     = getenv('GITHUB_REPOSITORY') ?: '';
$repoName = $repo ? explode('/', $repo)[1] : '';

// Single flat site served from the repository root: no per-branch nesting.
define('BASE_PATH', '/' . $repoName);

$distDir = __DIR__ . '/dist';
@mkdir($distDir, 0777, true);
@mkdir($distDir . '/assets', 0777, true);

$sourceKey = 'yla';
if (!isset($sources[$sourceKey])) {
    echo "No source mapping for: {$sourceKey}\n";
    exit(1);
}

require_once __DIR__ . '/parse.php';
require_once __DIR__ . '/render.php';

$config = [
    'headers_dir' => __DIR__ . '/' . $sources[$sourceKey]['headers_dir'],
    'site_title'  => $sources[$sourceKey]['title'],
    'setConf'     => 1,
    'refetch'     => ['enabled' => false, 'interval_unit' => 'days', 'interval_value' => 1, 'last_fetched' => 0, 'zip_url' => '', 'dest_key' => ''],
];

$classes     = parse_headers($config['headers_dir']);
$tree        = build_tree($classes);
$searchIndex = build_search_index($classes);
$title       = $config['site_title'];

function rewrite_links(string $html): string {
    $base = BASE_PATH;
    $html = preg_replace_callback('/href="(\?class=([^&"]+)&amp;method=([^"]+))"/', function ($m) use ($base) {
        return 'href="' . $base . '/' . urldecode($m[2]) . '/' . urldecode($m[3]) . '/"';
    }, $html);
    $html = preg_replace_callback('/href="(\?class=([^"&]+))"/', function ($m) use ($base) {
        return 'href="' . $base . '/' . urldecode($m[2]) . '/"';
    }, $html);
    $html = preg_replace('/href="\?"/', 'href="' . $base . '/"', $html);
    return $html;
}

function render_full_page(string $title, string $selectedClass, string $selectedMethod, array $classes, array $tree, array $searchIndex, array $config): string {
    $currentClass = $classes[$selectedClass] ?? null;
    $allMethods   = $currentClass ? get_all_methods($selectedClass, $classes) : [];
    $base         = BASE_PATH;
    $baseJson     = json_encode($base);
    $searchJson   = json_encode($searchIndex, JSON_UNESCAPED_UNICODE);

    ob_start();
    include __DIR__ . '/partials/topbar.php';
    $topbar = ob_get_clean();

    ob_start();
    include __DIR__ . '/partials/sidebar.php';
    $sidebar = ob_get_clean();

    ob_start();
    include __DIR__ . '/partials/content.php';
    $content = ob_get_clean();

    $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$title}</title>
  <link rel="stylesheet" href="{$base}/assets/style.css">
</head>
<body>
<div id="app">
{$topbar}
{$sidebar}
<main id="main">
{$content}
</main>
<div id="sidebar-overlay"></div>
</div>
<script>
(function() {
  const base = {$baseJson};
  function rewrite(url) {
    if (!url) return url;
    url = String(url);
    return url
      .replace(/\?class=([^&]+)&(?:amp;)?method=([^&]+)/, function(_, c, m) {
        return base + '/' + decodeURIComponent(c) + '/' + decodeURIComponent(m) + '/';
      })
      .replace(/\?class=([^&]+)/, function(_, c) {
        return base + '/' + decodeURIComponent(c) + '/';
      })
      .replace(/^\?$/, base + '/');
  }
  document.addEventListener('click', function(e) {
    const a = e.target.closest('a');
    if (!a) return;
    const href = a.getAttribute('href');
    if (!href || !href.startsWith('?')) return;
    e.preventDefault();
    location.href = rewrite(href);
  }, true);

  const index = {$searchJson};
  document.addEventListener('DOMContentLoaded', function() {
    const srBox   = document.getElementById('search-results');
    const srInput = document.getElementById('search');
    if (!srInput || !srBox) return;
    srInput.addEventListener('input', function() {
      const q = srInput.value.trim().toLowerCase();
      if (!q) { srBox.classList.remove('open'); srBox.innerHTML = ''; return; }
      const hits = index.filter(x =>
        x.name.toLowerCase().includes(q) ||
        (x.desc  && x.desc.toLowerCase().includes(q)) ||
        (x.class && x.class.toLowerCase().includes(q))
      );
      if (!hits.length) { srBox.classList.remove('open'); srBox.innerHTML = ''; return; }
      srBox.innerHTML = hits.map(h => {
        const url = h.type === 'class'
          ? base + '/' + encodeURIComponent(h.name) + '/'
          : base + '/' + encodeURIComponent(h.class) + '/' + encodeURIComponent(h.name) + '/';
        const sub = h.type === 'method' ? '<span class="sr-class">' + h.class + '.</span>' : '';
        return '<div class="sr-item" onclick="location.href=\'' + url + '\'">'
          + '<span class="sr-badge ' + h.type + '">' + h.type + '</span>'
          + sub + '<span class="sr-name">' + h.name + '</span>'
          + '<span class="sr-desc">' + (h.desc || '') + '</span>'
          + '</div>';
      }).join('');
      srBox.classList.add('open');
    });
    document.addEventListener('click', function(e) {
      if (!srInput.contains(e.target) && !srBox.contains(e.target)) {
        srBox.classList.remove('open');
        srBox.innerHTML = '';
      }
    });
    srInput.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') { srBox.classList.remove('open'); srBox.innerHTML = ''; srInput.blur(); }
    });
  });
})();
</script>
<script src="{$base}/assets/app.js"></script>
</body>
</html>
HTML;

    return rewrite_links($html);
}

function write_page(string $path, string $html): void {
    @mkdir(dirname($path), 0777, true);
    file_put_contents($path, $html);
}

write_page(
    $distDir . '/index.html',
    render_full_page($title, '', '', $classes, $tree, $searchIndex, $config)
);

foreach ($classes as $className => $cls) {
    $allMethods = get_all_methods($className, $classes);

    write_page(
        $distDir . '/' . $className . '/index.html',
        render_full_page($title, $className, '', $classes, $tree, $searchIndex, $config)
    );

    foreach ($allMethods as $methodName => $method) {
        write_page(
            $distDir . '/' . $className . '/' . $methodName . '/index.html',
            render_full_page($title, $className, $methodName, $classes, $tree, $searchIndex, $config)
        );
    }
}

copy(__DIR__ . '/assets/style.css', $distDir . '/assets/style.css');
copy(__DIR__ . '/assets/app.js',    $distDir . '/assets/app.js');

echo 'Done. ' . count($classes) . " classes written to dist/\n";

