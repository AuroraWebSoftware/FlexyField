<?php

namespace AuroraWebSoftware\FlexyField;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use AuroraWebSoftware\FlexyField\Commands\FlexyFieldCommand;

class FlexyFieldServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('flexyfield')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_flexyfield_table')
            ->hasCommand(FlexyFieldCommand::class);
    }
}
