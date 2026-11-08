<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

if (PHP_VERSION !== '5.6.40') {
    fwrite(STDERR, 'Expected PHP 5.6.40, got ' . PHP_VERSION . PHP_EOL);
    exit(1);
}

$root = dirname(__DIR__);

define('_PS_VERSION_', '1.7.8.11');
define('_DB_PREFIX_', 'ps_');

$socket = getenv('STATSSTOCK_MYSQL_SOCKET');
if (!$socket || !file_exists($socket)) {
    fwrite(STDERR, 'STATSSTOCK_MYSQL_SOCKET must point to a MariaDB socket' . PHP_EOL);
    exit(1);
}

$dsn = 'mysql:unix_socket=' . $socket . ';dbname=statsstock;charset=utf8mb4';
$pdo = new PDO($dsn, 'root', '', array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
));

require_once $root . '/tests/Support/PrestaShopDouble.php';
require_once $root . '/tests/Support/Schema.php';
require_once $root . '/tests/Support/StatsstockTestCase.php';

Schema::createTables($pdo);
Db::setPdo($pdo);

$GLOBALS['statsstock_test_pdo'] = $pdo;

require_once $root . '/statsstock.php';
require_once $root . '/upgrade/upgrade-2.0.1.php';
