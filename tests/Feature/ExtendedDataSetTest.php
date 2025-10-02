<?php

use PDPhilip\DataSet\DataModel;
use PDPhilip\DataSet\DataSet;
use PDPhilip\DataSet\Tests\Sets\CountryDataModel;
use PDPhilip\DataSet\Tests\Sets\CountrySet;

it('should use an extended data set', function () {

    $countries = new CountrySet;

    expect($countries)->toBeInstanceOf(DataSet::class)
        ->and($countries)->toBeInstanceOf(CountrySet::class);

});

it('should use an extended data set with extended data model', function () {

    $countries = new CountrySet;
    $model = $countries->create();
    $model->name = 'test';
    $model->save();

    expect($model)->toBeInstanceOf(CountryDataModel::class)
        ->and($model)->toBeInstanceOf(DataModel::class);

});

it('should seed data from class function', function () {

    $countries = new CountrySet;

    expect($countries->count())->toBe(250);

});

it('should seed data from class function and class arguments', function () {

    $countries = new CountrySet([
        [
            'id' => 'MI',
            'id3' => 'MOI',
            'name' => 'Monkey Island',
            'name_official' => 'United States of Monkey Island',
            'name_native' => 'Monkey Island',
            'dial_code' => '+555',
            'flag' => 'https://flagcdn.com/256x192/us.png',
            'currency_code' => 'MID',
            'currency_symbol' => 'M$',
            'region' => 'Americas',
            'sub_region' => 'North America',
        ],
        [
            'id' => 'GZ',
            'id3' => 'GIZ',
            'name' => 'Ginger Island',
            'name_official' => 'United States of Ginger Island',
            'name_native' => 'Ginger Island',
            'dial_code' => '+555',
            'flag' => 'https://flagcdn.com/256x192/us.png',
            'currency_code' => 'USD',
            'currency_symbol' => '$',
            'region' => 'Americas',
            'sub_region' => 'North America',
        ],
    ]);

    expect($countries->count())->toBe(252);

});

it('should seed data from class function and class arguments as associative array entry', function () {

    $countries = new CountrySet([
        'id' => 'MI',
        'id3' => 'MOI',
        'name' => 'Monkey Island',
        'name_official' => 'United States of Monkey Island',
        'name_native' => 'Monkey Island',
        'dial_code' => '+555',
        'flag' => 'https://flagcdn.com/256x192/us.png',
        'currency_code' => 'MID',
        'currency_symbol' => 'M$',
        'region' => 'Americas',
        'sub_region' => 'North America',
    ]);

    expect($countries->count())->toBe(251);

});

it('should have facade access', function () {
    expect(CountrySet::count())->toBe(250);
});

it('should get first', function () {
    $first = CountrySet::first();

    expect($first->name)->toBe('Afghanistan');
});

it('should find on where query', function () {
    $usd = CountrySet::where('currency_code', 'EUR')->get();
    expect(count($usd))->toBe(35);
});
