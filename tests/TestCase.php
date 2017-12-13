<?php
namespace Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use Concerns\CreatesApplication;

    protected $baseUrl = 'http://localhost:8000';

    public function setUp()
    {
        parent::setUp();

        $this->runDatabaseMigrations();

        Model::unguard();
    }

    public function tearDown()
    {
        parent::tearDown();
        Model::reguard();
    }

    /**
     * Setups the database before and after each test.
     *
     * @return void
     */
    public function runDatabaseMigrations()
    {
        $this->artisan('migrate');
        
        $this->beforeApplicationDestroyed(function() {
            $this->artisan('droptables');
        });
    }
}
