<?php

// Eleganced at 2026-03-02 15:30

namespace PDPhilip\DataSet;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class DataSetQuery
{
    protected Collection $query;

    protected ?string $groupBy = null;

    public function __construct(protected DataSet $dataSet)
    {
        $this->query = $dataSet->rows();
    }

    // ----------------------------------------------------------------------
    // CRUD
    // ----------------------------------------------------------------------

    public function create(array $attributes = []): DataModel
    {
        $modelClass = $this->dataSet->modelClass();
        $model = new $modelClass($attributes);
        $model->dataSet = $this->dataSet;

        return $model;
    }

    public function add(array $attributes = []): DataModel
    {
        return $this->create($attributes)->save();
    }

    public function insert(array $rows): static
    {
        $this->dataSet->insertRows($rows);
        $this->query = $this->dataSet->rows();

        return $this;
    }

    public function update(array $attributes): int
    {
        $count = 0;
        foreach ($this->query as $key => $row) {
            $this->dataSet->rows()->put($key, array_merge($row, $attributes));
            $count++;
        }

        return $count;
    }

    public function delete(): int
    {
        $keys = $this->query->keys();
        $keys->each(fn ($key) => $this->dataSet->rows()->forget($key));

        return $keys->count();
    }

    // ----------------------------------------------------------------------
    // Query — each returns a clone
    // ----------------------------------------------------------------------

    public function where(string $key, mixed $operator = null, mixed $value = null): static
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

    public function whereNot(string $key, mixed $value): static
    {
        return $this->where($key, '!=', $value);
    }

    public function whereStrict(string $key, mixed $value): static
    {
        return $this->where($key, '===', $value);
    }

    public function whereIn(string $key, array $values): static
    {
        $clone = clone $this;
        $clone->query = $clone->query->filter(
            fn (array $row) => in_array(data_get($row, $key), $values)
        );

        return $clone;
    }

    public function whereNotIn(string $key, array $values): static
    {
        $clone = clone $this;
        $clone->query = $clone->query->filter(
            fn (array $row) => ! in_array(data_get($row, $key), $values)
        );

        return $clone;
    }

    public function whereBetween(string $key, array $range): static
    {
        $clone = clone $this;
        $clone->query = $clone->query->filter(function (array $row) use ($key, $range) {
            $val = data_get($row, $key);

            return $val >= $range[0] && $val <= $range[1];
        });

        return $clone;
    }

    public function whereNotBetween(string $key, array $range): static
    {
        $clone = clone $this;
        $clone->query = $clone->query->filter(function (array $row) use ($key, $range) {
            $val = data_get($row, $key);

            return $val < $range[0] || $val > $range[1];
        });

        return $clone;
    }

    public function whereNull(string $key): static
    {
        $clone = clone $this;
        $clone->query = $clone->query->filter(
            fn (array $row) => data_get($row, $key) === null
        );

        return $clone;
    }

    public function whereNotNull(string $key): static
    {
        $clone = clone $this;
        $clone->query = $clone->query->filter(
            fn (array $row) => data_get($row, $key) !== null
        );

        return $clone;
    }

    public function search(string $term): static
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

    public function orderBy(string $key, string $direction = 'asc'): static
    {
        $clone = clone $this;
        $clone->query = strtolower($direction) === 'desc'
            ? $clone->query->sortByDesc($key)
            : $clone->query->sortBy($key);

        return $clone;
    }

    public function orderByDesc(string $key): static
    {
        return $this->orderBy($key, 'desc');
    }

    public function limit(int $count): static
    {
        $clone = clone $this;
        $clone->query = $clone->query->take($count);

        return $clone;
    }

    public function offset(int $count): static
    {
        $clone = clone $this;
        $clone->query = $clone->query->skip($count);

        return $clone;
    }

    public function groupBy(string $key): static
    {
        $clone = clone $this;
        $clone->groupBy = $key;

        return $clone;
    }

    // ----------------------------------------------------------------------
    // Terminal — execute and return
    // ----------------------------------------------------------------------

    public function get(): Collection
    {
        $results = $this->query->values()->map(fn (array $row) => $this->toModel($row));

        if ($this->groupBy) {
            return $results->groupBy($this->groupBy);
        }

        return $results;
    }

    public function all(): Collection
    {
        return $this->dataSet->rows()->values()->map(fn (array $row) => $this->toModel($row));
    }

    public function first(): ?DataModel
    {
        $row = $this->query->first();

        return $row ? $this->toModel($row) : null;
    }

    public function find(mixed $id): ?DataModel
    {
        $row = $this->dataSet->rows()->get($id);

        return $row ? $this->toModel($row) : null;
    }

    public function fetch(string $key, mixed $value): ?DataModel
    {
        return $this->where($key, $value)->first();
    }

    public function firstOrCreate(array $attributes, array $values = []): DataModel
    {
        $query = $this;
        foreach ($attributes as $key => $val) {
            $query = $query->where($key, $val);
        }

        return $query->first() ?? $this->add(array_merge($attributes, $values));
    }

    public function count(): int
    {
        return $this->query->count();
    }

    public function exists(): bool
    {
        return $this->query->isNotEmpty();
    }

    public function pluck(string $value, ?string $key = null): Collection
    {
        return $this->query->pluck($value, $key);
    }

    public function toArray(): array
    {
        $primaryKey = $this->dataSet->primaryKey();

        return $this->query->values()->map(function (array $row) use ($primaryKey) {
            if ($this->dataSet->isAutoId($row[$primaryKey])) {
                unset($row[$primaryKey]);
            }

            return $row;
        })->all();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
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
    // Internal
    // ----------------------------------------------------------------------

    protected function toModel(array $row): DataModel
    {
        $modelClass = $this->dataSet->modelClass();
        $model = new $modelClass($row);
        $model->dataSet = $this->dataSet;

        $primaryKey = $this->dataSet->primaryKey();
        if ($this->dataSet->isAutoId($row[$primaryKey])) {
            $model->autoIdKey = $primaryKey;
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

    public function __clone()
    {
        $this->query = $this->query->collect();
    }
}
