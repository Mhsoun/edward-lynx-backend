<?php

use Illuminate\Contracts\Console\Kernel;

class TestCase extends Illuminate\Foundation\Testing\TestCase
{

    protected $baseUrl = 'http://localhost';

    public function createApplication()
    {
        $app = require __DIR__ . '/../bootstrap/app.php';
        $app->loadEnvironmentFrom('.env.testing.php');
        $app->make(Kernel::class)->bootstrap();

        $this->setupDatabase();

        return $app;
    }

    protected function setupDatabase()
    {
        Artisan::call('migrate');
        Artisan::call('db:seed');
    }

    protected function authenticateApi()
    {
        $user = App\Models\User::find(1);
        $this->actingAs($user, 'api');
        
        return $this;
    }

    protected function api($method, $uri, array $data = [], array $headers = [])
    {
        return $this->authenticateApi()
                    ->json($method, $uri, $data, $headers);
    }

}