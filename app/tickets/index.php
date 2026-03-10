<?php

declare(strict_types=1);

// Minimal licensing API endpoint (server-side).
// Note: This folder is NOT part of the WordPress plugin repository in production; you will deploy it separately.

// Debug режим (PB_DEBUG) – при true при 403 причината се връща в JSON тялото. За production задай false.
define('PB_DEBUG', true);

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

/**
 * Emit JSON response and exit.
 *
 * @param int    $http_code HTTP status code.
 * @param bool   $success   Success flag.
 * @param string $message   Human-readable message.
 * @param array  $data      Extra fields to include.
 * @param array  $debug     Debug payload (only when PB_DEBUG is true).
 * @return never
 */
function pb_json(int $http_code, bool $success, string $message, array $data = array(), array $debug = array())
{
    http_response_code($http_code);
    $out = array_merge(
        array(
            'success' => $success,
            'message' => $message,
        ),
        $data
    );
    if (defined('PB_DEBUG') && PB_DEBUG && !empty($debug)) {
        $out['_debug'] = $debug;
    }
    echo json_encode($out, JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Simple, file-based rate limiter per IP.
 *
 * @param string $ip
 * @param int    $limit
 * @param int    $window_seconds
 * @return array{allowed: bool, remaining: int, reset_in: int}
 */
function pb_rate_limit(string $ip, int $limit = 60, int $window_seconds = 600): array
{
    $dir = sys_get_temp_dir();
    $key = hash('sha256', 'pb_lic_' . $ip);
    $path = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $key . '.json';
    $now = time();

    $state = array('start' => $now, 'count' => 0);
    $fh = @fopen($path, 'c+');
    if ($fh) {
        @flock($fh, LOCK_EX);
        $raw = stream_get_contents($fh);
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && isset($decoded['start'], $decoded['count'])) {
                $state['start'] = (int) $decoded['start'];
                $state['count'] = (int) $decoded['count'];
            }
        }

        if (($now - $state['start']) >= $window_seconds) {
            $state = array('start' => $now, 'count' => 0);
        }

        $state['count']++;

        ftruncate($fh, 0);
        rewind($fh);
        fwrite($fh, json_encode($state));
        @flock($fh, LOCK_UN);
        fclose($fh);
    } else {
        // If we can't write temp files, fail open (do not block).
        return array('allowed' => true, 'remaining' => $limit, 'reset_in' => $window_seconds);
    }

    $remaining = max(0, $limit - $state['count']);
    $reset_in = max(0, $window_seconds - ($now - $state['start']));
    return array(
        'allowed'   => ($state['count'] <= $limit),
        'remaining' => $remaining,
        'reset_in'  => $reset_in,
    );
}

$method = (string) ($_SERVER['REQUEST_METHOD'] ?? '');
$ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');

// Hard limits for request sizes (basic abuse protection).
$content_length = isset($_SERVER['CONTENT_LENGTH']) ? (int) $_SERVER['CONTENT_LENGTH'] : 0;
if ($content_length > 4096) {
    pb_json(413, false, 'Payload too large.');
}

// Rate limit (server-to-server; this is mostly to avoid accidental loops / abuse).
$rl = pb_rate_limit($ip, 120, 600); // 120 requests / 10 minutes / IP
if (!$rl['allowed']) {
    pb_json(429, false, 'Too many requests. Please retry later.', array(), array('ip' => $ip, 'reset_in' => $rl['reset_in']));
}

if ($method !== 'POST') {
    pb_json(405, false, 'Method not allowed. Use POST.');
}

// Accept form-encoded POST (as sent by wp_remote_post body array).
$license_key = isset($_POST['license_key']) ? trim((string) $_POST['license_key']) : '';
$site_url    = isset($_POST['site_url']) ? trim((string) $_POST['site_url']) : '';
$domain_hash = isset($_POST['domain_hash']) ? trim((string) $_POST['domain_hash']) : '';

if ($license_key === '' || $site_url === '' || $domain_hash === '') {
    pb_json(400, false, 'Missing required fields (license_key, site_url, domain_hash).');
}

// Basic input validation / normalization
if (strlen($license_key) < 3 || strlen($license_key) > 128) {
    pb_json(400, false, 'Invalid license_key length.');
}
if (!preg_match('/^[A-Za-z0-9._\\-]+$/', $license_key)) {
    pb_json(400, false, 'Invalid license_key format.');
}

$site_url_norm = rtrim($site_url, "/ \t\n\r\0\x0B");
$url_parts = @parse_url($site_url_norm);
if (!is_array($url_parts) || empty($url_parts['scheme']) || empty($url_parts['host'])) {
    pb_json(400, false, 'Invalid site_url.');
}

