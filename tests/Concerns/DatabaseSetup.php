<?php
namespace Tests\Concerns;

use Illuminate\Contracts\Console\Kernel;

trait DatabaseSetup
{
    
    protected static $migrated = false;

    protected function setupDatabase()
    {
        if (!static::$migrated) {
            $this->artisan('migrate:refresh');
            $this->app[Kernel::class]->setArtisan(null);
            static::$migrated = true;
        }

        $this->seedDatabase();
        $this->beginTransaction();
    }

    public function resetDatabase()
    {
        $this->rollbackTransaction();
    }

    public function seedDatabase()
    {
        $this->artisan('db:seed', ['--class' => 'TestDatabaseSeeder']);
    }

    public function beginTransaction()
    {
        $database = $this->app->make('db');
        foreach ($this->connectionsToTransact() as $name) {
            $database->connection($name)->beginTransaction();
        }

        /*
        $this->beforeApplicationDestroyed(function () use ($database) {
            foreach ($this->connectionsToTransact() as $name) {
                $database->connection($name)->rollBack();
            }
        });
        */
    }

    public function rollbackTransaction(Type $var = null)
    {
        $database = $this->app->make('db');
        foreach ($this->connectionsToTransact() as $name) {
            $database->connection($name)->rollBack();
        }
    }

    protected function connectionsToTransact()
    {
        return property_exists($this, 'connectionsToTransact')
                            ? $this->connectionsToTransact : [null];
    }

}
