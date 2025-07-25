# dbal-query-builder-wrapper

A modern PHP query builder tool for building SQL queries, compatible with Doctrine DBAL and Symfony.

## Features
- Build complex SQL queries programmatically
- Integrates with Doctrine DBAL
- PSR-4 autoloading
- Fully tested with PHPUnit
- Ready for use in any PHP or Symfony project

## Installation

Install via Composer:

```bash
composer require ngocongcan/dbal-query-builder-wrapper
```

## Usage Example

```php
use QueryBuilderBundle\QueryBuilderTool;
use QueryBuilderBundle\Condition;

$queryBuilder = new QueryBuilderTool();
$queryBuilder->addToSelect('u.username, u.id');
$queryBuilder->setFrom('users', 'u');
$queryBuilder->addJoins(QueryBuilderTool::LEFT_JOIN, 'groups', 'gr', 'u.group_id = gr.id');
$queryBuilder->addGroupBy('id', 'gr');
$queryBuilder->addIntCondition('recycled', 'u', 0, Condition::EQUAL);
$queryBuilder->addCondition(new Condition(Condition::IS_NOT_NULL, 'id', 'gr'));

$sql = $queryBuilder->getSqlQueryWithParams();
echo $sql;
// Output: SELECT u.username, u.id  FROM users u  LEFT JOIN groups gr ON u.group_id = gr.id  WHERE  u.recycled = 0  AND  gr.id IS NOT NULL  GROUP BY gr.id
```

## Development

### Run Tests
```bash
make test
```

### Lint PHP
```bash
make lint
```

### Install/Update Dependencies
```bash
make install
make update
```

### Clean Vendor and Cache
```bash
make clean
```

### Tag a Release
```bash
make tag VERSION=1.0.0
```

## Continuous Integration

This project uses GitHub Actions for CI. All pushes and pull requests are automatically tested on PHP 8.3.

## Contributing

Pull requests and issues are welcome! Please ensure all tests pass before submitting a PR.

## License

MIT
