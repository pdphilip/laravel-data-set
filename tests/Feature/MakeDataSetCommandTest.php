<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    File::deleteDirectory(app_path('DataSets'));
});

afterEach(function () {
    File::deleteDirectory(app_path('DataSets'));
});

it('creates a DataSet and DataModel pair', function () {
    $this->artisan('data-set:make', ['name' => 'Country'])
        ->expectsConfirmation('Generate a seeder trait?', 'no')
        ->assertSuccessful();

    expect(file_exists(app_path('DataSets/CountrySet.php')))->toBeTrue()
        ->and(file_exists(app_path('DataSets/Models/CountryDataModel.php')))->toBeTrue();

    $setContent = file_get_contents(app_path('DataSets/CountrySet.php'));
    $modelContent = file_get_contents(app_path('DataSets/Models/CountryDataModel.php'));

    expect($setContent)
        ->toContain('namespace App\DataSets;')
        ->toContain('use App\DataSets\Models\CountryDataModel;')
        ->toContain('class CountrySet extends DataSet')
        ->toContain('CountryDataModel::class')
        ->toContain('return [];')
        ->and($modelContent)
        ->toContain('namespace App\DataSets\Models;')
        ->toContain('class CountryDataModel extends DataModel');
});

it('singularizes plural input', function () {
    $this->artisan('data-set:make', ['name' => 'Countries'])
        ->expectsConfirmation('Generate a seeder trait?', 'no')
        ->assertSuccessful();

    expect(file_exists(app_path('DataSets/CountrySet.php')))->toBeTrue()
        ->and(file_exists(app_path('DataSets/Models/CountryDataModel.php')))->toBeTrue();
});

it('converts the name to studly case', function () {
    $this->artisan('data-set:make', ['name' => 'page-view'])
        ->expectsConfirmation('Generate a seeder trait?', 'no')
        ->assertSuccessful();

    expect(file_exists(app_path('DataSets/PageViewSet.php')))->toBeTrue()
        ->and(file_exists(app_path('DataSets/Models/PageViewDataModel.php')))->toBeTrue();
});

it('creates a seeder when confirmed', function () {
    $this->artisan('data-set:make', ['name' => 'Country'])
        ->expectsConfirmation('Generate a seeder trait?', 'yes')
        ->assertSuccessful();

    expect(file_exists(app_path('DataSets/CountrySet.php')))->toBeTrue()
        ->and(file_exists(app_path('DataSets/Models/CountryDataModel.php')))->toBeTrue()
        ->and(file_exists(app_path('DataSets/Seeders/SeederCountries.php')))->toBeTrue();

    $setContent = file_get_contents(app_path('DataSets/CountrySet.php'));
    $seederContent = file_get_contents(app_path('DataSets/Seeders/SeederCountries.php'));

    expect($setContent)
        ->toContain('use SeederCountries;')
        ->toContain('use App\DataSets\Seeders\SeederCountries;')
        ->toContain('return $this->countries;')
        ->and($seederContent)
        ->toContain('namespace App\DataSets\Seeders;')
        ->toContain('trait SeederCountries')
        ->toContain('protected array $countries = [');
});

it('does not create seeder when declined', function () {
    $this->artisan('data-set:make', ['name' => 'Country'])
        ->expectsConfirmation('Generate a seeder trait?', 'no')
        ->assertSuccessful();

    expect(file_exists(app_path('DataSets/Seeders')))->toBeFalse();
});

it('fails if the set already exists', function () {
    $this->artisan('data-set:make', ['name' => 'Country'])
        ->expectsConfirmation('Generate a seeder trait?', 'no')
        ->assertSuccessful();

    $this->artisan('data-set:make', ['name' => 'Country'])
        ->assertFailed();
});

it('overwrites with --force', function () {
    $this->artisan('data-set:make', ['name' => 'Country'])
        ->expectsConfirmation('Generate a seeder trait?', 'no')
        ->assertSuccessful();

    $this->artisan('data-set:make', ['name' => 'Country', '--force' => true])
        ->expectsConfirmation('Generate a seeder trait?', 'no')
        ->assertSuccessful();
});
