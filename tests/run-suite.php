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
$expectedFiles = array(
    'tests/GuardAndIndexTest.php',
    'tests/InstallHookTest.php',
    'tests/ModuleMetadataTest.php',
    'tests/StockReportTest.php',
    'tests/Upgrade201Test.php',
);
$expectedTestCount = 27;

$expectedAbsolute = array();
foreach ($expectedFiles as $relative) {
    $expectedAbsolute[] = $root . '/' . $relative;
}

$discovered = glob($root . '/tests/*Test.php');
if ($discovered === false) {
    fwrite(STDERR, 'Failed to scan tests/*Test.php' . PHP_EOL);
    exit(1);
}

sort($discovered);
sort($expectedAbsolute);

if ($discovered !== $expectedAbsolute) {
    fwrite(STDERR, 'Test file list drift: expected exactly ' . count($expectedFiles) . ' files in tests/' . PHP_EOL);
    fwrite(STDERR, '  expected: ' . implode(', ', $expectedFiles) . PHP_EOL);
    fwrite(STDERR, '  on disk: ' . implode(', ', array_map(function ($path) use ($root) {
        return substr($path, strlen($root) + 1);
    }, $discovered)) . PHP_EOL);
    exit(1);
}

$actualTestCount = 0;

foreach ($expectedFiles as $relative) {
  /** @var string $absolute */
    $absolute = $root . '/' . $relative;
    if (!is_file($absolute)) {
        fwrite(STDERR, 'Missing test file: ' . $relative . PHP_EOL);
        exit(1);
    }

    $source = file_get_contents($absolute);
    if (preg_match_all('/function\s+test\w+\s*\(/', $source, $matches)) {
        $actualTestCount += count($matches[0]);
    }
}

if ($actualTestCount !== $expectedTestCount) {
    fwrite(STDERR, 'Test count drift: expected ' . $expectedTestCount . ', found ' . $actualTestCount . PHP_EOL);
    exit(1);
}

$absoluteFiles = array();
foreach ($expectedFiles as $relative) {
    $absoluteFiles[] = $root . '/' . $relative;
}

srand(20261108);
shuffle($absoluteFiles);

$runtime = getenv('STATSSTOCK_RUNTIME');
if (!$runtime) {
    $runtime = sys_get_temp_dir() . '/statsstock-phpunit-' . getmypid();
    if (!is_dir($runtime)) {
        mkdir($runtime, 0700, true);
    }
}

$phar = getenv('STATSSTOCK_PHPUNIT_PHAR');
if (!$phar || !is_file($phar)) {
    fwrite(STDERR, 'STATSSTOCK_PHPUNIT_PHAR must point to a verified phpunit phar' . PHP_EOL);
    exit(1);
}

$xmlPath = $runtime . '/phpunit-shuffle.xml';
$xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
$xml .= "<phpunit bootstrap=\"" . $root . "/tests/bootstrap.php\" colors=\"false\"";
$xml .= " convertErrorsToExceptions=\"true\" convertNoticesToExceptions=\"true\" convertWarningsToExceptions=\"true\"";
$xml .= " backupGlobals=\"false\" backupStaticAttributes=\"false\">\n";
$xml .= "  <testsuites>\n";
$xml .= "    <testsuite name=\"module-tests\">\n";

foreach ($absoluteFiles as $file) {
    $xml .= '      <file>' . htmlspecialchars($file, ENT_QUOTES, 'UTF-8') . "</file>\n";
}

$xml .= "    </testsuite>\n";
$xml .= "  </testsuites>\n";
$xml .= "</phpunit>\n";
file_put_contents($xmlPath, $xml);

$command = escapeshellarg(PHP_BINARY)
    . ' -d error_reporting=-1 -d display_errors=1 -d display_startup_errors=1 -d html_errors=0'
    . ' ' . escapeshellarg($phar)
    . ' --report-useless-tests --disallow-test-output'
    . ' --configuration ' . escapeshellarg($xmlPath);

$descriptors = array(
    0 => array('pipe', 'r'),
    1 => array('pipe', 'w'),
    2 => array('pipe', 'w'),
);

$process = proc_open($command, $descriptors, $pipes, $root);

if (!is_resource($process)) {
    fwrite(STDERR, 'Failed to start PHPUnit' . PHP_EOL);
    exit(1);
}

fclose($pipes[0]);
$stdout = stream_get_contents($pipes[1]);
$stderr = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$exitCode = proc_close($process);

fwrite(STDOUT, $stdout);

if ($stderr !== '') {
    fwrite(STDERR, $stderr);
}

$forbidden = array(
    'OK, but incomplete',
    'FAILURES',
    'ERRORS',
    'Skipped:',
    'Risky:',
    'Incomplete:',
    'Warning:',
    'Notice:',
    'Deprecated:',
    'Strict Standards:',
    'Parse error:',
    'Fatal error:',
);

foreach ($forbidden as $needle) {
    if (strpos($stdout . $stderr, $needle) !== false || strpos($stderr, $needle) !== false) {
        fwrite(STDERR, 'Forbidden output marker: ' . $needle . PHP_EOL);
        exit(1);
    }
}

if ($stderr !== '') {
    fwrite(STDERR, 'PHPUnit stderr must be empty' . PHP_EOL);
    exit(1);
}

if ($exitCode !== 0) {
    fwrite(STDERR, 'PHPUnit exited with code ' . $exitCode . PHP_EOL);
    exit(1);
}

if (!preg_match('/^OK \\(27 tests, [0-9]+ assertions\\)$/m', $stdout)) {
    fwrite(STDERR, 'Missing clean OK summary for 27 tests' . PHP_EOL);
    exit(1);
}

echo "run-suite: passed\n";
exit(0);
