<div align="center">

# Laravel Data Set

[![Latest Version on Packagist](https://img.shields.io/packagist/v/pdphilip/laravel-data-set.svg?style=flat-square)](https://packagist.org/packages/pdphilip/laravel-data-set)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/pdphilip/laravel-data-set/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/pdphilip/laravel-data-set/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub phpstan Action Status](https://img.shields.io/github/actions/workflow/status/pdphilip/laravel-data-set/phpstan.yml?branch=main&label=phpstan&style=flat-square)](https://github.com/pdphilip/laravel-data-set/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](http://img.shields.io/packagist/dt/pdphilip/laravel-data-set.svg)](https://packagist.org/packages/pdphilip/laravel-data-set)

Eloquent-style querying for in-memory data sets. No database required.

</div>

Replace static arrays and config lookups with a queryable, model-like interface. Ideal for reference data (countries, currencies, timezones), test fixtures, and anywhere you need Eloquent ergonomics without a database.

```php
// Define your data set
class CountrySet extends DataSet
{
    protected string $modelClass = CountryDataModel::class;

    protected function data(): array
    {
        return [
            ['id' => 'US', 'name' => 'United States', 'currency' => 'USD', 'dial_code' => '+1'],
            ['id' => 'GB', 'name' => 'United Kingdom', 'currency' => 'GBP', 'dial_code' => '+44'],
            ['id' => 'DE', 'name' => 'Germany', 'currency' => 'EUR', 'dial_code' => '+49'],
            // ...
        ];
    }
}

// Query it like Eloquent
CountrySet::find('US')->name;                              // 'United States'
CountrySet::where('currency', 'EUR')->get();               // Collection of European countries
CountrySet::search('united')->pluck('name');               // ['United States', 'United Kingdom', ...]
CountrySet::where('dial_code', '+1')->first()->currency;   // 'USD'
```

## Requirements

- PHP 8.2+
- Laravel 10, 11, or 12

## Installation

```bash
composer require pdphilip/laravel-data-set
```

## Quick Start

### Inline usage

```php
use PDPhilip\DataSet\DataSet;

$set = new DataSet;

$set->add(['name' => 'Alpha', 'status' => 'active', 'score' => 85]);
$set->add(['name' => 'Beta', 'status' => 'inactive', 'score' => 42]);
$set->add(['name' => 'Charlie', 'status' => 'active', 'score' => 91]);

$set->where('status', 'active')->count();                  // 2
$set->where('score', '>', 80)->orderBy('name')->get();     // Alpha, Charlie
$set->search('beta')->first()->score;                      // 42
```

### Extended data set (recommended)

Create a dedicated class with seeded data:

```php
use PDPhilip\DataSet\DataSet;

class TimezoneSet extends DataSet
{
    protected function data(): array
    {
        return [
            ['id' => 'UTC', 'name' => 'Coordinated Universal Time', 'offset' => '+00:00'],
            ['id' => 'EST', 'name' => 'Eastern Standard Time', 'offset' => '-05:00'],
            ['id' => 'PST', 'name' => 'Pacific Standard Time', 'offset' => '-08:00'],
            // ...
        ];
    }
}

// Static facade - data loads once, cached per request
TimezoneSet::find('EST')->name;        // 'Eastern Standard Time'
TimezoneSet::count();                  // 3
```

### Custom model class

Type your data with a custom model for IDE autocompletion:

```php
use PDPhilip\DataSet\DataModel;

/**
 * @property string $id
 * @property string $name
 * @property string $currency
 * @property string $dial_code
 */
class CountryDataModel extends DataModel {}
```

```php
use PDPhilip\DataSet\DataSet;

class CountrySet extends DataSet
{
    protected string $modelClass = CountryDataModel::class;

    protected function data(): array
    {
        return [...];
    }
}
```

Now `CountrySet::find('US')` returns a `CountryDataModel` with typed properties.

### Relationship-like access

Use a DataSet as a pseudo-relationship on your Eloquent models:

```php
class User extends Model
{
    public function country(): ?CountryDataModel
    {
        return CountrySet::find($this->country_code);
    }
}

$user->country()->name;       // 'United States'
$user->country()->dial_code;  // '+1'
```

## API Reference

### CRUD

| Method | Returns | Description |
|--------|---------|-------------|
| `create(array $attributes)` | `DataModel` | Create an unsaved model instance |
| `add(array $attributes)` | `DataModel` | Create and save a model |
| `insert(array $rows)` | `static` | Bulk insert rows |

```php
// Create without saving
$model = $set->create(['name' => 'Draft']);
$model->status = 'pending';
$model->save();

// Create and save in one step
$model = $set->add(['name' => 'Ready', 'status' => 'active']);

// Bulk insert
$set->insert([
    ['name' => 'Alpha', 'status' => 'active'],
    ['name' => 'Beta', 'status' => 'inactive'],
]);
```

Records without an `id` get a UUID assigned automatically. To use a custom primary key:

```php
class MySet extends DataSet
{
    protected string $primaryKey = 'code';
}
```

### Query Methods

All query methods return a new instance, leaving the original untouched.

| Method | Description |
|--------|-------------|
| `where(string $key, mixed $operator, mixed $value)` | Filter by field. Supports `=`, `!=`, `<>`, `<`, `>`, `<=`, `>=`, `like` |
| `where(string $key, mixed $value)` | Shorthand for `where($key, '=', $value)` |
| `whereIn(string $key, array $values)` | Filter where field value is in array |
| `whereNotIn(string $key, array $values)` | Filter where field value is not in array |
| `whereBetween(string $key, array $range)` | Filter where field is between `[$min, $max]` |
| `whereNull(string $key)` | Filter where field is null or missing |
| `whereNotNull(string $key)` | Filter where field is not null |
| `search(string $term)` | Case-insensitive search across all string fields |
| `orderBy(string $key, string $direction)` | Sort results (`asc` or `desc`) |
| `orderByDesc(string $key)` | Sort descending |
| `limit(int $count)` | Limit result count |
| `offset(int $count)` | Skip first N results |

```php
// Chaining
$set->where('status', 'active')
    ->where('score', '>', 50)
    ->orderBy('name')
    ->limit(10)
    ->get();

// Dot notation for nested data
$set->where('address.city', 'Sydney')->get();

// Array field membership
$set->insert([
    ['id' => '1', 'tags' => ['php', 'laravel']],
    ['id' => '2', 'tags' => ['js', 'react']],
]);
$set->where('tags', 'php')->get(); // Row 1

// Clone isolation - queries never pollute the base set
$active = $set->where('status', 'active');
$activeHigh = $active->where('score', '>', 80');
$activeLow = $active->where('score', '<', 30);
// $active, $activeHigh, $activeLow are all independent
```

### Terminal Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `get()` | `Collection` | Execute query, return Collection of models |
| `all()` | `Collection` | All records (ignores filters) |
| `first()` | `DataModel\|null` | First matching record |
| `find(mixed $id)` | `DataModel\|null` | Find by primary key |
| `count()` | `int` | Count matching records |
| `exists()` | `bool` | Any matches? |
| `pluck(string $value, ?string $key)` | `Collection` | Pluck field values |
| `toArray()` | `array` | Raw array output |
| `paginate(int $perPage)` | `LengthAwarePaginator` | Paginated results |

### Static Facade

Extended DataSet classes support static method calls. The instance is cached per class for the duration of the request.

```php
CountrySet::where('currency', 'EUR')->get();
CountrySet::find('US');
CountrySet::count();
CountrySet::search('island')->pluck('name');
```

Use `flush()` to clear the cached instance (useful in tests or Laravel Octane):

```php
CountrySet::flush();
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [David Philip](https://github.com/pdphilip)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
