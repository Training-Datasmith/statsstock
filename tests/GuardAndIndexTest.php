<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

class GuardAndIndexTest extends StatsstockTestCase
{
    public function setUp()
    {
        $this->pdo = $GLOBALS['statsstock_test_pdo'];
        parent::setUp();
    }

  /**
   * @param string $entryRelative
   * @param string $expectedSentinel
   * @param string $mode
   */
    private function runGuardedEntry($entryRelative, $expectedSentinel, $mode = '')
    {
        $root = dirname(__DIR__);
        $entryFile = $root . '/' . $entryRelative;
        $sentinel = tempnam(sys_get_temp_dir(), 'statsstock-sentinel-');

        $env = array(
            'ENTRY_FILE' => $entryFile,
            'SENTINEL' => $sentinel,
        );

        if ($mode !== '') {
            $env['ENTRY_MODE'] = $mode;
        }

        $command = escapeshellarg(PHP_BINARY)
            . ' -d error_reporting=-1 -d display_errors=1 -d display_startup_errors=1'
            . ' -d html_errors=0 -d log_errors=0 -d output_buffering=0'
            . ' ' . escapeshellarg($root . '/tests/bin/run-entry.php');

        $descriptorSpec = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w'),
        );

        $process = proc_open($command, $descriptorSpec, $pipes, $root, $env);

        $this->assertTrue(is_resource($process));

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        $this->assertSame(0, $exitCode, 'stdout=' . $stdout . ' stderr=' . $stderr);
        $this->assertSame('', $stdout);
        $this->assertSame('', $stderr);
        $this->assertNotRegExp('/Notice:|Warning:|Deprecated:|Strict Standards:|Parse error:|Fatal error:/', $stdout . $stderr);
        $this->assertFileExists($sentinel);
        $this->assertSame($expectedSentinel, trim(file_get_contents($sentinel)));

        unlink($sentinel);
    }

    public function testRootIndexExitsCleanly()
    {
        $this->runGuardedEntry('index.php', 'no-class');
    }

    public function testTestsIndexExitsCleanly()
    {
        $this->runGuardedEntry('tests/index.php', 'no-class');
    }

    public function testPhpstanIndexExitsCleanly()
    {
        $this->runGuardedEntry('tests/phpstan/index.php', 'no-class');
    }

    public function testTranslationsIndexExitsCleanly()
    {
        $this->runGuardedEntry('translations/index.php', 'no-class');
    }

    public function testUpgradeIndexExitsCleanly()
    {
        $this->runGuardedEntry('upgrade/index.php', 'no-class');
    }

    public function testModuleExitsWhenPsVersionIsMissing()
    {
        $this->runGuardedEntry('statsstock.php', 'no-class');
    }

    public function testModuleDefinesClassWhenPsVersionIsPresent()
    {
        $this->runGuardedEntry('statsstock.php', 'has-class', 'module-with-version');
    }
}
