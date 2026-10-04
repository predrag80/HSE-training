<?php

declare(strict_types=1);

if ($argc !== 3) {
    fwrite(STDERR, "Usage: php configure-hse-turnstile.php <wp-config.php> <private-config.php>\n");
    exit(64);
}

$wpConfigPath = $argv[1];
$privateConfigPath = $argv[2];

if (!is_file($wpConfigPath) || !is_readable($wpConfigPath) || !is_writable($wpConfigPath)) {
    fwrite(STDERR, "The WordPress configuration file is not readable and writable.\n");
    exit(1);
}

if (!is_file($privateConfigPath) || !is_readable($privateConfigPath)) {
    fwrite(STDERR, "The private Turnstile configuration file is missing.\n");
    exit(1);
}

$contents = file_get_contents($wpConfigPath);
if ($contents === false) {
    fwrite(STDERR, "Unable to read the WordPress configuration file.\n");
    exit(1);
}

$marker = '// HSE Turnstile configuration';
$requireLine = sprintf(
    "require_once %s; %s",
    var_export($privateConfigPath, true),
    $marker
);

if (str_contains($contents, $marker)) {
    $updated = preg_replace(
        '/^.*\/\/ HSE Turnstile configuration[ \t]*$/m',
        $requireLine,
        $contents,
        1,
        $replacementCount
    );

    if ($updated === null || $replacementCount !== 1) {
        fwrite(STDERR, "Unable to update the existing Turnstile configuration include.\n");
        exit(1);
    }
} else {
    $pattern = '/^\s*require_once\s+ABSPATH\s*\.\s*[\'\"]wp-settings\.php[\'\"]\s*;\s*$/m';
    $updated = preg_replace($pattern, $requireLine . "\n\n$0", $contents, 1, $replacementCount);

    if ($updated === null || $replacementCount !== 1) {
        fwrite(STDERR, "Unable to locate the WordPress bootstrap include.\n");
        exit(1);
    }
}

if ($updated === $contents) {
    fwrite(STDOUT, "WordPress Turnstile configuration is already connected.\n");
    exit(0);
}

$backupPath = sprintf('%s.before-hse-turnstile-%s', $wpConfigPath, gmdate('YmdHis'));
if (!copy($wpConfigPath, $backupPath)) {
    fwrite(STDERR, "Unable to create a backup of wp-config.php.\n");
    exit(1);
}

$temporaryPath = $wpConfigPath . '.hse-turnstile.tmp';
if (file_put_contents($temporaryPath, $updated, LOCK_EX) === false) {
    fwrite(STDERR, "Unable to write the updated WordPress configuration.\n");
    exit(1);
}

$permissions = fileperms($wpConfigPath);
if ($permissions !== false) {
    chmod($temporaryPath, $permissions & 0777);
}

if (!rename($temporaryPath, $wpConfigPath)) {
    fwrite(STDERR, "Unable to activate the updated WordPress configuration.\n");
    exit(1);
}

fwrite(STDOUT, "WordPress Turnstile configuration connected successfully.\n");
