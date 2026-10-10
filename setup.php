<?php
defined('CONFIG_FILE')      || define('CONFIG_FILE',      __DIR__ . '/config.php');
defined('CONFIG_DIST_FILE') || define('CONFIG_DIST_FILE', __DIR__ . '/config.php.dist');
defined('SOURCE_DIR')       || define('SOURCE_DIR',       __DIR__ . '/source');

if (!function_exists('write_config')) {
    require_once __DIR__ . '/fetch.php';
}

if (file_exists(CONFIG_FILE)) {
    $existing = require CONFIG_FILE;
    if (!empty($existing['setConf'])) {
        header('Location: /');
        exit;
    }
}

$distCfg       = file_exists(CONFIG_DIST_FILE) ? (require CONFIG_DIST_FILE) : [];
$defaultTitle  = $distCfg['site_title']  ?? 'Eluna / ALE API';
$defaultHdrDir = $distCfg['headers_dir'] ?? '';
if (strpos($defaultHdrDir, __DIR__) === 0) {
    $defaultHdrDir = ltrim(str_replace(__DIR__, '', $defaultHdrDir), '/');
}

$sourceExists   = is_dir(SOURCE_DIR);
$sourceWritable = $sourceExists && is_writable(SOURCE_DIR);
$sourceMode     = $sourceExists ? decoct(fileperms(SOURCE_DIR) & 0777) : null;
$rootWritable   = is_writable(__DIR__);

$configWritable = (!file_exists(CONFIG_FILE) && $rootWritable)
               || (file_exists(CONFIG_FILE)  && is_writable(CONFIG_FILE));

$chownError = null;
if (!$configWritable) {
    $wwwUser = posix_getpwuid(posix_geteuid())['name'];
    if (@chown(__DIR__, $wwwUser)) {
        $rootWritable   = is_writable(__DIR__);
        $configWritable = (!file_exists(CONFIG_FILE) && $rootWritable)
                       || (file_exists(CONFIG_FILE)  && is_writable(CONFIG_FILE));
    } else {
        $chownError = 'Could not chown ' . __DIR__ . ' to ' . $wwwUser . ' — run it manually: chown ' . $wwwUser . ' ' . __DIR__;
    }
}

