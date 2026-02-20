<?php

// Eleganced at 2026-02-20 18:00

namespace PDPhilip\DataSet;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * @method static where(string $key, mixed $operator = null, mixed $value = null)
 * @method static whereNot(string $key, mixed $value)
 * @method static whereStrict(string $key, mixed $value)
 * @method static whereIn(string $key, array $values)
 * @method static whereNotIn(string $key, array $values)
 * @method static whereBetween(string $key, array $range)
 * @method static whereNotBetween(string $key, array $range)
 * @method static whereNull(string $key)
 * @method static whereNotNull(string $key)
 * @method static search(string $term)
 * @method static orderBy(string $key, string $direction = 'asc')
 * @method static orderByDesc(string $key)
 * @method static groupBy(string $key)
 * @method static limit(int $count)
 * @method static offset(int $count)
 * @method Collection get()
 * @method Collection all()
 * @method DataModel|null first()
 * @method DataModel|null find(mixed $id)
 * @method DataModel|null fetch(string $key, mixed $value)
 * @method DataModel firstOrCreate(array $attributes, array $values = [])
 * @method int count()
 * @method bool exists()
 * @method Collection pluck(string $value, ?string $key = null)
 * @method array toArray()
 * @method LengthAwarePaginator paginate(int $perPage = 15)
 * @method int update(array $attributes)
 * @method int delete()
 * @method DataModel create(array $attributes = [])
 * @method DataModel add(array $attributes = [])
 * @method static insert(array $rows)
 */
class DataSet
{
    protected string $modelClass = DataModel::class;

    protected string $primaryKey = 'id';

    protected Collection $rows;

    protected Collection $query;

    protected ?string $groupBy = null;

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

