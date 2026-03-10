<?php

declare(strict_types=1);

// Minimal mock licensing API endpoint for local testing.
// This folder is NOT part of the WordPress plugin; it's only for developing/testing the client.

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    http_response_code(204);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(array(
        'success' => false,
        'message' => 'Method not allowed. Use POST.',
    ));
    exit;
}

// Accept form-encoded POST (as sent by wp_remote_post body array).
$license_key = isset($_POST['license_key']) ? trim((string) $_POST['license_key']) : '';
$site_url    = isset($_POST['site_url']) ? trim((string) $_POST['site_url']) : '';
$domain_hash = isset($_POST['domain_hash']) ? trim((string) $_POST['domain_hash']) : '';

if ($license_key === '' || $site_url === '' || $domain_hash === '') {
    http_response_code(400);
    echo json_encode(array(
        'success' => false,
        'message' => 'Missing required fields (license_key, site_url, domain_hash).',
    ));
    exit;
}

// Optional sanity check: domain_hash should match sha256(site_url)
$expected_hash = hash('sha256', $site_url);
if (!hash_equals($expected_hash, $domain_hash)) {
    http_response_code(400);
    echo json_encode(array(
        'success' => false,
        'message' => 'Invalid domain_hash.',
    ));
    exit;
}

// Static success response for client-side testing.
http_response_code(200);
echo json_encode(array(
    'success' => true,
    'plan'    => 'pro',
));
