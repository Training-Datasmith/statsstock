<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

$entryFile = getenv('ENTRY_FILE');
$sentinel = getenv('SENTINEL');
$mode = getenv('ENTRY_MODE');

if (!$entryFile || !$sentinel) {
    fwrite(STDERR, 'ENTRY_FILE and SENTINEL are required' . PHP_EOL);
    exit(3);
}

register_shutdown_function(function () use ($sentinel) {
    $status = class_exists('statsstock', false) ? 'has-class' : 'no-class';
    file_put_contents($sentinel, $status);
});

if ($mode === 'module-with-version') {
    define('_PS_VERSION_', '1.7.8.11');

    class Module
    {
        public function __construct()
        {
        }

        public function trans($id, $parameters, $domain)
        {
            return $id;
        }
    }

    include $entryFile;
    exit(0);
}

include $entryFile;

file_put_contents($sentinel, 'returned');
exit(2);