$permsOk     = (($sourceExists && $sourceWritable) || (!$sourceExists && $rootWritable)) && $configWritable;
$hasCurl     = extension_loaded('curl');
$hasZip      = extension_loaded('zip');
$canDownload = $hasCurl && $hasZip;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Setup — Eluna API Docs</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body class="setup">
<div id="setup-card">
  <h1>Site Setup</h1>
  <p class="setup-sub">Complete each step to configure your API documentation site.</p>

  <div class="step-indicator" id="step-indicator"></div>

  <!-- STEP 1: PERMISSIONS -->
  <div class="step active" data-step="1">
    <h2>Permissions</h2>
    <p class="step-desc">
      The <code>source/</code> directory and <code>config.php</code> must be writable by the web server.
    </p>

    <?php if ($sourceExists && $sourceWritable): ?>
      <p><code>source/</code> exists and is writable.</p>
    <?php elseif ($sourceExists && !$sourceWritable): ?>
      <p><code>source/</code> exists but is not writable (mode: <code><?= htmlspecialchars($sourceMode) ?></code>).<br>
      Run: <code>chmod 0777 <?= htmlspecialchars(SOURCE_DIR) ?></code></p>
    <?php elseif (!$sourceExists && $rootWritable): ?>
      <p><code>source/</code> does not exist yet — it will be created automatically.</p>
    <?php else: ?>
      <p>The site root is not writable. Run:<br>
      <code>mkdir <?= htmlspecialchars(SOURCE_DIR) ?> &amp;&amp; chmod 0777 <?= htmlspecialchars(SOURCE_DIR) ?></code></p>
    <?php endif; ?>

    <?php if ($chownError): ?>
      <p><?= htmlspecialchars($chownError) ?></p>
    <?php elseif (!$configWritable): ?>
      <p><code>config.php</code> is not writable. Run:<br>
      <code>chown <?= htmlspecialchars(posix_getpwuid(posix_geteuid())['name']) ?> <?= htmlspecialchars(__DIR__) ?></code></p>
    <?php else: ?>
      <p><code>config.php</code> is writable.</p>
    <?php endif; ?>

    <div class="btn-row">
      <button class="btn secondary" onclick="location.reload()">Reload</button>
      <button class="btn" onclick="nextStep()" <?= !$permsOk ? 'disabled' : '' ?>>Next</button>
    </div>
  </div>

  <!-- STEP 2: BASIC SETTINGS -->
  <div class="step" data-step="2">
    <h2>Basic Settings</h2>
    <p class="step-desc">Set the site title and the path to your Lua method header files.</p>
    <div class="field">
      <label>Site Title</label>
      <input type="text" id="site_title" value="<?= htmlspecialchars($defaultTitle) ?>">
    </div>
    <div class="btn-row">
      <button class="btn secondary" onclick="prevStep()">Back</button>
      <button class="btn" onclick="validateBasic()">Next</button>
    </div>
  </div>

  <!-- STEP 3: SOURCE PATH -->
  <div class="step" data-step="3">
    <h2>Lua Engine Source</h2>
    <p class="step-desc">This site uses local <code>.h</code> files to present the API. Either download source files or enter the path to existing ones manually.</p>

    <div class="setup-subsection">
      <h3>Download</h3>
      <?php if ($canDownload): ?>
        <div class="source-btns">
          <button class="btn" onclick="startFetch('https://github.com/azerothcore/mod-ale/archive/refs/heads/master.zip')">mod-ale</button>
          <button class="btn" onclick="startFetch('https://github.com/ElunaLuaEngine/Eluna/archive/refs/heads/master.zip')">ElunaLuaEngine</button>
          <button class="btn secondary" onclick="toggleCustomUrl()">Custom .zip URL</button>
        </div>
        <div id="custom-url-row">
          <div class="inner">
            <input type="text" id="custom_url" placeholder="https://…/archive/master.zip">
            <button class="btn" onclick="startFetch(document.getElementById('custom_url').value)">Fetch</button>
          </div>
        </div>
        <div id="fetch-status"></div>
        <div id="dir-picker">
          <p>Multiple directories with <code>.h</code> files were found — choose which one to use:</p>
          <div id="dir-list"></div>
          <button class="btn" style="margin-top:10px" onclick="confirmDir()">Use Selected Directory</button>
        </div>
      <?php else: ?>
        <?php if (!$hasCurl): ?>
          <p>Extension <code>php-curl</code> is not loaded. Cannot download source files.<br>
          Run: <code>sudo apt install php-curl &amp;&amp; sudo systemctl restart apache2</code></p>
        <?php endif; ?>
        <?php if (!$hasZip): ?>
          <p>Extension <code>php-zip</code> is not loaded. Cannot process downloaded archives.<br>
          Run: <code>sudo apt install php-zip &amp;&amp; sudo systemctl restart apache2</code></p>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <div class="setup-subsection">
      <h3>Local Source Path</h3>
      <div class="field">
        <input type="text" id="headers_dir" value=""
               placeholder="Default: <?= htmlspecialchars($defaultHdrDir) ?>">
      </div>
      <div id="path-status"></div>
    </div>

    <div class="btn-row">
      <button class="btn secondary" onclick="prevStep()">Back</button>
      <button class="btn" onclick="validatePath()">Next</button>
    </div>
  </div>

  <!-- STEP 4: AUTO RE-FETCH -->
  <div class="step" data-step="4">
    <h2>Auto Re-fetch</h2>
    <p class="step-desc">
      Optionally re-download the source code on a schedule. This requires a URL to have been used in the previous step.
    </p>
    <div class="toggle-row">
      <input type="checkbox" id="refetch_enabled"
             onchange="document.getElementById('refetch-fields').style.display = this.checked ? 'block' : 'none'">
      <label for="refetch_enabled">Automatically re-fetch source code</label>
    </div>
    <div id="refetch-fields">
      <div class="refetch-inline">
        <span style="font-size:13px;color:var(--muted);">Every</span>
        <input type="number" id="refetch_value" value="1" min="1">
        <select id="refetch_unit">
          <option value="minutes">minutes</option>
          <option value="hours">hours</option>
          <option value="days" selected>days</option>
        </select>
      </div>
    </div>
    <div class="btn-row">
      <button class="btn secondary" onclick="prevStep()">Back</button>
      <button class="btn" onclick="nextStep()">Next</button>
    </div>
  </div>

  <!-- STEP 5: SAVE -->
  <div class="step" data-step="5">
    <h2>Save Configuration</h2>
    <p class="step-desc">Review your settings and save the configuration file.</p>
    <div id="summary" style="margin-bottom:16px;font-size:13px;color:var(--muted);line-height:1.8;"></div>
    <div id="save-status"></div>
    <div class="btn-row">
      <button class="btn secondary" onclick="prevStep()">Back</button>
      <button class="btn" onclick="saveConfig()" id="save-btn">Save Config</button>
    </div>
  </div>

</div>

<script>
const TOTAL_STEPS  = 5;
const DEFAULT_PATH = <?= json_encode($defaultHdrDir) ?>;
let currentStep = 1;
let _destKey = '';
let _zipUrl  = '';

function buildIndicator() {
  const el = document.getElementById('step-indicator');
  let html = '';
  for (let i = 1; i <= TOTAL_STEPS; i++) {
    const cls = i < currentStep ? 'done' : i === currentStep ? 'active' : '';
    html += `<div class="step-pip"><div class="step-pip-num ${cls}">${i < currentStep ? '✓' : i}</div></div>`;
    if (i < TOTAL_STEPS) html += `<div class="step-pip"><div class="step-pip-line"></div></div>`;
  }
  el.innerHTML = html;
}

function showStep(n) {
  document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
  document.querySelector(`.step[data-step="${n}"]`).classList.add('active');
  currentStep = n;
  buildIndicator();
  if (n === 5) buildSummary();
}

function nextStep() { showStep(currentStep + 1); }
function prevStep() { showStep(currentStep - 1); }

