<?php

use PDPhilip\DataSet\DataModel;
use PDPhilip\DataSet\DataSet;

// ----------------------------------------------------------------------
// CRUD
// ----------------------------------------------------------------------

it('creates an unsaved model', function () {
    $set = new DataSet;
    $model = $set->create(['name' => 'Alpha']);

    expect($model)->toBeInstanceOf(DataModel::class)
        ->and($model->name)->toBe('Alpha')
        ->and($model->isSaved())->toBeFalse();
});

it('adds a saved model', function () {
    $set = new DataSet;
    $model = $set->add(['name' => 'Alpha']);

    expect($model->isSaved())->toBeTrue()
        ->and($model->id)->not->toBeNull()
        ->and($set->count())->toBe(1);
});

it('saves and modifies a model', function () {
    $set = new DataSet;
    $model = $set->add(['name' => 'Alpha']);
    $model->name = 'Beta';
    $model->save();

    expect($set->first()->name)->toBe('Beta')
        ->and($set->count())->toBe(1);
});

it('inserts bulk rows', function () {
    $set = new DataSet;
    $set->insert([
        ['name' => 'Alpha'],
        ['name' => 'Beta'],
        ['name' => 'Charlie'],
    ]);

    expect($set->count())->toBe(3);
});

it('inserts a single associative array', function () {
    $set = new DataSet;
    $set->insert(['name' => 'Alpha', 'status' => 'active']);

    expect($set->count())->toBe(1)
        ->and($set->first()->name)->toBe('Alpha');
});

it('generates UUIDs for records without IDs', function () {
    $set = new DataSet;
    $set->add(['name' => 'Alpha']);
    $set->add(['name' => 'Beta']);

    $ids = $set->pluck('id');
    expect($ids)->toHaveCount(2)
        ->and($ids[0])->not->toBe($ids[1]);
});

it('preserves explicit IDs', function () {
    $set = new DataSet;
    $set->add(['id' => 'custom-1', 'name' => 'Alpha']);

    expect($set->find('custom-1')->name)->toBe('Alpha');
});

it('deletes a model', function () {
    $set = new DataSet;
    $set->insert([
        ['id' => '1', 'name' => 'Alpha'],
        ['id' => '2', 'name' => 'Beta'],
    ]);

    $model = $set->find('1');
    $model->delete();

    expect($set->count())->toBe(1)
        ->and($set->find('1'))->toBeNull()
        ->and($model->isSaved())->toBeFalse();
});

it('bulk deletes matching rows', function () {
    $set = new DataSet;
    $set->insert([
        ['id' => '1', 'status' => 'active'],
        ['id' => '2', 'status' => 'inactive'],
        ['id' => '3', 'status' => 'active'],
        ['id' => '4', 'status' => 'inactive'],
    ]);

    $deleted = $set->where('status', 'inactive')->delete();

    expect($deleted)->toBe(2)
        ->and($set->count())->toBe(2)
        ->and($set->where('status', 'inactive')->exists())->toBeFalse();
});

it('bulk updates matching rows', function () {
    $set = new DataSet;
    $set->insert([
        ['id' => '1', 'name' => 'Alpha', 'status' => 'draft'],
        ['id' => '2', 'name' => 'Beta', 'status' => 'draft'],
        ['id' => '3', 'name' => 'Charlie', 'status' => 'published'],
    ]);

    $updated = $set->where('status', 'draft')->update(['status' => 'published']);

    expect($updated)->toBe(2)
        ->and($set->where('status', 'published')->count())->toBe(3)
        ->and($set->find('1')['status'])->toBe('published');
});

// ----------------------------------------------------------------------
// Where
// ----------------------------------------------------------------------

it('filters with where equals', function () {
    $set = new DataSet;
    $set->insert([
        ['id' => '1', 'name' => 'Alpha', 'status' => 'active'],
        ['id' => '2', 'name' => 'Beta', 'status' => 'inactive'],
        ['id' => '3', 'name' => 'Charlie', 'status' => 'active'],
    ]);

    expect($set->where('status', 'active')->count())->toBe(2);
});

it('filters with where operator', function () {
    $set = new DataSet;
    $set->insert([
        ['id' => '1', 'score' => 10],
        ['id' => '2', 'score' => 50],
        ['id' => '3', 'score' => 90],
    ]);

    expect($set->where('score', '>', 40)->count())->toBe(2)
        ->and($set->where('score', '>=', 50)->count())->toBe(2)
        ->and($set->where('score', '<', 50)->count())->toBe(1)
        ->and($set->where('score', '!=', 50)->count())->toBe(2);
});

