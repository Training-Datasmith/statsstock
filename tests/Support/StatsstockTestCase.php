<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

class StatsstockTestCase extends PHPUnit_Framework_TestCase
{
  /** @var PDO */
    protected $pdo;

    public function setUp()
    {
        parent::setUp();
        Tools::reset();
        Module::resetHooks(true, true);
        Category::reset();
        Configuration::reset();
        Context::setInstance(new ShopContext());
        Schema::truncateAll($this->pdo);
    }

    protected function assertCleanHtml($html)
    {
        $this->assertInternalType('string', $html);
        $this->assertNotContains('BAD_DOMAIN', $html);
        $this->assertNotContains('BAD_PARAMS', $html);
    }

  /**
   * @return array<int, array<string, string>>
   */
    protected function extractProductRows($html)
    {
        $rows = array();
        $pattern = '/<tr>\s*<td>([^<]*)<\/td>\s*<td>([^<]*)<\/td>\s*<td>([^<]*)<\/td>\s*<td>([^<]*)<\/td>\s*<td>([^<]*)<\/td>\s*<td>([^<]*)<\/td>\s*<\/tr>/';

        if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                if (strpos($match[0], '<thead') !== false || strpos($match[0], '<tfoot') !== false) {
                    continue;
                }

                $rows[] = array(
                    'id' => $match[1],
                    'reference' => $match[2],
                    'name' => $match[3],
                    'quantity' => $match[4],
                    'price' => $match[5],
                    'value' => $match[6],
                );
            }
        }

        return $rows;
    }

  /**
   * @return array{quantity: string, average: string, total: string}|null
   */
    protected function extractFooterTotals($html)
    {
        $pattern = '/<tfoot>.*?<tr>\s*<td colspan="3"><\/td>\s*<td>([^<]*)<\/td>\s*<td>([^<]*)<\/td>\s*<td>([^<]*)<\/td>\s*<\/tr>/s';

        if (!preg_match($pattern, $html, $matches)) {
            return null;
        }

        return array(
            'quantity' => $matches[1],
            'average' => $matches[2],
            'total' => $matches[3],
        );
    }
}