        $this->query = $this->rows;
    }

    protected function data(): array
    {
        return [];
    }

    // ----------------------------------------------------------------------
    // Magic routing
    // ----------------------------------------------------------------------

    public function __call(string $name, array $arguments): mixed
    {
        if (method_exists($this, $name)) {
            return $this->{$name}(...$arguments);
        }

        throw new \BadMethodCallException("Method {$name} does not exist on ".static::class);
    }

    public static function __callStatic(string $name, array $arguments): mixed
    {
        return static::resolve()->{$name}(...$arguments);
    }

    // ----------------------------------------------------------------------
    // CRUD
    // ----------------------------------------------------------------------

    protected function create(array $attributes = []): DataModel
    {
        $model = new $this->modelClass($attributes);
        $model->dataSet = $this;

        return $model;
    }

    protected function add(array $attributes = []): DataModel
    {
        return $this->create($attributes)->save();
    }

    protected function insert(array $rows): static
    {
        $this->insertRows($rows);
        $this->query = $this->rows;

        return $this;
    }

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

    protected function update(array $attributes): int
    {
        $count = 0;
        foreach ($this->query as $key => $row) {
            $this->rows->put($key, array_merge($row, $attributes));
            $count++;
        }

        return $count;
    }

    protected function delete(): int
    {
        $keys = $this->query->keys();
        $keys->each(fn ($key) => $this->rows->forget($key));

        return $keys->count();
    }

    // ----------------------------------------------------------------------
    // Query — each returns a clone
    // ----------------------------------------------------------------------

    protected function where(string $key, mixed $operator = null, mixed $value = null): static
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $clone = clone $this;
        $clone->query = $clone->query->filter(
            fn (array $row) => $this->compare(data_get($row, $key), $operator, $value)
        );

        return $clone;
    }

    protected function whereNot(string $key, mixed $value): static
    {
        return $this->where($key, '!=', $value);
    }

    protected function whereStrict(string $key, mixed $value): static
    {
        return $this->where($key, '===', $value);
    }

    protected function whereIn(string $key, array $values): static
    {
        $clone = clone $this;
        $clone->query = $clone->query->filter(
            fn (array $row) => in_array(data_get($row, $key), $values)
        );

        return $clone;
    }

    protected function whereNotIn(string $key, array $values): static
    {
        $clone = clone $this;
        $clone->query = $clone->query->filter(
            fn (array $row) => ! in_array(data_get($row, $key), $values)
        );

        return $clone;
    }

    protected function whereBetween(string $key, array $range): static
    {
        $clone = clone $this;
        $clone->query = $clone->query->filter(function (array $row) use ($key, $range) {
            $val = data_get($row, $key);

            return $val >= $range[0] && $val <= $range[1];
        });

        return $clone;
    }

    protected function whereNotBetween(string $key, array $range): static
    {
        $clone = clone $this;
        $clone->query = $clone->query->filter(function (array $row) use ($key, $range) {
            $val = data_get($row, $key);

            return $val < $range[0] || $val > $range[1];
        });

        return $clone;
    }

    protected function whereNull(string $key): static
    {
        $clone = clone $this;
        $clone->query = $clone->query->filter(
            fn (array $row) => data_get($row, $key) === null
        );

        return $clone;
    }

    protected function whereNotNull(string $key): static
    {
        $clone = clone $this;
        $clone->query = $clone->query->filter(
            fn (array $row) => data_get($row, $key) !== null
        );

        return $clone;
    }

    protected function search(string $term): static
    {
        $term = strtolower($term);
        $clone = clone $this;
        $clone->query = $clone->query->filter(function (array $row) use ($term) {
            foreach ($row as $value) {
                if (is_string($value) && str_contains(strtolower($value), $term)) {
                    return true;
                }
            }

            return false;
        });

        return $clone;
    }

    protected function orderBy(string $key, string $direction = 'asc'): static
    {
        $clone = clone $this;
        $clone->query = strtolower($direction) === 'desc'
            ? $clone->query->sortByDesc($key)
            : $clone->query->sortBy($key);

        return $clone;
    }

    protected function orderByDesc(string $key): static
    {
        return $this->orderBy($key, 'desc');
    }

    protected function limit(int $count): static
    {
        $clone = clone $this;
        $clone->query = $clone->query->take($count);

        return $clone;
    }

    protected function offset(int $count): static
    {
        $clone = clone $this;
        $clone->query = $clone->query->skip($count);

        return $clone;
    }

    protected function groupBy(string $key): static
    {
        $clone = clone $this;
        $clone->groupBy = $key;

        return $clone;
    }

    // ----------------------------------------------------------------------
    // Terminal — execute and return
    // ----------------------------------------------------------------------

    protected function get(): Collection
    {
        $results = $this->query->values()->map(fn (array $row) => $this->toModel($row));

        if ($this->groupBy) {
            return $results->groupBy($this->groupBy);
        }

        return $results;
    }

    protected function all(): Collection
    {
        return $this->rows->values()->map(fn (array $row) => $this->toModel($row));
    }

    protected function first(): ?DataModel
    {
        $row = $this->query->first();

        return $row ? $this->toModel($row) : null;
    }

    protected function find(mixed $id): ?DataModel
    {
        $row = $this->rows->get($id);

        return $row ? $this->toModel($row) : null;
    }

    protected function fetch(string $key, mixed $value): ?DataModel
    {
        return $this->where($key, $value)->first();
    }

    protected function firstOrCreate(array $attributes, array $values = []): DataModel
    {
        $query = $this;
        foreach ($attributes as $key => $val) {
            $query = $query->where($key, $val);
        }

        return $query->first() ?? $this->add(array_merge($attributes, $values));
    }

    protected function count(): int
    {
        return $this->query->count();
    }

    protected function exists(): bool
    {
        return $this->query->isNotEmpty();
    }

    protected function pluck(string $value, ?string $key = null): Collection
    {
        return $this->query->pluck($value, $key);
    }

    protected function toArray(): array
    {
        return $this->query->values()->map(function (array $row) {
            if (isset($this->autoIds[$row[$this->primaryKey]])) {
                unset($row[$this->primaryKey]);
            }

            return $row;
        })->all();
    }

    protected function paginate(int $perPage = 15): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();
        $total = $this->query->count();
        $items = $this->query->values()->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $items->map(fn (array $row) => $this->toModel($row)),
            $total,
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );
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
    // Internal
    // ----------------------------------------------------------------------

    protected function toModel(array $row): DataModel
    {
        $model = new $this->modelClass($row);
        $model->dataSet = $this;

        if (isset($this->autoIds[$row[$this->primaryKey]])) {
            $model->autoIdKey = $this->primaryKey;
        }

        $model->markSaved();

        return $model;
    }

    protected function compare(mixed $actual, string $operator, mixed $expected): bool
    {
        if (is_array($actual)) {
            return match ($operator) {
                '=', '==' => in_array($expected, $actual),
                '!=', '<>' => ! in_array($expected, $actual),
                default => false,
            };
        }

        return match ($operator) {
            '=', '==' => $actual == $expected,
            '===' => $actual === $expected,
            '!=', '<>' => $actual != $expected,
            '!==' => $actual !== $expected,
            '<' => $actual < $expected,
            '>' => $actual > $expected,
            '<=' => $actual <= $expected,
            '>=' => $actual >= $expected,
            'like' => str_contains(strtolower((string) $actual), strtolower(str_replace('%', '', (string) $expected))),
            default => false,
        };
    }

    private function insertRows(array $rows): void
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

    public function __clone()
    {
        $this->query = $this->query->collect();
    }
}
