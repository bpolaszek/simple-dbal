<?php

use BenTools\SimpleDBAL\Tests\TestSuite;

require_once __DIR__ . '/../vendor/autoload.php';

$settings = TestSuite::getSettings();
$credentials = TestSuite::getCredentialsFromSettings($settings);

// Test mysqli driver is on
if (!class_exists('mysqli')) {

    print PHP_EOL . PHP_EOL;
    print 'Cannot run test suite: Mysqli extension is not enabled.';
    print PHP_EOL . PHP_EOL;
    exit;
}

// Test mysqli driver is on
if (!class_exists('PDO')) {

    print PHP_EOL . PHP_EOL;
    print 'Cannot run test suite: PDO extension is not enabled.';
    print PHP_EOL . PHP_EOL;
    exit;
}

try {
    $link = new PDO(sprintf('mysql:host=%s;port=%d', $credentials->getHostname(), $credentials->getPort()), $credentials->getUser(), $credentials->getPassword(), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {

    print PHP_EOL . PHP_EOL;
    print 'Cannot run test suite: invalid credentials.';
    print PHP_EOL . PHP_EOL;
    exit;
}

try {
    $link->query(sprintf('USE %s;', $credentials->getDatabase()));
} catch (Exception $e) {
    print PHP_EOL . PHP_EOL;
    printf('Cannot run test suite: database %s can not be opened.', $credentials->getDatabase());
    print PHP_EOL . PHP_EOL;
    exit;
}
