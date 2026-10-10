<?php
defined('SOURCE_DIR')  || define('SOURCE_DIR',  __DIR__ . '/source');
defined('CONFIG_FILE') || define('CONFIG_FILE', __DIR__ . '/config.php');

function fetch_zip(string $url): array {
    $tmp = tempnam(sys_get_temp_dir(), 'eluna_') . '.zip';
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 120,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT      => 'ElunaAPIDocs/1.0',
    ]);
    $data  = curl_exec($ch);
    $code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    if ($data === false || $code !== 200) {
        return ['ok' => false, 'path' => null, 'code' => $code, 'error' => $error];
    }
    file_put_contents($tmp, $data);
    return ['ok' => true, 'path' => $tmp, 'code' => $code, 'error' => ''];
}

function extract_zip(string $zipPath, string $destDir): bool {
    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) return false;
    $zip->extractTo($destDir);
    $zip->close();
    return true;
}

function find_header_dirs(string $base): array {
    $dirs = [];
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iter as $entry) {
        if (!$entry->isDir()) continue;
        if (!empty(glob($entry->getPathname() . '/*.h'))) {
            $dirs[] = $entry->getPathname();
        }
    }
    return array_values(array_unique($dirs));
}

function dirs_have_duplicate_files(array $dirs): bool {
    $seen = [];
    foreach ($dirs as $dir) {
        foreach (glob($dir . '/*.h') as $f) {
            $base = basename($f);
            if (isset($seen[$base])) return true;
            $seen[$base] = true;
        }
    }
    return false;
}

function delete_dir(string $dir): void {
    if (!is_dir($dir)) return;
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $f) {
        $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
    }
    rmdir($dir);
}

function do_refetch(array $config): bool {
    $url     = $config['refetch']['zip_url']  ?? '';
    $destKey = $config['refetch']['dest_key'] ?? '';
    if (!$url || !$destKey) return false;

    $dest = SOURCE_DIR . '/' . $destKey;
    if (is_dir($dest)) delete_dir($dest);

    $zip = fetch_zip($url);
    if (!$zip['ok']) return false;

    mkdir($dest, 0777, true);
    extract_zip($zip['path'], $dest);
    unlink($zip['path']);
    return true;
}

function write_config(array $cfg): void {
    file_put_contents(
        CONFIG_FILE,
        "<?php\nreturn " . var_export($cfg, true) . ";\n"
    );
}

function resolve_headers_dir(string $headersDir): string {
    return (strpos($headersDir, '/') === 0)
        ? $headersDir
        : __DIR__ . '/' . $headersDir;
}

// ── AJAX ──────────────────────────────────────────────────────────────────────
if (basename($_SERVER['SCRIPT_FILENAME']) !== 'fetch.php') return;

if (!isset($_POST['action'])) exit;

header('Content-Type: application/json');

if ($_POST['action'] === 'check_headers') {
    $headersDir = trim($_POST['headers_dir'] ?? '');
    if (!$headersDir) {
        echo json_encode(['ok' => false, 'error' => 'No path provided']);
        exit;
    }
    $abs   = resolve_headers_dir($headersDir);
    $files = is_dir($abs) ? glob($abs . '/*.h') : [];
    if (empty($files)) {
        echo json_encode(['ok' => false, 'error' => 'No .h files found in: ' . $abs]);
        exit;
    }
    echo json_encode(['ok' => true, 'count' => count($files)]);
    exit;
}

if ($_POST['action'] === 'fetch_zip') {
    if (!extension_loaded('curl') || !extension_loaded('zip')) {
        echo json_encode(['ok' => false, 'error' => 'curl and zip PHP extensions are required for downloading']);
        exit;
    }

    $url = trim($_POST['url'] ?? '');
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        echo json_encode(['ok' => false, 'error' => 'Invalid URL']);
        exit;
    }

    if (!is_dir(SOURCE_DIR)) {
        mkdir(SOURCE_DIR, 0777, true);
    }

    $key  = preg_replace('/[^a-z0-9_-]/i', '_', parse_url($url, PHP_URL_HOST) . '_' . time());
    $dest = SOURCE_DIR . '/' . $key;
    mkdir($dest, 0777, true);

    $zip = fetch_zip($url);
    if (!$zip['ok']) {
        delete_dir($dest);
        echo json_encode(['ok' => false, 'error' => 'Download failed — HTTP ' . $zip['code'] . ' — ' . $zip['error']]);
        exit;
    }

    extract_zip($zip['path'], $dest);
    unlink($zip['path']);

    $hdirs = find_header_dirs($dest);
    if (empty($hdirs)) {
        delete_dir($dest);
        echo json_encode(['ok' => false, 'error' => 'No .h files found in the downloaded archive']);
        exit;
    }

    $conflict = dirs_have_duplicate_files($hdirs);
    $relDirs  = array_map(fn($d) => str_replace(__DIR__ . '/', '', $d), $hdirs);

    echo json_encode([
        'ok'       => true,
        'dirs'     => $relDirs,
        'conflict' => $conflict,
        'dest_key' => $key,
        'zip_url'  => $url,
    ]);
    exit;
}

if ($_POST['action'] === 'save_config') {
    $headersDir  = trim($_POST['headers_dir']  ?? '');
    $siteTitle   = trim($_POST['site_title']   ?? '');
    $destKey     = trim($_POST['dest_key']     ?? '');
    $zipUrl      = trim($_POST['zip_url']      ?? '');
    $refetchOn   = !empty($_POST['refetch_enabled']);
    $refetchUnit = in_array($_POST['refetch_unit'] ?? '', ['minutes', 'hours', 'days'])
                       ? $_POST['refetch_unit'] : 'days';
    $refetchVal  = max(1, (int)($_POST['refetch_value'] ?? 1));

    if (!$headersDir || !$siteTitle) {
        echo json_encode(['ok' => false, 'error' => 'Site Title and Headers Directory are required']);
        exit;
    }

    $configWritable = (!file_exists(CONFIG_FILE) && is_writable(__DIR__))
                   || (file_exists(CONFIG_FILE)  && is_writable(CONFIG_FILE));

    if (!$configWritable) {
        echo json_encode(['ok' => false, 'error' => 'config.php is not writable — check file/directory permissions']);
        exit;
    }

    write_config([
        'headers_dir' => resolve_headers_dir($headersDir),
        'site_title'  => $siteTitle,
        'setConf'     => 1,
        'refetch'     => [
            'enabled'        => $refetchOn,
            'interval_unit'  => $refetchUnit,
            'interval_value' => $refetchVal,
            'last_fetched'   => 0,
            'zip_url'        => $zipUrl,
            'dest_key'       => $destKey,
        ],
    ]);

    echo json_encode(['ok' => true]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Unknown action']);