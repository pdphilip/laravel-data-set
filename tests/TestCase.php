<?php

namespace PDPhilip\DataSet\Tests;

use OmniTerm\OmniTermServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use PDPhilip\DataSet\DataSetServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            OmniTermServiceProvider::class,
            DataSetServiceProvider::class,
        ];
    }
}
