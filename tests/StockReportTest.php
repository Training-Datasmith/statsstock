<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

class StockReportTest extends StatsstockTestCase
{
    public function setUp()
    {
        $this->pdo = $GLOBALS['statsstock_test_pdo'];
        parent::setUp();
    }

    public function testEmptyCatalogReturnsFormAndEmptyMessage()
    {
        $module = new statsstock();
        $html = $module->hookDisplayAdminStatsModules();

        $this->assertCleanHtml($html);
        $this->assertContains('Evaluation of available quantities for sale', $html);
        $this->assertContains('Your catalog is empty.', $html);
        $this->assertContains('<option value="0">- All -</option>', $html);
        $this->assertContains('name="submitCategory" value="1"', $html);
        $this->assertContains('name="statsstock_id_category"', $html);
        $this->assertContains('$(\'#calendar\').slideToggle();', $html);
        $this->assertContains('action="out:AdminStats?module=statsstock"', $html);
        $this->assertNotContains('<table', $html);
        $this->assertNotContains(
            'This section corresponds to the default wholesale price according to the default supplier for the product. An average price is used when the product has attributes.',
            $html
        );
    }

    public function testEmptyCategoryFilterStillReturnsTheForm()
    {
        Schema::seedTwoProducts($this->pdo);
        Tools::setSubmit('submitCategory');
        Tools::setValue('statsstock_id_category', '4');

        $module = new statsstock();
        $html = $module->hookDisplayAdminStatsModules();

        $this->assertCleanHtml($html);
        $this->assertContains('Your catalog is empty.', $html);
        $this->assertNotContains('Café table', $html);
        $this->assertNotContains('Pine board', $html);
        $this->assertNotContains('<table', $html);
    }

    public function testUnfilteredReportComputesRowValuesAndUnweightedAverage()
    {
        Schema::seedTwoProducts($this->pdo);
        $module = new statsstock();
        $html = $module->hookDisplayAdminStatsModules();

        $this->assertCleanHtml($html);
        $rows = $this->extractProductRows($html);
        $this->assertCount(2, $rows);

        $this->assertSame('CAF', $rows[0]['reference']);
        $this->assertSame('Café table', $rows[0]['name']);
        $this->assertSame('1', $rows[0]['quantity']);
        $this->assertSame('10.0000000000', $rows[0]['price']);
        $this->assertSame('10', $rows[0]['value']);

        $this->assertSame('PIN', $rows[1]['reference']);
        $this->assertSame('Pine board', $rows[1]['name']);
        $this->assertSame('3', $rows[1]['quantity']);
        $this->assertSame('30.0000000000', $rows[1]['price']);
        $this->assertSame('90', $rows[1]['value']);

        $footer = $this->extractFooterTotals($html);
        $this->assertNotNull($footer);
        $this->assertSame('4', $footer['quantity']);
        $this->assertSame('20', $footer['average']);
        $this->assertSame('100', $footer['total']);

        $this->assertContains('Total quantities', $html);
        $this->assertContains('Average price', $html);
        $this->assertContains('Total value', $html);
        $this->assertContains('action="out:AdminStats?module=statsstock"', $html);
        $this->assertNotContains('FMT:', $html);
    }

    public function testAttributeWholesaleAverageExcludesZeros()
    {
        Schema::insertProduct($this->pdo, 1, 'VAR', 100, 'Variant mix', 7, 1, 4, 5);
        Schema::insertProductAttribute($this->pdo, 11, 1, 10, 1);
        Schema::insertProductAttribute($this->pdo, 12, 1, 30, 1);
        Schema::insertProductAttribute($this->pdo, 13, 1, 0, 1);

        $module = new statsstock();
        $html = $module->hookDisplayAdminStatsModules();
        $rows = $this->extractProductRows($html);

        $this->assertCount(1, $rows);
        $this->assertSame('20.0000000000', $rows[0]['price']);
        $this->assertSame('80', $rows[0]['value']);
        $this->assertNotContains('<td>100</td>', $html);
        $this->assertNotContains('<td>400</td>', $html);
    }

    public function testAllZeroAttributePricesFallBackToProductWholesale()
    {
        Schema::insertProduct($this->pdo, 1, 'ZERO', 7, 'Zero attrs', 7, 1, 2, 5);
        Schema::insertProductAttribute($this->pdo, 21, 1, 0, 1);
        Schema::insertProductAttribute($this->pdo, 22, 1, 0, 1);

        $module = new statsstock();
        $html = $module->hookDisplayAdminStatsModules();
        $rows = $this->extractProductRows($html);

        $this->assertCount(1, $rows);
        $this->assertSame('7.0000000000', $rows[0]['price']);
        $this->assertSame('14', $rows[0]['value']);
    }

    public function testMissingStockRowShowsZeroQuantity()
    {
        $stmt = $this->pdo->prepare('INSERT INTO ps_product (id_product, reference) VALUES (?, ?)');
        $stmt->execute(array(1, 'NOSTK'));
        $stmt = $this->pdo->prepare('INSERT INTO ps_product_shop (id_product, id_shop, wholesale_price) VALUES (?, ?, ?)');
        $stmt->execute(array(1, 1, 8));
        $stmt = $this->pdo->prepare('INSERT INTO ps_product_lang (id_product, id_lang, id_shop, name) VALUES (?, ?, ?, ?)');
        $stmt->execute(array(1, 7, 1, 'No stock row'));

        $module = new statsstock();
        $html = $module->hookDisplayAdminStatsModules();
        $rows = $this->extractProductRows($html);

        $this->assertCount(1, $rows);
        $this->assertSame('0', $rows[0]['quantity']);
        $this->assertSame('0', $rows[0]['value']);
    }

