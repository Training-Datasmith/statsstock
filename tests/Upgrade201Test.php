<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

class Upgrade201Test extends StatsstockTestCase
{
    public function setUp()
    {
        $this->pdo = $GLOBALS['statsstock_test_pdo'];
        parent::setUp();
    }

    public function testUpgradeReplacesLegacyHookWithDisplayHook()
    {
        $module = new statsstock();

        $this->assertTrue(upgrade_module_2_0_1($module));
        $this->assertSame(
            array(
                array('unregister', 'AdminStatsModules'),
                array('register', 'displayAdminStatsModules'),
            ),
            Module::$hookLog
        );
    }
}
