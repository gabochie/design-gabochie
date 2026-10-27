<?php
/**
 * Deploy webhook for gabochie/gabochie-design.
 *
 * Routing:
 *   refs/heads/main        -> STAGING (wp.design.gabochie.com)  via deploy-staging.sh
 *   refs/tags/vX.Y.Z       -> LIVE    (design.gabochie.com)     via deploy-live.sh <tag>
 *   refs/tags/auto-*       -> LIVE    (design.gabochie.com)     via deploy-live.sh <tag>
 *
 * Requests are verified with X-Hub-Signature-256 (HMAC-SHA256).
 *
 * The shared secret is NEVER stored in this file. It is read from a 0600 file
 * outside the docroot (default: /home/gabozvqd/deploy/webhook-secret.env) or from
 * the GABOCHIE_WEBHOOK_SECRET environment variable.
 */

$secretFile = getenv('GABOCHIE_WEBHOOK_SECRET_FILE');
if ($secretFile === false || $secretFile === '') {
    $secretFile = '/home/gabozvqd/deploy/webhook-secret.env';
}

$secret = getenv('GABOCHIE_WEBHOOK_SECRET');
if ($secret === false) {
    $secret = '';
}

if ($secret === '' && is_readable($secretFile)) {
    $contents = (string) file_get_contents($secretFile);
    if (preg_match('/^\s*GABOCHIE_WEBHOOK_SECRET\s*=\s*(.+?)\s*$/m', $contents, $m)) {
        $secret = trim($m[1], " \t\"'");
    }
}

if ($secret === '') {
    error_log('gabochie webhook: shared secret not configured');
    http_response_code(500);
    exit('Server secret not configured');
}

$event = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? '';

$payload = file_get_contents('php://input');

if ($event === 'ping') {
    http_response_code(200);
    exit('pong');
}

if ($event !== 'push') {
    http_response_code(202);
    exit('Ignored event: ' . $event);
}

$hash = hash_hmac('sha256', $payload, $secret);
if (!isset($_SERVER['HTTP_X_HUB_SIGNATURE_256'])
    || !hash_equals($_SERVER['HTTP_X_HUB_SIGNATURE_256'], 'sha256=' . $hash)) {
    http_response_code(403);
    exit('Forbidden');
}

$data = json_decode($payload, true);
$ref  = is_array($data) ? ($data['ref'] ?? '') : '';

if ($ref === 'refs/heads/main') {
    exec('bash /home/gabozvqd/deploy/deploy-staging.sh > /home/gabozvqd/deploy/webhook-staging.log 2>&1 &');
    http_response_code(200);
    exit('Staging deploy triggered');
}

if (preg_match('#^refs/tags/(v[0-9][^/]*|auto-[^/]*)$#', $ref, $m)) {
    $tag  = $m[1];
    $safe = escapeshellarg($tag);
    exec("bash /home/gabozvqd/deploy/deploy-live.sh $safe > /home/gabozvqd/deploy/webhook-live.log 2>&1 &");
    http_response_code(200);
    exit('Live deploy triggered: ' . $tag);
}

http_response_code(200);
exit('No matching ref; ignored');
