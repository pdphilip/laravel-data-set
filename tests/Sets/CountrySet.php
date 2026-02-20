<?php

namespace PDPhilip\DataSet\Tests\Sets;

use PDPhilip\DataSet\DataSet;

class CountrySet extends DataSet
{
    use SeederCountries;

    protected string $modelClass = CountryDataModel::class;

    protected function data(): array
    {
        return $this->countries;
    }
}
