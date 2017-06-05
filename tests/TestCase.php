<?php

use Illuminate\Contracts\Console\Kernel;

class TestCase extends Illuminate\Foundation\Testing\TestCase
{

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

}