it('filters with where like', function () {
    $set = new DataSet;
    $set->insert([
        ['id' => '1', 'name' => 'Alpha Dog'],
        ['id' => '2', 'name' => 'Beta Cat'],
        ['id' => '3', 'name' => 'Alpha Cat'],
    ]);

    expect($set->where('name', 'like', 'alpha')->count())->toBe(2)
        ->and($set->where('name', 'like', 'cat')->count())->toBe(2);
});

it('filters with whereIn', function () {
    $set = new DataSet;
    $set->insert([
        ['id' => '1', 'status' => 'active'],
        ['id' => '2', 'status' => 'inactive'],
        ['id' => '3', 'status' => 'pending'],
    ]);

    expect($set->whereIn('status', ['active', 'pending'])->count())->toBe(2);
});

it('filters with whereNotIn', function () {
    $set = new DataSet;
    $set->insert([
        ['id' => '1', 'status' => 'active'],
        ['id' => '2', 'status' => 'inactive'],
        ['id' => '3', 'status' => 'pending'],
    ]);

    expect($set->whereNotIn('status', ['inactive'])->count())->toBe(2);
});

it('filters with whereBetween', function () {
    $set = new DataSet;
    $set->insert([
        ['id' => '1', 'score' => 10],
        ['id' => '2', 'score' => 50],
        ['id' => '3', 'score' => 90],
    ]);

    expect($set->whereBetween('score', [20, 80])->count())->toBe(1);
});

it('filters with whereNot', function () {
    $set = new DataSet;
    $set->insert([
        ['id' => '1', 'status' => 'active'],
        ['id' => '2', 'status' => 'inactive'],
        ['id' => '3', 'status' => 'active'],
    ]);

    expect($set->whereNot('status', 'inactive')->count())->toBe(2);
});

it('filters with whereStrict', function () {
    $set = new DataSet;
    $set->insert([
        ['id' => '1', 'score' => 1],
        ['id' => '2', 'score' => '1'],
        ['id' => '3', 'score' => 2],
    ]);

    expect($set->whereStrict('score', 1)->count())->toBe(1)
        ->and($set->where('score', 1)->count())->toBe(2);
});

it('filters with whereNotBetween', function () {
    $set = new DataSet;
    $set->insert([
        ['id' => '1', 'score' => 10],
        ['id' => '2', 'score' => 50],
        ['id' => '3', 'score' => 90],
    ]);

    expect($set->whereNotBetween('score', [20, 80])->count())->toBe(2);
});

it('filters with whereNull and whereNotNull', function () {
    $set = new DataSet;
    $set->insert([
        ['id' => '1', 'name' => 'Alpha', 'bio' => null],
        ['id' => '2', 'name' => 'Beta', 'bio' => 'Hello'],
        ['id' => '3', 'name' => 'Charlie'],
    ]);

    expect($set->whereNull('bio')->count())->toBe(2)
        ->and($set->whereNotNull('bio')->count())->toBe(1);
});

it('filters with dot notation', function () {
    $set = new DataSet;
    $set->insert([
        ['id' => '1', 'address' => ['city' => 'Sydney']],
        ['id' => '2', 'address' => ['city' => 'London']],
        ['id' => '3', 'address' => ['city' => 'Sydney']],
    ]);

    expect($set->where('address.city', 'Sydney')->count())->toBe(2);
});

it('filters where value is array checks membership', function () {
    $set = new DataSet;
    $set->insert([
        ['id' => '1', 'tags' => ['php', 'laravel']],
        ['id' => '2', 'tags' => ['js', 'react']],
        ['id' => '3', 'tags' => ['php', 'vue']],
    ]);

    expect($set->where('tags', 'php')->count())->toBe(2)
        ->and($set->where('tags', '!=', 'php')->count())->toBe(1);
});

// ----------------------------------------------------------------------
// Search
// ----------------------------------------------------------------------

it('searches across all string fields', function () {
    $set = new DataSet;
    $set->insert([
        ['id' => '1', 'name' => 'Alpha', 'city' => 'Sydney'],
        ['id' => '2', 'name' => 'Beta', 'city' => 'London'],
        ['id' => '3', 'name' => 'Charlie', 'city' => 'Sydney Alpha'],
    ]);

    expect($set->search('alpha')->count())->toBe(2);
});

it('searches case-insensitively', function () {
    $set = new DataSet;
    $set->insert([
        ['id' => '1', 'name' => 'ALPHA'],
        ['id' => '2', 'name' => 'alpha'],
        ['id' => '3', 'name' => 'Beta'],
    ]);

    expect($set->search('alpha')->count())->toBe(2);
});

// ----------------------------------------------------------------------
// Ordering
// ----------------------------------------------------------------------

