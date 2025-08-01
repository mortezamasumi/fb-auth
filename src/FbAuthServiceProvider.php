<?php

namespace Mortezamasumi\FbAuth;

use Mortezamasumi\FbAuth\FbAuth;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FbAuthServiceProvider extends PackageServiceProvider
{
    public static string $name = 'fb-auth';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasTranslations()
            ->hasConfigFile();
    }

    public function packageRegistered()
    {
        $this->app->singleton('FbAuth', fn ($app) => new FbAuth());
    }
}