function validateBasic() {
  const title = document.getElementById('site_title').value.trim();
  if (!title) { alert('Please enter a site title.'); return; }
  nextStep();
}

function validatePath() {
  const input = document.getElementById('headers_dir');
  const hdir  = input.value.trim();

  if (!hdir) {
    if (!DEFAULT_PATH) { alert('Please enter a headers directory.'); return; }
    if (!confirm('Proceed with default path to source files: ' + DEFAULT_PATH + '?')) return;
    input.value = DEFAULT_PATH;
  }

  const pathStatus = document.getElementById('path-status');
  pathStatus.textContent = 'Checking…';

  const fd = new FormData();
  fd.append('action',      'check_headers');
  fd.append('headers_dir', input.value.trim());

  fetch('fetch.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.ok) {
        pathStatus.textContent = 'Found ' + data.count + ' .h file(s).';
        nextStep();
      } else {
        pathStatus.textContent = data.error;
      }
    })
    .catch(() => { pathStatus.textContent = 'Could not verify path.'; });
}

function buildSummary() {
  const title   = document.getElementById('site_title').value.trim();
  const hdir    = document.getElementById('headers_dir').value.trim() || DEFAULT_PATH;
  const refetch = document.getElementById('refetch_enabled').checked;
  const rfVal   = document.getElementById('refetch_value').value;
  const rfUnit  = document.getElementById('refetch_unit').value;
  document.getElementById('summary').innerHTML =
    `<b>Site Title:</b> ${esc(title)}<br>` +
    `<b>Headers Dir:</b> ${esc(hdir)}<br>` +
    `<b>Auto Re-fetch:</b> ${refetch ? 'Every ' + esc(rfVal) + ' ' + esc(rfUnit) : 'Disabled'}`;
}

function toggleCustomUrl() {
  const row = document.getElementById('custom-url-row');
  row.style.display = row.style.display === 'none' ? 'block' : 'none';
}

function setFetchStatus(msg) {
  document.getElementById('fetch-status').textContent = msg;
}

function startFetch(url) {
  url = (url || '').trim();
  if (!url) return;
  document.getElementById('dir-picker').style.display = 'none';
  setFetchStatus('Downloading…');

  const fd = new FormData();
  fd.append('action', 'fetch_zip');
  fd.append('url',    url);

  fetch('fetch.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (!data.ok) {
        setFetchStatus(data.error);
        return;
      }
      _destKey = data.dest_key;
      _zipUrl  = data.zip_url;

      if (!data.conflict || data.dirs.length === 1) {
        useDir(data.dirs[0]);
        setFetchStatus('Downloaded. Headers directory set to: ' + data.dirs[0]);
      } else {
        setFetchStatus('Downloaded. Choose a directory below.');
        showDirPicker(data.dirs);
      }
    })
    .catch(() => setFetchStatus('Network error during fetch.'));
}

function showDirPicker(dirs) {
  const list = document.getElementById('dir-list');
  list.innerHTML = dirs.map((d, i) =>
    `<label class="dir-option">
      <input type="radio" name="dir_pick" value="${esc(d)}"${i === 0 ? ' checked' : ''}>
      <code>${esc(d)}</code>
    </label>`
  ).join('');
  document.getElementById('dir-picker').style.display = 'block';
}

function confirmDir() {
  const picked = document.querySelector('input[name=dir_pick]:checked');
  if (!picked) return;
  useDir(picked.value);
  document.getElementById('dir-picker').style.display = 'none';
  setFetchStatus('Downloaded. Headers directory set to: ' + picked.value);
}

function useDir(dir) {
  document.getElementById('headers_dir').value = dir;
  document.getElementById('path-status').textContent = '';
}

function saveConfig() {
  const btn    = document.getElementById('save-btn');
  const status = document.getElementById('save-status');
  btn.disabled  = true;
  btn.innerHTML = '<span class="spinner"></span> Saving…';

  const hdir = document.getElementById('headers_dir').value.trim() || DEFAULT_PATH;

  const fd = new FormData();
  fd.append('action',          'save_config');
  fd.append('site_title',      document.getElementById('site_title').value.trim());
  fd.append('headers_dir',     hdir);
  fd.append('dest_key',        _destKey);
  fd.append('zip_url',         _zipUrl);
  fd.append('refetch_enabled', document.getElementById('refetch_enabled').checked ? '1' : '');
  fd.append('refetch_unit',    document.getElementById('refetch_unit').value);
  fd.append('refetch_value',   document.getElementById('refetch_value').value);

  fetch('fetch.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.ok) {
        status.textContent = 'Config saved. Redirecting…';
        setTimeout(() => location.href = '', 1200);
      } else {
        status.textContent = data.error || 'Save failed.';
        btn.disabled  = false;
        btn.innerHTML = 'Save Config';
      }
    })
    .catch(() => {
      status.textContent = 'Network error.';
      btn.disabled  = false;
      btn.innerHTML = 'Save Config';
    });
}

function esc(s) {
  return String(s)
    .replace(/&/g, '&amp;').replace(/</g, '&lt;')
    .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

buildIndicator();
</script>
</body>
</html>