it('orders by key ascending', function () {
    $set = new DataSet;
    $set->insert([
        ['id' => '1', 'name' => 'Charlie'],
        ['id' => '2', 'name' => 'Alpha'],
        ['id' => '3', 'name' => 'Beta'],
    ]);

    $names = $set->orderBy('name')->pluck('name')->all();
    expect($names)->toBe(['Alpha', 'Beta', 'Charlie']);
});

it('orders by key descending', function () {
    $set = new DataSet;
    $set->insert([
        ['id' => '1', 'name' => 'Alpha'],
        ['id' => '2', 'name' => 'Charlie'],
        ['id' => '3', 'name' => 'Beta'],
    ]);

    $names = $set->orderByDesc('name')->pluck('name')->all();
    expect($names)->toBe(['Charlie', 'Beta', 'Alpha']);
});

// ----------------------------------------------------------------------
// Limit & Offset
// ----------------------------------------------------------------------

it('limits results', function () {
    $set = new DataSet;
    $set->insert([
        ['id' => '1', 'name' => 'Alpha'],
        ['id' => '2', 'name' => 'Beta'],
        ['id' => '3', 'name' => 'Charlie'],
    ]);

    expect($set->limit(2)->count())->toBe(2);
});

it('offsets results', function () {
    $set = new DataSet;
    $set->insert([
        ['id' => '1', 'name' => 'Alpha'],
        ['id' => '2', 'name' => 'Beta'],
        ['id' => '3', 'name' => 'Charlie'],
    ]);

    expect($set->offset(1)->first()->name)->toBe('Beta');
});

// ----------------------------------------------------------------------
// Chaining
// ----------------------------------------------------------------------

it('chains where + orderBy + limit', function () {
    $set = new DataSet;
    $set->insert([
        ['id' => '1', 'name' => 'Charlie', 'status' => 'active'],
        ['id' => '2', 'name' => 'Alpha', 'status' => 'active'],
        ['id' => '3', 'name' => 'Beta', 'status' => 'inactive'],
        ['id' => '4', 'name' => 'Delta', 'status' => 'active'],
    ]);

    $result = $set->where('status', 'active')->orderBy('name')->limit(2)->get();

    expect($result)->toHaveCount(2)
        ->and($result[0]->name)->toBe('Alpha')
        ->and($result[1]->name)->toBe('Charlie');
});

// ----------------------------------------------------------------------
// Clone Isolation
// ----------------------------------------------------------------------

it('does not pollute the base set when querying', function () {
    $set = new DataSet;
    $set->insert([
        ['id' => '1', 'status' => 'active'],
        ['id' => '2', 'status' => 'inactive'],
        ['id' => '3', 'status' => 'active'],
    ]);

    $active = $set->where('status', 'active');

    expect($active->count())->toBe(2)
        ->and($set->count())->toBe(3);
});

it('allows branching queries from the same base', function () {
    $set = new DataSet;
    $set->insert([
        ['id' => '1', 'region' => 'EU', 'currency' => 'EUR'],
        ['id' => '2', 'region' => 'EU', 'currency' => 'GBP'],
        ['id' => '3', 'region' => 'US', 'currency' => 'USD'],
    ]);

    $eu = $set->where('region', 'EU');

    expect($eu->where('currency', 'EUR')->count())->toBe(1)
        ->and($eu->where('currency', 'GBP')->count())->toBe(1)
        ->and($eu->count())->toBe(2);
});

// ----------------------------------------------------------------------
// Terminal Methods
// ----------------------------------------------------------------------

it('returns all records via all() ignoring filters', function () {
    $set = new DataSet;
    $set->insert([
        ['id' => '1', 'status' => 'active'],
        ['id' => '2', 'status' => 'inactive'],
    ]);

    $filtered = $set->where('status', 'active');

    expect($filtered->count())->toBe(1)
        ->and($filtered->all())->toHaveCount(2);
});

it('finds by primary key', function () {
    $set = new DataSet;
    $set->insert([
        ['id' => 'us', 'name' => 'United States'],
        ['id' => 'uk', 'name' => 'United Kingdom'],
    ]);

    expect($set->find('us')->name)->toBe('United States')
        ->and($set->find('xx'))->toBeNull();
});

it('fetches by key and value', function () {
    $set = new DataSet;
    $set->insert([
        ['id' => '1', 'name' => 'Alpha', 'status' => 'active'],
        ['id' => '2', 'name' => 'Beta', 'status' => 'inactive'],
    ]);

    expect($set->fetch('name', 'Beta')->status)->toBe('inactive')
        ->and($set->fetch('name', 'Unknown'))->toBeNull();
});

