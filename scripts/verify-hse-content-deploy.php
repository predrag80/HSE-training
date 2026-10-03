<?php

declare(strict_types=1);

if ($argc !== 2) {
    fwrite(STDERR, "Usage: php verify-hse-content-deploy.php <wordpress-root>\n");
    exit(64);
}

$wordpressRoot = rtrim($argv[1], '/');
$bootstrapPath = $wordpressRoot . '/wp-load.php';

if (!is_file($bootstrapPath) || !is_readable($bootstrapPath)) {
    fwrite(STDERR, "The WordPress bootstrap file is unavailable.\n");
    exit(1);
}

require $bootstrapPath;

$triggerClass = 'HSETraining\\Headless\\Infrastructure\\ContentDeployTrigger';
if (!class_exists($triggerClass) || !is_callable([$triggerClass, 'dispatch'])) {
    fwrite(STDERR, "The content-deploy trigger is unavailable.\n");
    exit(1);
}

$triggerClass::dispatch();

$status = get_option('hse_content_deploy_status', []);
$httpCode = is_array($status) ? (int) ($status['http_code'] ?? 0) : 0;
if (!is_array($status) || ($status['status'] ?? '') !== 'queued' || $httpCode < 200 || $httpCode >= 300) {
    fwrite(STDERR, "The production deploy request was not accepted by GitHub.\n");
    exit(1);
}

fwrite(STDOUT, "Production content deploy request accepted by GitHub.\n");
