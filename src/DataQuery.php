<?php

namespace PDPhilip\DataSet;

use Exception;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PDPhilip\DataSet\Support\Helpers;

/**
 * @template TModel of DataModel
 * @template TCollection of Collection<int, array<DataModel>>
 */
final class DataQuery
{
    protected Collection $rows;

    private DataSet $set;

    /** @param DataSet $set */
    public function __construct($set)
    {
        $this->set = $set;
        $this->rows = $set->getRows();
    }

    // ----------------------------------------------------------------------
    // Builder methods
    // ----------------------------------------------------------------------

    /**
     * @param  string  $key
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return static
     */
    public function where($key, $operator = null, $value = null)
    {
        return $this->operatorForWhere(...func_get_args());

    }

    /**
     * @param  string  $key
     * @param  mixed  $value
     * @return static
     */
    public function whereStrict($key, $value)
    {
        $this->rows = $this->rows->whereStrict($key, $value);

        return $this;
    }

    /**
     * @param  string  $key
     * @param  array  $values
     * @param  bool  $strict
     * @return static
     */
    public function whereIn($key, $values, $strict = false)
    {

        $this->rows = $this->rows->whereIn($key, $values, $strict);

        return $this;
    }

    /**
     * @param  string  $key
     * @param  array  $values
     * @return static
     */
    public function whereBetween($key, $values)
    {
        $this->rows = $this->rows->whereBetween($key, $values);

        return $this;
    }

    /**
     * @param  string  $key
     * @return static
     */
    public function whereNull($key)
    {
        $this->rows = $this->rows->whereNull($key);

        return $this;
    }

    /**
     * @param  string  $key
     * @param  mixed|null  $value
     * @return static
     */
    public function whereNot($key, $value = null)
    {
        return $this->where($key, '!=', $value);
    }

    /**
     * @param  string  $key
     * @param  array  $values
     * @param  bool  $strict
     * @return static
     */
    public function whereNotIn($key, $values, $strict = false)
    {
        $this->rows = $this->rows->whereNotIn($key, $values, $strict);

        return $this;
    }

    /**
     * @param  string  $key
     * @return static
     */
    public function whereNotNull($key)
    {
        $this->rows = $this->rows->whereNotNull($key);

        return $this;
    }

    /**
     * @param  string  $key
     * @param  array  $values
     * @return static
     */
    public function whereNotBetween($key, $values)
    {
        $this->rows = $this->rows->whereNotBetween($key, $values);

        return $this;
    }

    /**
     * @param  string  $value
     * @param  bool  $caseSensitive
     * @return static
     */
    public function search($value, $caseSensitive = false)
    {
        $this->rows = $this->rows->filter(function ($item) use ($value, $caseSensitive) {
            $dot = collect($item)->dot()->toArray();
            $values = array_values($dot);
            if ($values) {
                foreach ($values as $val) {
                    if ($caseSensitive) {
                        if ($val && Str::contains((string) ($val), (string) ($value))) {
                            return $item;
                        }
                    } else {
                        if ($val && Str::contains(strtolower($val), strtolower($value))) {
                            return $item;
                        }
                    }

                }
            }
        });

        return $this;
    }

    /**
     * @param  string  $key
     * @param  string  $dir
     * @return static
     */
    public function orderBy($key, $dir = 'ASC')
    {
        $this->rows = $dir === 'DESC' ? $this->rows->sortByDesc($key) : $this->rows->sortBy($key);

        return $this;
    }

    /**
     * @param  string  $key
     * @return static
     */
    public function orderByDesc($key)
    {
        return $this->orderBy($key, 'DESC');
    }

    /**
     * @param  int  $offset
     * @return static
     */
    public function skip($offset = 1)
    {
        $this->rows = $this->rows->skip($offset);

        return $this;
    }

    /**
     * @param  int  $limit
     * @return static
     */
    public function limit($limit)
    {
        $this->rows = $this->rows->slice(0, $limit);

        return $this;
    }

    /**
     * @param  string  $key
     * @return static
     */
    public function groupBy($key)
    {
        $this->rows = $this->rows->groupBy($key);

        return $this;
    }

    /**
     * @param  string  $value
     * @param  string|null  $key
     * @return Collection
     */
    public function pluck($value, $key = null)
    {
        return $this->rows->pluck($value, $key);
    }

    // ----------------------------------------------------------------------
    // Executors
    // ----------------------------------------------------------------------

    /**
     * @return TCollection
     */
    public function all()
    {
        // reset any queries
        $this->rows = $this->set->getRows();

        return $this->get();
    }

    /**
     * @param  mixed  $id
     * @return TModel|null
     */
    public function find($id)
    {
        return $this->where($this->set->primaryKey, $id)->first();
    }

    /**
     * @return TModel|null
     */
    public function first()
    {
        $row = $this->rows->first();

        return $row ? new DataModel($this->set, $row, true) : null;
    }

    /**
     * @return TCollection
     */
    public function get()
    {
        return $this->set->mapToModels($this->rows);
    }

    /**
     * @return array<int, array<string,mixed>>
     */
    public function toArray(): array
    {
        return array_values($this->rows->toArray());
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->rows);
    }

    /**
     * @param  string  $key
     * @param  mixed  $value
     * @return bool
     */
    public function exists($key, $value)
    {
        return ! empty($this->where($key, $value)->first());
    }

    /**
     * @param  string  $key
     * @param  mixed  $value
     * @return TModel|null
     */
    public function fetch($key, $value)
    {
        return $this->where($key, $value)->first();
    }

    /**
     * @param  int  $perPage
     * @return Paginator
     */
    public function paginate($perPage)
    {
        $page = Paginator::resolveCurrentPage();
        $items = $this->skip(($page - 1) * $perPage)->toArray();

        return new Paginator($items, $perPage, null, [
            'path' => Paginator::resolveCurrentPath(),
        ]);
    }

    // ----------------------------------------------------------------------
    // Internals
    // ----------------------------------------------------------------------

    /** @internal */
    protected function operatorForWhere($key, $operator = null, $value = null)
    {
        if (func_num_args() === 1) {
            $value = true;
            $operator = '=';
        }

        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->rows = $this->rows->filter(function ($item) use ($key, $operator, $value) {
            $value = Helpers::enumValue($value);
            $dots = collect($item)->dot()->toArray();
            $liftedDots = Helpers::liftDots($dots);
            $retrieved = $liftedDots[$key] ?? false;
            $retrieved = Helpers::enumValue($retrieved);
            if (is_array($retrieved)) {

                return match ($operator) {
                    '=', '==' => in_array($value, $retrieved),
                    '!=', '<>' => ! in_array($value, $retrieved),
                    default => throw new Exception('Unsupported on array values: '.$operator),
                };
            }

            switch ($operator) {
                default:
                case '=':
                case '==': return $retrieved == $value;
                case '!=':
                case '<>': return $retrieved != $value;
                case '<':return $retrieved < $value;
                case '>':return $retrieved > $value;
                case '<=':return $retrieved <= $value;
                case '>=':return $retrieved >= $value;
                case '===':return $retrieved === $value;
                case '!==':return $retrieved !== $value;
                case '<=>':return $retrieved <=> $value;
                case 'like':return Str::contains(Str::lower($retrieved), Str::lower($value));
            }

        });

        return $this;

    }
}
