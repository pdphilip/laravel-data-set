<?php

namespace PDPhilip\DataSet;

use PDPhilip\DataSet\Commands\MakeDataSetCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class DataSetServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('data-set')
            ->hasCommand(MakeDataSetCommand::class);
    }
}
