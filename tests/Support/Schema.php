<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

class Schema
{
    public static function createTables(PDO $pdo)
    {
        $pdo->exec('CREATE TABLE IF NOT EXISTS ps_product (
            id_product INT NOT NULL,
            reference VARCHAR(64) NOT NULL,
            PRIMARY KEY (id_product)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $pdo->exec('CREATE TABLE IF NOT EXISTS ps_product_shop (
            id_product INT NOT NULL,
            id_shop INT NOT NULL,
            wholesale_price DECIMAL(20,6) NULL,
            PRIMARY KEY (id_product, id_shop)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $pdo->exec('CREATE TABLE IF NOT EXISTS ps_product_lang (
            id_product INT NOT NULL,
            id_lang INT NOT NULL,
            id_shop INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            PRIMARY KEY (id_product, id_lang, id_shop)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $pdo->exec('CREATE TABLE IF NOT EXISTS ps_product_attribute (
            id_product_attribute INT NOT NULL,
            id_product INT NOT NULL,
            PRIMARY KEY (id_product_attribute)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $pdo->exec('CREATE TABLE IF NOT EXISTS ps_product_attribute_shop (
            id_product_attribute INT NOT NULL,
            id_shop INT NOT NULL,
            wholesale_price DECIMAL(20,6) NOT NULL,
            PRIMARY KEY (id_product_attribute, id_shop)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $pdo->exec('CREATE TABLE IF NOT EXISTS ps_stock_available (
            id_product INT NOT NULL,
            id_product_attribute INT NOT NULL,
            quantity INT NOT NULL,
            PRIMARY KEY (id_product, id_product_attribute)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $pdo->exec('CREATE TABLE IF NOT EXISTS ps_category_product (
            id_category INT NOT NULL,
            id_product INT NOT NULL,
            PRIMARY KEY (id_category, id_product)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public static function truncateAll(PDO $pdo)
    {
        $tables = array(
            'ps_category_product',
            'ps_stock_available',
            'ps_product_attribute_shop',
            'ps_product_attribute',
            'ps_product_lang',
            'ps_product_shop',
            'ps_product',
        );

        foreach ($tables as $table) {
            $pdo->exec('DELETE FROM ' . $table);
        }
    }

    public static function insertProduct(PDO $pdo, $idProduct, $reference, $wholesale, $name, $idLang, $idShop, $quantity, $idCategory)
    {
        $stmt = $pdo->prepare('INSERT INTO ps_product (id_product, reference) VALUES (?, ?)');
        $stmt->execute(array((int) $idProduct, $reference));

        $stmt = $pdo->prepare('INSERT INTO ps_product_shop (id_product, id_shop, wholesale_price) VALUES (?, ?, ?)');
        $stmt->execute(array((int) $idProduct, (int) $idShop, $wholesale));

        $stmt = $pdo->prepare('INSERT INTO ps_product_lang (id_product, id_lang, id_shop, name) VALUES (?, ?, ?, ?)');
        $stmt->execute(array((int) $idProduct, (int) $idLang, (int) $idShop, $name));

        $stmt = $pdo->prepare('INSERT INTO ps_stock_available (id_product, id_product_attribute, quantity) VALUES (?, 0, ?)');
        $stmt->execute(array((int) $idProduct, (int) $quantity));

        $stmt = $pdo->prepare('INSERT INTO ps_category_product (id_category, id_product) VALUES (?, ?)');
        $stmt->execute(array((int) $idCategory, (int) $idProduct));
    }

    public static function insertProductLang(PDO $pdo, $idProduct, $idLang, $idShop, $name)
    {
        $stmt = $pdo->prepare('INSERT INTO ps_product_lang (id_product, id_lang, id_shop, name) VALUES (?, ?, ?, ?)');
        $stmt->execute(array((int) $idProduct, (int) $idLang, (int) $idShop, $name));
    }

    public static function insertProductAttribute(PDO $pdo, $idProductAttribute, $idProduct, $wholesale, $idShop)
    {
        $stmt = $pdo->prepare('INSERT INTO ps_product_attribute (id_product_attribute, id_product) VALUES (?, ?)');
        $stmt->execute(array((int) $idProductAttribute, (int) $idProduct));

        $stmt = $pdo->prepare('INSERT INTO ps_product_attribute_shop (id_product_attribute, id_shop, wholesale_price) VALUES (?, ?, ?)');
        $stmt->execute(array((int) $idProductAttribute, (int) $idShop, $wholesale));
    }

    public static function seedTwoProducts(PDO $pdo)
    {
        self::insertProduct($pdo, 1, 'CAF', 10, 'Café table', 7, 1, 1, 5);
        self::insertProduct($pdo, 2, 'PIN', 30, 'Pine board', 7, 1, 3, 9);
    }
}