// Optional sanity check: domain_hash should match sha256(site_url) (allow with/without trailing slash)
$expected_hash_1 = hash('sha256', $site_url);
$expected_hash_2 = hash('sha256', $site_url_norm);
if (!hash_equals($expected_hash_1, $domain_hash) && !hash_equals($expected_hash_2, $domain_hash)) {
    pb_json(
        400,
        false,
        'Invalid domain_hash.',
        array(),
        array(
            'expected_hash_1' => $expected_hash_1,
            'expected_hash_2' => $expected_hash_2,
            'received'        => $domain_hash,
        )
    );
}

// ---- DB-backed licensing (single-domain, no expiration) ----
// Config is stored outside webroot.
$ini_path = '/home/avalonbg/configtickets.ini';
$ini = @parse_ini_file($ini_path, true, INI_SCANNER_TYPED);
if (!is_array($ini) || empty($ini['database']) || !is_array($ini['database'])) {
    pb_json(500, false, 'Server configuration error.', array(), array('ini_path' => $ini_path, 'step' => 'parse_ini_file'));
}

$db_user = isset($ini['database']['username']) ? (string) $ini['database']['username'] : '';
$db_pass = isset($ini['database']['password']) ? (string) $ini['database']['password'] : '';
$db_name = isset($ini['database']['dbname']) ? (string) $ini['database']['dbname'] : '';
if ($db_user === '' || $db_name === '') {
    pb_json(500, false, 'Server configuration error.', array(), array('ini_path' => $ini_path, 'step' => 'missing_db_fields'));
}

try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=' . $db_name . ';charset=utf8mb4',
        $db_user,
        $db_pass,
        array(
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        )
    );
} catch (Throwable $e) {
    pb_json(500, false, 'Database connection failed.', array(), array('error' => $e->getMessage()));
}

$license_key_hash = hash('sha256', $license_key);
$now = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
$table = 'license_activations';

try {
    $stmt = $pdo->prepare("SELECT id, domain_hash, site_url, plan, status FROM {$table} WHERE license_key_hash = :h LIMIT 1");
    $stmt->execute(array(':h' => $license_key_hash));
    $row = $stmt->fetch();
} catch (Throwable $e) {
    pb_json(500, false, 'Database query failed.', array(), array('error' => $e->getMessage()));
}

if (!$row) {
    pb_json(200, false, 'Invalid license key.', array('plan' => 'free'));
}

$status = isset($row['status']) ? (string) $row['status'] : 'revoked';
if ($status !== 'active') {
    pb_json(200, false, 'License revoked.', array('plan' => 'free'));
}

$plan = isset($row['plan']) ? (string) $row['plan'] : 'pro';
$plan = ($plan === 'pro') ? 'pro' : 'free';

// First activation: bind license to this domain_hash.
$db_domain_hash = isset($row['domain_hash']) ? trim((string) $row['domain_hash']) : '';
if ($db_domain_hash === '') {
    try {
        $sql = "UPDATE {$table} SET domain_hash = ?, site_url = ?, activated_at = ?, last_check_at = ? WHERE id = ? AND (domain_hash IS NULL OR domain_hash = '')";
        $up  = $pdo->prepare($sql);
        $up->execute(array($domain_hash, $site_url_norm, $now, $now, (int) $row['id']));
    } catch (Throwable $e) {
        pb_json(500, false, 'Database update failed.', array(), array('error' => $e->getMessage()));
    }
    // Re-read effective binding (in case of race).
    try {
        $stmt = $pdo->prepare("SELECT domain_hash, plan, status FROM {$table} WHERE id = :id LIMIT 1");
        $stmt->execute(array(':id' => (int) $row['id']));
        $row2 = $stmt->fetch();
        if (is_array($row2)) {
            $db_domain_hash = isset($row2['domain_hash']) ? trim((string) $row2['domain_hash']) : $db_domain_hash;
            $status = isset($row2['status']) ? (string) $row2['status'] : $status;
            $plan = (isset($row2['plan']) && (string) $row2['plan'] === 'pro') ? 'pro' : 'free';
        }
    } catch (Throwable $e) {
        // Ignore; fallback to what we have.
    }
}

// If bound to another domain -> deny.
if ($db_domain_hash !== '' && !hash_equals($db_domain_hash, $domain_hash)) {
    pb_json(
        200,
        false,
        'License already activated for another domain.',
        array('plan' => 'free'),
        array('bound_domain_hash' => $db_domain_hash)
    );
}

// Update last_check_at (best effort).
try {
    $up = $pdo->prepare("UPDATE {$table} SET last_check_at = :now WHERE id = :id");
    $up->execute(array(':now' => $now, ':id' => (int) $row['id']));
} catch (Throwable $e) {
    // Non-fatal.
}

pb_json(
    200,
    true,
    'OK',
    array('plan' => $plan),
    array(
        'ip'   => $ip,
        'host' => (string) ($url_parts['host'] ?? ''),
        'rl'   => $rl,
    )
);
