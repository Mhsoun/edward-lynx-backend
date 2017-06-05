<?php

use App\User;

/**
* The base class for test cases
*/
class TestCase extends Illuminate\Foundation\Testing\TestCase {
	public function setUp()
	{
		parent::setUp();
		$this->seed('TestDatabaseSeeder');
	}

	/**
	 * Creates the application.
	 *
	 * @return \Illuminate\Foundation\Application
	 */
	public function createApplication()
	{
		$app = require __DIR__.'/../bootstrap/app.php';
		$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
		return $app;
	}

	/**
    * Helper function for signing in in test cases
    */
    protected function signIn()
    {
        $user = User::where('name', '=', 'Pizza AB')->get()->first();
        $this->be($user);
        return $user;
    }

	/**
    * Adds the CSRF token to the given request parameters
    */
    protected function withCSRF($params) {
        $params['_token'] = csrf_token();
        return $params;
    }
}
