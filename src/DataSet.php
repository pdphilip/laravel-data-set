<?php

// Eleganced at 2026-03-02 15:30

namespace PDPhilip\DataSet;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * @mixin DataSetQuery
 *
 * @method static DataSetQuery where(string $key, mixed $operator = null, mixed $value = null)
 * @method static DataSetQuery whereNot(string $key, mixed $value)
 * @method static DataSetQuery whereStrict(string $key, mixed $value)
 * @method static DataSetQuery whereIn(string $key, array $values)
 * @method static DataSetQuery whereNotIn(string $key, array $values)
 * @method static DataSetQuery whereBetween(string $key, array $range)
 * @method static DataSetQuery whereNotBetween(string $key, array $range)
 * @method static DataSetQuery whereNull(string $key)
 * @method static DataSetQuery whereNotNull(string $key)
 * @method static DataSetQuery search(string $term)
 * @method static DataSetQuery orderBy(string $key, string $direction = 'asc')
 * @method static DataSetQuery orderByDesc(string $key)
 * @method static DataSetQuery groupBy(string $key)
 * @method static DataSetQuery limit(int $count)
 * @method static DataSetQuery offset(int $count)
 * @method static DataSetQuery insert(array $rows)
 * @method static Collection get()
 * @method static Collection all()
 * @method static DataModel|null first()
 * @method static DataModel|null find(mixed $id)
 * @method static DataModel|null fetch(string $key, mixed $value)
 * @method static DataModel firstOrCreate(array $attributes, array $values = [])
 * @method static DataModel create(array $attributes = [])
 * @method static DataModel add(array $attributes = [])
 * @method static int count()
 * @method static bool exists()
 * @method static int update(array $attributes)
 * @method static int delete()
 * @method static Collection pluck(string $value, ?string $key = null)
 * @method static array toArray()
 * @method static LengthAwarePaginator paginate(int $perPage = 15)
 */
class DataSet
{
    protected string $modelClass = DataModel::class;

    protected string $primaryKey = 'id';

    protected Collection $rows;

    /** @var array<string, true> */
    protected array $autoIds = [];

    /** @var array<string, static> */
    protected static array $resolved = [];

    // ----------------------------------------------------------------------
    // Lifecycle
    // ----------------------------------------------------------------------

    public function __construct(array $seed = [])
    {
        $this->rows = new Collection;

        $this->insertRows($this->data());
        $this->insertRows($seed);
    }

    protected function data(): array
    {
        return [];
    }

    // ----------------------------------------------------------------------
    // Query routing
    // ----------------------------------------------------------------------

    public function newQuery(): DataSetQuery
    {
        return new DataSetQuery($this);
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->newQuery()->{$name}(...$arguments);
    }

    public static function __callStatic(string $name, array $arguments): mixed
    {
        return static::resolve()->{$name}(...$arguments);
    }

    // ----------------------------------------------------------------------
    // Model persistence
    // ----------------------------------------------------------------------

    public function save(DataModel $model): void
    {
        $attrs = $model->rawAttributes();

        if (empty($attrs[$this->primaryKey])) {
            $id = Str::uuid()->toString();
            $model->{$this->primaryKey} = $id;
            $attrs[$this->primaryKey] = $id;
            $this->autoIds[$id] = true;
        }

        $this->rows->put($attrs[$this->primaryKey], $attrs);
        $model->markSaved();
    }

    public function deleteModel(DataModel $model): void
    {
        $this->rows->forget($model->{$this->primaryKey});
    }

    // ----------------------------------------------------------------------
    // Static facade
    // ----------------------------------------------------------------------

    public static function resolve(): static
    {
        /** @phpstan-ignore-next-line */
        return static::$resolved[static::class] ??= new static;
    }

    public static function flush(): void
    {
        unset(static::$resolved[static::class]);
    }

    // ----------------------------------------------------------------------
    // Internal — accessed by DataSetQuery
    // ----------------------------------------------------------------------

    /** @internal */
    public function rows(): Collection
    {
        return $this->rows;
    }

    /** @internal */
    public function primaryKey(): string
    {
        return $this->primaryKey;
    }

    /** @internal */
    public function modelClass(): string
    {
        return $this->modelClass;
    }

    /** @internal */
    public function isAutoId(mixed $id): bool
    {
        return isset($this->autoIds[$id]);
    }

    /** @internal */
    public function insertRows(array $rows): void
    {
        if (! $rows) {
            return;
        }

        // Single associative array → wrap
        if (! isset($rows[0]) && ! array_is_list($rows)) {
            $rows = [$rows];
        }

        foreach ($rows as $row) {
            if (! is_array($row)) {
                throw new \RuntimeException('Each row must be an associative array, '.gettype($row).' given.');
            }

            $hasId = isset($row[$this->primaryKey]);
            $id = $row[$this->primaryKey] ?? Str::uuid()->toString();
            $row[$this->primaryKey] = $id;

            if (! $hasId) {
                $this->autoIds[$id] = true;
            }

            $this->rows->put($id, $row);
        }
    }
}
