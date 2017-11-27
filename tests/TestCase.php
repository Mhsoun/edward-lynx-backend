<?php
namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use Concerns\CreatesApplication, Concerns\DatabaseSetup;

    protected $baseUrl = 'http://localhost:8000';

    public function setUp()
    {
        parent::setUp();
        $this->setupDatabase();
    }

    public function tearDown()
    {
        $this->resetDatabase();
    }

    /**
     * Authenticates the user for API access.
     *
     * @param  App\Models\User $user
     * @return $this
     */
    protected function apiAuthenticate(App\Models\User $user = null)
    {
        if (!$user) {
            $user = App\Models\User::find(1);
        }

        $this->actingAs($user, 'api');

        return $this;
    }

    protected function api($method, $uri, array $data = [], array $headers = [])
    {
        return $this->authenticateApi()
                    ->json($method, $uri, $data, $headers);
    }

}
