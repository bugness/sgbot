<?php

$ds   = DIRECTORY_SEPARATOR;
$dir  = __DIR__ . $ds . '..' . $ds . 'build';
$file = $dir . $ds . 'sgbot.phar';

if (!file_exists($dir)) {
    mkdir($dir, 0755);
}

if (file_exists($file)) {
    unlink($file);
}

$phar = new Phar($file, 0, 'sgbot.phar');
$test = $phar->buildFromDirectory(dirname(__DIR__), '[app/|src/SGBot|vendor/]');
$phar->setStub('#!/usr/bin/env php' . PHP_EOL . $phar->createDefaultStub('app/console'));
$phar->compressFiles(Phar::GZ);

if (file_exists($file)) {
    chmod($file, 0755);
}
