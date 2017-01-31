<?php

namespace App\Providers;

use App\Http\JsonHalResponse;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Routing\ResponseFactory;

class JsonHalServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(ResponseFactory $response)
    {
        $response->macro('jsonHal', function($input, $status = 200, $headers = [], $options = 0) {
            return new JsonHalResponse($input, $status, $headers, $options);
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
