<?php

declare(strict_types=1);

if ($argc !== 2) {
	fwrite(STDERR, "Usage: php remove-hse-content-deploy.php <wp-config.php>\n");
	exit(64);
}

$wpConfigPath = $argv[1];

if (!is_file($wpConfigPath) || !is_readable($wpConfigPath) || !is_writable($wpConfigPath)) {
	fwrite(STDERR, "The WordPress configuration file is not readable and writable.\n");
	exit(1);
}

$contents = file_get_contents($wpConfigPath);
if ($contents === false) {
	fwrite(STDERR, "Unable to read the WordPress configuration file.\n");
	exit(1);
}

$updated = preg_replace(
	'/^.*\/\/ HSE content deploy configuration[ \t]*(?:\R|$)/m',
	'',
	$contents,
	-1,
	$replacementCount
);

if ($updated === null) {
	fwrite(STDERR, "Unable to sanitize the WordPress configuration.\n");
	exit(1);
}

if ($replacementCount === 0) {
	fwrite(STDOUT, "No production content-deploy include was present.\n");
	exit(0);
}

$temporaryPath = $wpConfigPath . '.without-content-deploy.tmp';
if (file_put_contents($temporaryPath, $updated, LOCK_EX) === false) {
	fwrite(STDERR, "Unable to write the sanitized WordPress configuration.\n");
	exit(1);
}

$permissions = fileperms($wpConfigPath);
if ($permissions !== false) {
	chmod($temporaryPath, $permissions & 0777);
}

if (!rename($temporaryPath, $wpConfigPath)) {
	fwrite(STDERR, "Unable to activate the sanitized WordPress configuration.\n");
	exit(1);
}

fwrite(STDOUT, "Production content-deploy configuration removed.\n");
