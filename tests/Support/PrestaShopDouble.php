<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

class Module
{
    public $name;
    public $version;
    public $tab;
    public $author;
    public $need_instance;
    public $displayName;
    public $description;
    public $ps_versions_compliancy;
    public $context;

  /** @var bool */
    private static $parentInstallResult = true;

  /** @var bool */
    private static $hookActionResult = true;

  /** @var array<int, array{0: string, 1: string}> */
    public static $hookLog = array();

  /** @var array<string, string> */
    private static $translationDomains = array(
        'Available quantities' => 'Modules.Statsstock.Admin',
        'Enrich your stats, add a tab showing the available quantities of products left for sale.' => 'Modules.Statsstock.Admin',
        'Evaluation of available quantities for sale' => 'Modules.Statsstock.Admin',
        'Your catalog is empty.' => 'Modules.Statsstock.Admin',
        'Ref.' => 'Modules.Statsstock.Admin',
        'Price*' => 'Modules.Statsstock.Admin',
        'Total quantities' => 'Modules.Statsstock.Admin',
        'Total value' => 'Modules.Statsstock.Admin',
        'This section corresponds to the default wholesale price according to the default supplier for the product. An average price is used when the product has attributes.' => 'Modules.Statsstock.Admin',
        'Category' => 'Admin.Global',
        'All' => 'Admin.Global',
        'ID' => 'Admin.Global',
        'Item' => 'Admin.Global',
        'Available quantity for sale' => 'Admin.Global',
        'Average price' => 'Admin.Global',
        'Value' => 'Admin.Global',
    );

    public function __construct()
    {
        $this->context = Context::getContext();
    }

    public function install()
    {
        return self::$parentInstallResult;
    }

    public function registerHook($name)
    {
        self::$hookLog[] = array('register', $name);

        return self::$hookActionResult;
    }

    public function unregisterHook($name)
    {
        self::$hookLog[] = array('unregister', $name);

        return self::$hookActionResult;
    }

    public function trans($id, $parameters, $domain)
    {
        if (!is_array($parameters) || count($parameters) !== 0) {
            return 'BAD_PARAMS:' . $id;
        }

        if (!isset(self::$translationDomains[$id]) || self::$translationDomains[$id] !== $domain) {
            return 'BAD_DOMAIN:' . $id;
        }

        return $id;
    }

    public static function resetHooks($parentInstallResult, $hookActionResult)
    {
        self::$hookLog = array();
        self::$parentInstallResult = (bool) $parentInstallResult;
        self::$hookActionResult = (bool) $hookActionResult;
    }
}

class Context
{
  /** @var Context|null */
    private static $instance;

  /** @return Context */
    public static function getContext()
    {
        if (self::$instance === null) {
            self::$instance = new ShopContext();
        }

        return self::$instance;
    }

    public static function setInstance($context)
    {
        self::$instance = $context;
    }
}

class Cookie
{
  /** @var array<string, mixed> */
    private $data = array();

    public function __get($name)
    {
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }

        return null;
    }

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }
}

class ShopContext
{
  /** @var Cookie */
    public $cookie;

  /** @var object */
    public $language;

  /** @var Link */
    public $link;

    public function __construct()
    {
        $this->cookie = new Cookie();
        $this->language = new stdClass();
        $this->language->id = 7;
        $this->link = new Link();
    }
}

class LocaleFormatter
{
    public function formatPrice($amount, $iso)
    {
        return 'FMT:' . number_format((float) $amount, 2, '.', '') . ':' . $iso;
    }
}

class LocaleShopContext extends ShopContext
{
    public function getCurrentLocale()
    {
        return new LocaleFormatter();
    }
}

class Link
{
    public function getAdminLink($controller, $withToken = true, $extraParams = array(), $params = array())
    {
        $module = isset($params['module']) ? $params['module'] : '';

        return $controller . '?module=' . $module;
    }
}

class Tools
{
  /** @var array<string, bool> */
    private static $submit = array();

  /** @var array<string, string> */
    private static $values = array();

    public static function reset()
    {
        self::$submit = array();
        self::$values = array();
    }

