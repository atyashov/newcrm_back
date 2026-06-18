<?php

namespace App\Providers;

use App\Services\Localization\LocalizationService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class ModularProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
        $modules = config('modular.modules');
        $path = config('modular.path');

        if ($modules){
            Route::group([
                'prefix' => '' //LocalizationService::locale() // Мультиязычность в URL (Проблемы с нахождением сервиса Locale)
            ], function() use($modules, $path){
                foreach ($modules as $mod => $submodules) {
                    foreach ($submodules as $key => $sub) {

                        $relativePath = "/$mod/$sub";
                        Route::middleware('web')
                            ->group(function() use($mod, $sub, $relativePath, $path){
                                $this->getWebRoutes($mod, $sub, $relativePath, $path);
                            });

                        Route::middleware('api')
                            ->prefix('api')
                            ->group(function() use($mod, $sub, $relativePath, $path){
                                $this->getApiRoutes($mod, $sub, $relativePath, $path);
                            });
                    }
                }
            });
        }
        $this->app['view']->addNamespace('Pub', base_path().'/resources/views/Pub');
        $this->app['view']->addNamespace('Admin', base_path().'/resources/views/Admin');
    }

    private function getWebRoutes(int|string $mod, mixed $sub, string $relativePath, mixed $path)
    {
        $routesPath = $path.$relativePath.'/Routes/web.php';
        if(file_exists($routesPath)){
            if ($mod != config('modular.groupWithoutPrefix')){
                Route::group(
                    [
                        'prefix' => strtolower($mod),
                        'middleware' => $this->getMiddleware($mod)
                    ],
                    function() use($mod, $sub, $routesPath){
                        Route::namespace("App\Modules\\$mod\\$sub\Controllers")->group($routesPath);
                    }
                );
            } else{
                Route::namespace("App\Modules\\$mod\\$sub\Controllers")->
                middleware($this->getMiddleware($mod))->
                group($routesPath);
            }
        }
    }

    private function getApiRoutes(int|string $mod, mixed $sub, string $relativePath, mixed $path)
    {
        $routesPath = $path.$relativePath.'/Routes/api.php';
        if(file_exists($routesPath)){
            Route::group(
                [
                    'prefix' => strtolower($mod),
                    'middleware' => $this->getMiddleware($mod, 'api')
                ],
                function() use($mod, $sub, $routesPath){
                    Route::namespace("App\Modules\\$mod\\$sub\Controllers")->group($routesPath);
                }
            );
        }
    }

    private function getMiddleware(int|string $mod, string $key = 'web')
    {
        $middleware = [];

        $config = config('modular.groupMiddleware');

        if (isset($config[$mod])){
            if (array_key_exists($key, $config[$mod])){
                $middleware = array_merge($middleware, $config[$mod][$key]);
            }
        }

        return  $middleware;
    }
}
