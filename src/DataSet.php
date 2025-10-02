<?php

namespace PDPhilip\DataSet;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Fluent;
use Illuminate\Support\Str;
use PDPhilip\DataSet\Support\Helpers;

/**
 * @template TModel of DataModel
 *
 * @template-covariant  TModelCov of DataModel
 *
 * @template TId of string|int
 * @template TRows of Collection<TId, array<TModelCov>>
 * @template TCollection of Collection<int, array<TModelCov>>
 *
 * @mixin DataQuery
 */
class DataSet
{
    /** @var class-string<TModel> */
    protected $modelClass = DataModel::class;

    /** @var TRows */
    protected $rows;

    /** @var TId */
    public $primaryKey = 'id';

    protected array $queryMethods = [
        'where',
        'whereStrict',
        'whereIn',
        'whereBetween',
        'whereNull',
        'whereNot',
        'whereNotIn',
        'whereNotNull',
        'whereNotBetween',
        'search',
        'orderBy',
        'orderByDesc',
        'skip',
        'limit',
        'pluck',
        'all',
        'find',
        'first',
        'get',
        'toArray',
        'count',
        'exists',
        'fetch',
        'paginate',
    ];

    /**
     * @param  array<int|string,array<string,mixed>>  $seed
     */
    public function __construct(array|Collection $seed = [])
    {
        $this->rows = new Collection;
        $this->insert(Helpers::ensureIndexedArray($this->seeder()));
        $this->insert(Helpers::ensureIndexedArray($seed));
    }

    /**
     * @param  class-string<covariant DataModel>  $modelClass
     * @return static
     */
    public function using($modelClass)
    {
        $this->modelClass = $modelClass;

        return $this;
    }

    /**
     * @param  array|Fluent  $attributes
     * @return TModelCov
     */
    public function create($attributes = [])
    {
        $mcl = $this->modelClass;

        return new $mcl($this, $attributes);
    }

    /**
     * @param  array|Fluent  $attributes
     * @return TModel
     */
    public function add($attributes = [])
    {
        return $this->create($attributes)->save();
    }

    /**
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $defaults
     * @return TModel
     */
    public function firstOrCreate($key, $value, $defaults = [])
    {
        if ($first = $this->fetch($key, $value)) {
            return $first;
        }
        $item[$key] = $value;
        if ($defaults) {
            foreach ($defaults as $k => $v) {
                $item[$k] = $v;
            }
        }

        return $this->add($item);
    }

    // ----------------------------------------------------------------------
    // Queries
    // ----------------------------------------------------------------------

    /**
     * @return DataQuery
     */
    public function query()
    {
        return new DataQuery($this);
    }

    /**
     * @param  array  $rows
     * @return void
     */
    public function insert($rows)
    {
        if ($rows) {
            foreach ($rows as $row) {
                $this->add($row);
            }
        }

    }

    /** @return TCollection */
    public function getRows()
    {
        return $this->rows->values()->map(fn ($r) => $r);
    }

    public function __call(string $name, array $arguments)
    {
        if (! in_array($name, $this->queryMethods)) {
            throw new Exception('Unknown method: '.$name);
        }

        return $this->query()->{$name}(...$arguments);
    }

    /** @return array */
    protected function seeder()
    {
        return [];
    }

    // ----------------------------------------------------------------------
    // Internals
    // ----------------------------------------------------------------------

    /** @internal */
    public function upsert(DataModel $model): void
    {
        $attrs = $model->toArray();
        $idKey = $this->primaryKey;

        if (! Arr::has($attrs, $idKey) || empty($attrs[$idKey])) {
            $attrs[$idKey] = Str::uuid()->toString();
            $model->set($idKey, $attrs[$idKey]);
        }

        $this->rows->put($attrs[$idKey], $attrs);
        $model->markSaved();
    }

    /** @internal */
    public function mapToModels(Collection $rows): Collection
    {
        return $rows->values()->map(fn (array $r) => new DataModel($this, $r, true));
    }

    /** @internal */
    protected function seederToArray($seeds): array
    {
        if (! $seeds) {
            return [];
        }

        if ($seeds instanceof Collection) {
            return $seeds->toArray();
        }

        if (empty($seeds[0])) {
            $seeds = [$seeds];
        }

        return $seeds;
    }

    /**
     * Facade generator
     *
     * @return mixed
     */
    public static function __callStatic(string $name, array $arguments)
    {
        // @phpstan-ignore-next-line
        $instance = new static;

        return $instance->{$name}(...$arguments);
    }
}
