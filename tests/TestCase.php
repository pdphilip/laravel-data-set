<?php

namespace PDPhilip\DataSet\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use PDPhilip\DataSet\DataSetServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            DataSetServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        //        config()->set('database.default', 'testing');

        /*
        $migration = include __DIR__.'/../database/migrations/create_omnilens_table.php.stub';
        $migration->up();
        */
    }
}
