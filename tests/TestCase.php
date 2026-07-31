<?php

namespace Mortezamasumi\FbAuth\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\Facades\Filament;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\LivewireServiceProvider;
use Mortezamasumi\FbAuth\FbAuthPlugin;
use Mortezamasumi\FbAuth\FbAuthServiceProvider;
use Mortezamasumi\FbEssentials\FbEssentialsServiceProvider;
use Mortezamasumi\FbSms\FbSmsServiceProvider;
use Orchestra\Testbench\TestCase as TestbenchTestCase;
use Orchestra\Workbench\WorkbenchServiceProvider;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;

class TestCase extends TestbenchTestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app)
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('mobile')->unique()->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('expiration_date')->nullable();
            $table->boolean('active')->default(false);
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Filament::registerPanel(
            Panel::make()
                ->id('admin')
                ->path('/')
                ->login()
                ->default()
                ->registration()
                ->emailVerification()
                ->passwordReset()
                ->pages([
                    Dashboard::class,
                ])
                ->plugins([
                    FbAuthPlugin::make(),
                ])
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            BladeHeroiconsServiceProvider::class,
            BladeIconsServiceProvider::class,
            FilamentServiceProvider::class,
            ActionsServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            NotificationsServiceProvider::class,
            SchemasServiceProvider::class,
            SupportServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            LivewireServiceProvider::class,
            BladeCaptureDirectiveServiceProvider::class,
            WorkbenchServiceProvider::class,
            FbEssentialsServiceProvider::class,
            FbSmsServiceProvider::class,
            FbAuthServiceProvider::class,
        ];
    }
}
