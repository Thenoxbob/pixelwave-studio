<?php
declare(strict_types=1);

// Amorçage commun aux tests (PHPUnit ou tests/run.php)

$config = getenv('PIXELWAVE_CONFIG') ?: __DIR__ . '/config.test.php';
if (!is_file($config)) {
    fwrite(STDERR, "Copier tests/config.test.example.php en tests/config.test.php\n");
    exit(2);
}
putenv('PIXELWAVE_CONFIG=' . $config);

if (!class_exists('PHPUnit\Framework\TestCase')) {
    require __DIR__ . '/lib/TestCaseCompat.php';
}
require dirname(__DIR__) . '/src/bootstrap.php';
require __DIR__ . '/lib/BaseDeTest.php';
