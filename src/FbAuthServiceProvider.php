<?php

namespace Mortezamasumi\FbAuth;

use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Livewire\Features\SupportTesting\Testable;
use Mortezamasumi\FbAuth\FbAuth;
use Mortezamasumi\FbAuth\Testing\TestsFbAuth;
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
            ->hasConfigFile()
            ->hasViews();
    }

    public function packageRegistered()
    {
        $this->app->singleton('FbAuth', fn ($app) => new FbAuth());
    }

    public function packageBooted(): void
    {
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        Testable::mixin(new TestsFbAuth);
    }

    protected function getAssetPackageName(): ?string
    {
        return 'mortezamasumi/fb-auth';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        return [
            Css::make('fb-auth-styles', __DIR__.'/../resources/dist/css/index.css'),
            Js::make('fb-auth-scripts', __DIR__.'/../resources/dist/js/index.js'),
        ];
    }
}