    public function testNegativeQuantityProducesNegativeValue()
    {
        Schema::insertProduct($this->pdo, 1, 'NEG', 5, 'Negative qty', 7, 1, -2, 5);

        $module = new statsstock();
        $html = $module->hookDisplayAdminStatsModules();
        $rows = $this->extractProductRows($html);
        $footer = $this->extractFooterTotals($html);

        $this->assertCount(1, $rows);
        $this->assertSame('-2', $rows[0]['quantity']);
        $this->assertSame('5.0000000000', $rows[0]['price']);
        $this->assertSame('-10', $rows[0]['value']);
        $this->assertNotNull($footer);
        $this->assertSame('-2', $footer['quantity']);
        $this->assertSame('5', $footer['average']);
        $this->assertSame('-10', $footer['total']);
    }

    public function testSubmittedCategoryFiltersTheCatalog()
    {
        Schema::seedTwoProducts($this->pdo);
        Tools::setSubmit('submitCategory');
        Tools::setValue('statsstock_id_category', '5');

        $module = new statsstock();
        $html = $module->hookDisplayAdminStatsModules();

        $this->assertContains('Café table', $html);
        $this->assertNotContains('Pine board', $html);
        $this->assertContains('value="5" selected="selected"', $html);
        $this->assertNotContains('value="9" selected="selected"', $html);
    }

    public function testSubmittedCategoryIsCastToInteger()
    {
        Schema::seedTwoProducts($this->pdo);
        Tools::setSubmit('submitCategory');
        Tools::setValue('statsstock_id_category', '5 OR 1=1');

        $module = new statsstock();
        $html = $module->hookDisplayAdminStatsModules();

        $this->assertContains('Café table', $html);
        $this->assertNotContains('Pine board', $html);

        $count = $this->pdo->query('SELECT COUNT(*) FROM ps_product')->fetchColumn();
        $this->assertInternalType('string', $count);
        $this->assertSame(2, (int) $count);
    }

    public function testCookieStaysPutWhenCategoryWasNotSubmitted()
    {
        Schema::seedTwoProducts($this->pdo);
        $module = new statsstock();
        $module->context->cookie->statsstock_id_category = '9';
        Tools::setValue('statsstock_id_category', '5');

        $html = $module->hookDisplayAdminStatsModules();

        $this->assertContains('Pine board', $html);
        $this->assertNotContains('Café table', $html);
        $this->assertContains('value="9" selected="selected"', $html);
    }

    public function testSelectedOptionMatchesTheCookieCategory()
    {
        Schema::seedTwoProducts($this->pdo);
        $module = new statsstock();
        $module->context->cookie->statsstock_id_category = '5';

        $html = $module->hookDisplayAdminStatsModules();

        $this->assertContains('value="5" selected="selected"', $html);
        $this->assertNotContains('value="9" selected="selected"', $html);
    }

    public function testNamesFollowLanguageAndShopRestriction()
    {
        $stmt = $this->pdo->prepare('INSERT INTO ps_product (id_product, reference) VALUES (?, ?)');
        $stmt->execute(array(1, 'OAK'));
        $stmt = $this->pdo->prepare('INSERT INTO ps_product_shop (id_product, id_shop, wholesale_price) VALUES (?, ?, ?)');
        $stmt->execute(array(1, 1, 10));
        Schema::insertProductLang($this->pdo, 1, 7, 1, 'Oak board');
        Schema::insertProductLang($this->pdo, 1, 3, 1, 'Planche');
        Schema::insertProductLang($this->pdo, 1, 7, 2, 'Other shop oak');
        $stmt = $this->pdo->prepare('INSERT INTO ps_stock_available (id_product, id_product_attribute, quantity) VALUES (?, 0, ?)');
        $stmt->execute(array(1, 1));
        $stmt = $this->pdo->prepare('INSERT INTO ps_category_product (id_category, id_product) VALUES (?, ?)');
        $stmt->execute(array(5, 1));

        $module = new statsstock();
        $html = $module->hookDisplayAdminStatsModules();

        $this->assertContains('Oak board', $html);
        $this->assertContains('Boards', $html);
        $this->assertNotContains('Planche', $html);
        $this->assertNotContains('Other shop oak', $html);
        $this->assertNotContains('Planches', $html);
        $this->assertSame(1, substr_count($html, 'Oak board'));
    }

    public function testLocaleFormatsPricesWithTheDefaultCurrency()
    {
        Schema::seedTwoProducts($this->pdo);
        $module = new statsstock();
        $context = new LocaleShopContext();
        $context->cookie = $module->context->cookie;
        $module->context = $context;

        $html = $module->hookDisplayAdminStatsModules();
        $rows = $this->extractProductRows($html);
        $footer = $this->extractFooterTotals($html);

        $this->assertSame('FMT:10.00:EUR', $rows[0]['price']);
        $this->assertSame('FMT:10.00:EUR', $rows[0]['value']);
        $this->assertSame('FMT:30.00:EUR', $rows[1]['price']);
        $this->assertSame('FMT:90.00:EUR', $rows[1]['value']);
        $this->assertSame('1', $rows[0]['quantity']);
        $this->assertSame('3', $rows[1]['quantity']);
        $this->assertNotNull($footer);
        $this->assertSame('FMT:20.00:EUR', $footer['average']);
        $this->assertSame('FMT:100.00:EUR', $footer['total']);
        $this->assertNotContains(':USD', $html);
    }
}
