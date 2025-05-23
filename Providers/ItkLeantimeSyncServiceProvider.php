<?php

namespace Modules\ItkLeantimeSync\Providers;

use App\Conversation;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;
use Modules\ItkLeantimeSync\Service\Helper;
use TorMorten\Eventy\Facades\Events as Eventy;

define('ITK_LEANTIME_SYNC_MODULE', 'itkleantimesync');
class ItkLeantimeSyncServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->hooks();
    }

    /**
     * Module hooks.
     */
    public function hooks()
    {
        Eventy::addAction(
            'conversation.status_changed',
            function (Conversation $conversation, $user) {
                app(Helper::class)
                ->syncStatus($conversation, $user);
            },
            20,
            3
        );
        Eventy::addAction(
            'conversation.user_changed',
            function (Conversation $conversation, $user, $prev_user_id) {
                app(Helper::class)
                ->syncAssignee($conversation, $user, $prev_user_id);
            },
            20,
            3
        );
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerTranslations();
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('itkleantimesync.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php',
            'itkleantimesync'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $this->loadJsonTranslationsFrom(__DIR__ .'/../Resources/lang');
    }

    /**
     * Register an additional directory of factories.
     *
     * @source https://github.com/sebastiaanluca/laravel-resource-flow/blob/develop/src/Modules/ModuleServiceProvider.php#L66
     */
    public function registerFactories()
    {
        if (! app()->environment('production')) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
