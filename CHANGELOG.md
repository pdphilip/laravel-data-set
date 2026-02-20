# Changelog

All notable changes to `Laravel Data Set` will be documented in this file.

## v1.0.0 - 2026-02-20

### Complete rebuild

Ground-up rewrite. 5 files / 728 lines reduced to 2 files / ~320 lines. Same Eloquent-style API, cleaner internals.

#### Highlights

- **Clone-based query isolation** - every `where()`, `orderBy()`, etc. returns a fresh clone. Queries never pollute the base set or each other.
- **Static singleton caching** - `CountrySet::find('US')` loads data once per request. `flush()` for Octane/testing.
- **Full CRUD** - `create()`, `add()`, `insert()`, `$model->save()`, `$model->delete()`, bulk `update()` and `delete()`.
- **Auto-ID management** - records without an ID get a UUID internally. Auto-generated IDs are hidden from `toArray()` output for clean data round-trips.
- **DataModel** - extends Laravel's Fluent with `save()`, `delete()`, `isSaved()`. Supports `@property` docblocks for IDE autocompletion.

#### Query methods

`where()`, `whereNot()`, `whereStrict()`, `whereIn()`, `whereNotIn()`, `whereBetween()`, `whereNotBetween()`, `whereNull()`, `whereNotNull()`, `search()`, `orderBy()`, `orderByDesc()`, `groupBy()`, `limit()`, `offset()`

Operators: `=`, `!=`, `<>`, `<`, `>`, `<=`, `>=`, `like`, `===`, `!==`

Supports dot notation (`where('address.city', 'Sydney')`) and array field membership (`where('tags', 'php')`).

#### Terminal methods

`get()`, `all()`, `first()`, `find()`, `fetch()`, `firstOrCreate()`, `count()`, `exists()`, `pluck()`, `toArray()`, `paginate()`, `update()`, `delete()`

#### Removed

- `DataQuery.php` - merged into DataSet
- `DataSetServiceProvider.php` - unnecessary
- `Support/Helpers.php` - replaced by `data_get()` and inlined logic

**Full Changelog**: https://github.com/pdphilip/laravel-data-set/compare/v0.0.1...v1.0.0

## v0.0.1 - 2025-10-02

### Beta release

**Full Changelog**: https://github.com/pdphilip/laravel-data-set/commits/v0.0.1
