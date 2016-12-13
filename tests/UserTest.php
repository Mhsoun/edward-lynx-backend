<?php

use App\User;

/**
* Contains tests for users.
*/
class UserTest extends TestCase {
	/**
	* Tests signing in with an invalid email
	*/
	public function testSigninInvalidEmail()
	{
		$response = $this->call('POST', '/auth/login', ['email' => 'wololo@lol.se', 'password' => 'password123']);
		$this->assertEquals(500, $response->getStatusCode());
	}

	/**
	* Tests signing in using an invalid password
	*/
	public function testSigninInvalidPassword()
	{
		$response = $this->call('POST', '/auth/login', ['email' => 'admin@pizza.se', 'password' => 'password1234']);
		$this->assertEquals(500, $response->getStatusCode());
	}

	/**
	* Tests signing in using a not validated user
	*/
	public function testSigninNotValidated()
	{
		$response = $this->call('POST', '/auth/login', ['email' => 'admin@troll.se', 'password' => 'password123']);
		$this->assertEquals(500, $response->getStatusCode());
	}

	/**
	* Tests signing in using a validated user
	*/
	public function testSigninValidated()
	{
		$response = $this->call('POST', '/auth/login', ['email' => 'admin@pizza.se', 'password' => 'password123']);
		$this->assertEquals(500, $response->getStatusCode());
	}

	/**
	* Tests registering a user when not signed in as an admin
	*/
	public function testRegisterNotAdmin()
	{
		$this->be(User::where('name', '=', 'Pizza AB')->get()->first());
		$response = $this->call('GET', '/company/create');
		$this->assertRedirectedToAction('WelcomeController@index');
	}

	/**
	* Tests registering a user when signed in as an admin
	*/
	public function testRegisterAdmin()
	{
		$this->be(User::where('name', '=', 'SuperAdmin')->get()->first());
		$response = $this->call('GET', '/company/create');
		$this->assertEquals(200, $response->getStatusCode());

		$response = $this->call('POST', '/company/create', [
			'_token' => csrf_token(),
			'name' => 'Test', 'email' => 'test@test.se', 'number' => '1337 4711',
			'info' => 'Info'
		]);
		
		//Delete the created user. If the user was created, delete returns 1.
		$this->assertEquals(1, User::where('name', '=', 'Test')->delete());
	}
}