it('returns first or creates a new record', function () {
    $set = new DataSet;
    $set->insert([
        ['id' => '1', 'name' => 'Alpha', 'status' => 'active'],
    ]);

    $existing = $set->firstOrCreate(['name' => 'Alpha']);
    expect($existing->name)->toBe('Alpha')
        ->and($set->count())->toBe(1);

    $created = $set->firstOrCreate(['name' => 'Beta'], ['status' => 'pending']);
    expect($created->name)->toBe('Beta')
        ->and($created->status)->toBe('pending')
        ->and($set->count())->toBe(2);
});

it('groups results by key', function () {
    $set = new DataSet;
    $set->insert([
        ['id' => '1', 'name' => 'Alpha', 'browser' => 'Chrome'],
        ['id' => '2', 'name' => 'Beta', 'browser' => 'Firefox'],
        ['id' => '3', 'name' => 'Charlie', 'browser' => 'Chrome'],
        ['id' => '4', 'name' => 'Delta', 'browser' => 'Safari'],
    ]);

    $grouped = $set->groupBy('browser')->get();

    expect($grouped)->toHaveCount(3)
        ->and($grouped['Chrome'])->toHaveCount(2)
        ->and($grouped['Firefox'])->toHaveCount(1)
        ->and($grouped['Safari'])->toHaveCount(1);
});

it('chains where + groupBy', function () {
    $set = new DataSet;
    $set->insert([
        ['id' => '1', 'status' => 'active', 'browser' => 'Chrome'],
        ['id' => '2', 'status' => 'active', 'browser' => 'Firefox'],
        ['id' => '3', 'status' => 'inactive', 'browser' => 'Chrome'],
        ['id' => '4', 'status' => 'active', 'browser' => 'Chrome'],
    ]);

    $grouped = $set->where('status', 'active')->groupBy('browser')->get();

    expect($grouped)->toHaveCount(2)
        ->and($grouped['Chrome'])->toHaveCount(2)
        ->and($grouped['Firefox'])->toHaveCount(1);
});

it('checks existence', function () {
    $set = new DataSet;
    $set->insert([
        ['id' => '1', 'status' => 'active'],
        ['id' => '2', 'status' => 'inactive'],
    ]);

    expect($set->where('status', 'active')->exists())->toBeTrue()
        ->and($set->where('status', 'deleted')->exists())->toBeFalse();
});

it('plucks values', function () {
    $set = new DataSet;
    $set->insert([
        ['id' => 'us', 'name' => 'United States'],
        ['id' => 'uk', 'name' => 'United Kingdom'],
    ]);

    $names = $set->pluck('name', 'id');
    expect($names->all())->toBe(['us' => 'United States', 'uk' => 'United Kingdom']);
});

it('converts to array', function () {
    $set = new DataSet;
    $set->insert([
        ['id' => '1', 'name' => 'Alpha'],
    ]);

    $arr = $set->toArray();
    expect($arr)->toBeArray()
        ->and($arr[0]['name'])->toBe('Alpha')
        ->and($arr[0]['id'])->toBe('1');
});

it('hides auto-generated IDs in toArray', function () {
    $set = new DataSet;
    $set->insert([
        ['name' => 'Alpha'],
        ['id' => 'custom', 'name' => 'Beta'],
    ]);

    $arr = $set->toArray();
    expect($arr[0])->not->toHaveKey('id')
        ->and($arr[0]['name'])->toBe('Alpha')
        ->and($arr[1]['id'])->toBe('custom');
});

it('hides auto-generated IDs on model toArray', function () {
    $set = new DataSet;
    $set->add(['name' => 'Alpha']);
    $set->add(['id' => 'custom', 'name' => 'Beta']);

    $models = $set->get();
    $alpha = $models->firstWhere('name', 'Alpha');
    $beta = $models->firstWhere('name', 'Beta');

    expect($alpha->id)->not->toBeNull()
        ->and($alpha->toArray())->not->toHaveKey('id')
        ->and($beta->toArray())->toHaveKey('id');
});

it('preserves auto-ID internally for find and delete', function () {
    $set = new DataSet;
    $model = $set->add(['name' => 'Alpha']);

    $id = $model->id;
    expect($id)->not->toBeNull()
        ->and($set->find($id)->name)->toBe('Alpha');

    $model->delete();
    expect($set->count())->toBe(0);
});

it('paginates results', function () {
    $set = new DataSet;
    for ($i = 1; $i <= 25; $i++) {
        $set->add(['id' => (string) $i, 'name' => "Item {$i}"]);
    }

    $page = $set->paginate(10);
    expect($page->count())->toBe(10)
        ->and($page->hasMorePages())->toBeTrue();
});
