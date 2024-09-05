<?php

namespace AuroraWebSoftware\FlexyField;

use AuroraWebSoftware\FlexyField\Commands\FlexyFieldCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FlexyFieldServiceProvider extends PackageServiceProvider
{
    public function boot() : FlexyFieldServiceProvider
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        return parent::boot();
    }

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
            // ->hasViews()
            //->hasMigration('create_flexyfield_table')
            // ->hasCommand(FlexyFieldCommand::class)
        ;
    }
}
