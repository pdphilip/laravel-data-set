<?php

// Eleganced at 2026-02-20 18:00

namespace PDPhilip\DataSet;

use Illuminate\Support\Fluent;

class DataModel extends Fluent
{
    /** @internal */
    public ?DataSet $dataSet = null;

    /** @internal */
    public ?string $autoIdKey = null;

    protected bool $saved = false;

    public function save(): static
    {
        $this->dataSet?->save($this);

        return $this;
    }

    public function delete(): void
    {
        $this->dataSet?->deleteModel($this);
        $this->saved = false;
    }

    public function toArray(): array
    {
        $attrs = parent::toArray();

        if ($this->autoIdKey) {
            unset($attrs[$this->autoIdKey]);
        }

        return $attrs;
    }

    /** @internal - Returns all attributes including auto-generated IDs */
    public function rawAttributes(): array
    {
        return parent::toArray();
    }

    public function isSaved(): bool
    {
        return $this->saved;
    }

    /** @internal */
    public function markSaved(): void
    {
        $this->saved = true;
    }
}