    public static function setSubmit($key, $value = true)
    {
        self::$submit[$key] = (bool) $value;
    }

    public static function setValue($key, $value)
    {
        self::$values[$key] = (string) $value;
    }

    public static function isSubmit($key)
    {
        return isset(self::$submit[$key]) && self::$submit[$key];
    }

    public static function getValue($key)
    {
        if (!isset(self::$values[$key])) {
            return null;
        }

        return self::$values[$key];
    }

    public static function safeOutput($string)
    {
        return 'out:' . $string;
    }
}

class Shop
{
    public static function addSqlAssociation($table, $alias)
    {
        if ($table === 'product' && $alias === 'p') {
            return ' INNER JOIN ps_product_shop product_shop ON (product_shop.id_product = p.id_product AND product_shop.id_shop = 1) ';
        }

        if ($table === 'product_attribute' && $alias === 'pa') {
            return ' INNER JOIN ps_product_attribute_shop product_attribute_shop ON (product_attribute_shop.id_product_attribute = pa.id_product_attribute AND product_attribute_shop.id_shop = 1) ';
        }

        throw new RuntimeException('Unexpected Shop::addSqlAssociation(' . $table . ', ' . $alias . ')');
    }

    public static function addSqlRestrictionOnLang($alias)
    {
        if ($alias !== 'pl') {
            throw new RuntimeException('Unexpected Shop::addSqlRestrictionOnLang(' . $alias . ')');
        }

        return ' AND pl.id_shop = 1 ';
    }
}

class Product
{
    public static function sqlStock($alias, $idProductAttribute)
    {
        if ($alias !== 'p' || (int) $idProductAttribute !== 0) {
            throw new RuntimeException('Unexpected Product::sqlStock(' . $alias . ', ' . $idProductAttribute . ')');
        }

        return ' LEFT JOIN ps_stock_available stock ON (stock.id_product = p.id_product AND stock.id_product_attribute = 0) ';
    }
}

class Category
{
  /** @var array<int, array<int, array<string, mixed>>> */
    private static $categoriesByLang = array(
        7 => array(
            array('id_category' => 5, 'name' => 'Boards'),
            array('id_category' => 9, 'name' => 'Nails'),
        ),
        3 => array(
            array('id_category' => 5, 'name' => 'Planches'),
        ),
    );

    public static function reset()
    {
        self::$categoriesByLang = array(
            7 => array(
                array('id_category' => 5, 'name' => 'Boards'),
                array('id_category' => 9, 'name' => 'Nails'),
            ),
            3 => array(
                array('id_category' => 5, 'name' => 'Planches'),
            ),
        );
    }

    public static function setCategoriesForLang($idLang, $categories)
    {
        self::$categoriesByLang[(int) $idLang] = $categories;
    }

    public static function getSimpleCategories($idLang)
    {
        $idLang = (int) $idLang;

        if (!isset(self::$categoriesByLang[$idLang])) {
            return array();
        }

        return self::$categoriesByLang[$idLang];
    }
}

class Configuration
{
    public static function reset()
    {
    }

    public static function get($key)
    {
        if ($key === 'PS_CURRENCY_DEFAULT') {
            return '3';
        }

        return false;
    }
}

class Currency
{
    public $iso_code;

    public function __construct($id)
    {
        $this->iso_code = ((int) $id === 3) ? 'EUR' : 'USD';
    }
}

class Db
{
  /** @var Db|null */
    private static $instance;

  /** @var PDO */
    private $pdo;

    private function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public static function setPdo(PDO $pdo)
    {
        self::$instance = new Db($pdo);
    }

  /** @return Db */
    public static function getInstance()
    {
        if (self::$instance === null) {
            throw new RuntimeException('Db is not configured');
        }

        return self::$instance;
    }

    public function executeS($sql)
    {
        $statement = $this->pdo->query($sql);

        if ($statement === false) {
            $error = $this->pdo->errorInfo();

            throw new RuntimeException(isset($error[2]) ? $error[2] : 'SQL query failed');
        }

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}
