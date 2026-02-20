<?php

use PDPhilip\DataSet\DataModel;
use PDPhilip\DataSet\DataSet;
use PDPhilip\DataSet\Tests\Sets\CountryDataModel;
use PDPhilip\DataSet\Tests\Sets\CountrySet;

beforeEach(function () {
    CountrySet::flush();
});

it('is a DataSet subclass', function () {
    $countries = new CountrySet;

    expect($countries)->toBeInstanceOf(DataSet::class)
        ->and($countries)->toBeInstanceOf(CountrySet::class);
});

it('uses a custom model class', function () {
    $countries = new CountrySet;
    $model = $countries->first();

    expect($model)->toBeInstanceOf(CountryDataModel::class)
        ->and($model)->toBeInstanceOf(DataModel::class);
});

it('seeds data from the data() method', function () {
    $countries = new CountrySet;

    expect($countries->count())->toBe(250);
});

it('merges seed data with constructor arguments', function () {
    $countries = new CountrySet([
        ['id' => 'MI', 'name' => 'Monkey Island'],
        ['id' => 'GZ', 'name' => 'Ginger Island'],
    ]);

    expect($countries->count())->toBe(252);
});

it('merges a single associative array as one record', function () {
    $countries = new CountrySet(['id' => 'MI', 'name' => 'Monkey Island']);

    expect($countries->count())->toBe(251);
});

it('supports static facade access', function () {
    expect(CountrySet::count())->toBe(250);
});

it('supports static facade queries', function () {
    $eur = CountrySet::where('currency_code', 'EUR')->get();

    expect($eur)->toHaveCount(35);
});

it('finds by country code', function () {
    $us = CountrySet::find('US');

    expect($us)->toBeInstanceOf(CountryDataModel::class)
        ->and($us->name)->toBe('United States');
});

it('caches the static instance', function () {
    $a = CountrySet::resolve();
    $b = CountrySet::resolve();

    expect($a)->toBe($b);
});

it('flushes the static cache', function () {
    $a = CountrySet::resolve();
    CountrySet::flush();
    $b = CountrySet::resolve();

    expect($a)->not->toBe($b);
});

it('searches countries by name', function () {
    $results = CountrySet::search('united')->get();
    $names = $results->pluck('name')->all();

    expect($results->count())->toBeGreaterThan(0)
        ->and($names)->toContain('United States')
        ->and($names)->toContain('United Kingdom');
});

it('orders countries by name', function () {
    $first = CountrySet::orderBy('name')->first();

    expect($first->name)->toBe('Afghanistan');
});
