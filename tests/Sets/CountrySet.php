<?php

namespace PDPhilip\DataSet\Tests\Sets;

use PDPhilip\DataSet\DataSet;

class CountrySet extends DataSet
{
    use SeederCountries;

    /** @var class-string<CountryDataModel> */
    protected $modelClass = CountryDataModel::class;

    public function seeder()
    {
        return $this->countries;
    }
}
