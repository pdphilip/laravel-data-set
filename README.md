<div align="center">

# Laravel Data Set

[![Latest Version on Packagist](https://img.shields.io/packagist/v/pdphilip/laravel-data-set.svg?style=flat-square)](https://packagist.org/packages/pdphilip/laravel-data-set) [![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/pdphilip/laravel-data-set/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/pdphilip/data-set/actions?query=workflow%3Arun-tests+branch%3Amain) [![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/pdphilip/data-set/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/pdphilip/laravel-data-set/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain) [![Total Downloads](https://img.shields.io/packagist/dt/pdphilip/laravel-data-set.svg?style=flat-square)](https://packagist.org/packages/pdphilip/laravel-data-set)

Eloquent style management of data sets in Laravel

</div>

## Installation

Add the package via composer:

```bash
composer require pdphilip/data-set
```

Then install with:

```bash
php artisan data-set:install
```

## Usage

```php
use PDPhilip\DataSet\DataSet;

$dataSet = new DataSet;

//Add data models
$model = $dataSet->create();
$model->name = 'Alpha';
$model->status = 'active';
$model->hits = 2;
$model->save();

$model2 = $dataSet->create();
$model2->name = 'Bravo';
$model2->status = 'active';
$model2->hits = 15;
$model2->save();

$model3 = $dataSet->create();
$model3->name = 'Charlie';
$model3->status = 'inactive';
$model3->hits = 6;
$model3->save();

$model4 = $dataSet->create();
$model4->name = 'Delta';
$model4->status = 'active';
$model4->hits = 11;
$model4->save();

$dataSet->add([
$model4->name = 'Echo';
$model4->status = 'inactive';
$model4->hits = 5;
]);

//Find
$model = $dataSet->where('name','Charlie')->first()
$models = $dataSet->where('status','active')->get()
$models = $dataSet->where('hits','>',5)->get()
$models = $dataSet->search('Delta')->get()
///.....

//Count
$count = $dataSet->where('hits','>',5)->count() //3

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
