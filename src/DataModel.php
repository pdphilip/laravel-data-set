<?php

namespace PDPhilip\DataSet;

use Illuminate\Support\Fluent;
use JsonSerializable;

class DataModel extends Fluent implements JsonSerializable
{
    private bool $saved = false;

    public function __construct(
        private readonly DataSet $set,
        array|Fluent $attributes = [],
        bool $saved = false
    ) {
        parent::__construct($attributes);
        $this->saved = $saved;
    }

    /**
     * @return static
     */
    public function save()
    {
        $this->set->upsert($this);

        return $this;
    }

    /**
     * @return bool
     */
    public function isSaved()
    {
        return $this->saved;
    }

    /** @internal */
    public function markSaved(): void
    {
        $this->saved = true;
    }
}
