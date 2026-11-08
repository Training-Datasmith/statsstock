<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

class InstallHookTest extends StatsstockTestCase
{
    public function setUp()
    {
        $this->pdo = $GLOBALS['statsstock_test_pdo'];
        parent::setUp();
    }

    public function testInstallRegistersDisplayHookWhenParentSucceeds()
    {
        Module::resetHooks(true, true);
        $module = new statsstock();

        $this->assertTrue($module->install());
        $this->assertSame(
            array(array('register', 'displayAdminStatsModules')),
            Module::$hookLog
        );
    }

    public function testInstallReturnsFalseAndSkipsHookWhenParentFails()
    {
        Module::resetHooks(false, true);
        $module = new statsstock();

        $this->assertFalse($module->install());
        $this->assertSame(array(), Module::$hookLog);
    }

    public function testInstallReturnsFalseWhenHookRegistrationFails()
    {
        Module::resetHooks(true, false);
        $module = new statsstock();

        $this->assertFalse($module->install());
        $this->assertSame(
            array(array('register', 'displayAdminStatsModules')),
            Module::$hookLog
        );
    }
}
