# Architecture: statsstock

## Purpose

A PrestaShop statistics module that reports current stock levels, highlighting products that are low in stock or out of stock to assist with reorder planning.

## Directory Structure

```
statsstock.php   - Module class (ModuleGrid subclass); all business logic
upgrade/         - Migration scripts
tests/           - PHPUnit test stubs and PHPStan bootstrap
translations/    - Locale string overrides
```

## Key Design Decisions

- **ModuleGrid inheritance**: Renders a sortable grid with low-stock filtering.
- **Threshold-based colouring**: Applies CSS classes to highlight products below a configurable minimum stock threshold.

## Extension Points

- Change the low-stock threshold by modifying the `$low_stock_threshold` property.
- Override `getData()` to include warehouse or supplier info.

## Dependency Flow

```
statsstock (ModuleGrid)
  └─> hookDisplayAdminStatsModules() — renders the stock report grid
  └─> getData()                      — stock level query with threshold filter
        └─> Db::getInstance()
```
