<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

class ModuleMetadataTest extends StatsstockTestCase
{
    public function setUp()
    {
        $this->pdo = $GLOBALS['statsstock_test_pdo'];
        parent::setUp();
    }

    public function testConstructorSetsIdentity()
    {
        $module = new statsstock();

        $this->assertSame('statsstock', $module->name);
        $this->assertSame('2.0.2', $module->version);
        $this->assertSame('PrestaShop', $module->author);
        $this->assertSame('administration', $module->tab);
        $this->assertSame(0, $module->need_instance);
    }

    public function testConstructorSetsTranslatedStringsAndCompliancy()
    {
        $module = new statsstock();

        $this->assertSame('Available quantities', $module->displayName);
        $this->assertSame(
            'Enrich your stats, add a tab showing the available quantities of products left for sale.',
            $module->description
        );
        $this->assertSame('1.7.6.0', $module->ps_versions_compliancy['min']);
        $this->assertSame('1.7.8.11', $module->ps_versions_compliancy['max']);
        $this->assertNotContains('BAD_DOMAIN', $module->displayName);
        $this->assertNotContains('BAD_DOMAIN', $module->description);
    }

    public function testNameAndVersionMatchConfigXml()
    {
        $module = new statsstock();
        $xml = simplexml_load_file(dirname(__DIR__) . '/config.xml');

        $this->assertInstanceOf('SimpleXMLElement', $xml);
        $this->assertSame((string) $module->name, (string) $xml->name);
        $this->assertSame((string) $module->version, (string) $xml->version);
    }
}
