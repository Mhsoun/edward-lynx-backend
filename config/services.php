<?php

return [

	/*
	|--------------------------------------------------------------------------
	| Third Party Services
	|--------------------------------------------------------------------------
	|
	| This file is for storing the credentials for third party services such
	| as Stripe, Mailgun, Mandrill, and others. This file provides a sane
	| default location for this type of information, allowing packages
	| to have a conventional place to find your various credentials.
	|
	*/

	// 'mailgun' => [
	// 	'domain' => 'sandboxc7b0dccd7f3842f4b2c4fcf4d950e235.mailgun.org',
	// 	'secret' => 'key-4a7b3f1084eb8528b218e3f89ff645df',
	// ],

	'mailgun' => [
		'domain' => 'mg.lynxtool.edwardlynx.com',
		'secret' => 'key-4a7b3f1084eb8528b218e3f89ff645df',
	],

	'mandrill' => [
		'secret' => '',
	],

	'ses' => [
		'key' => '',
		'secret' => '',
		'region' => 'us-east-1',
	],

	'stripe' => [
		'model'  => 'User',
		'secret' => '',
	],
    
    'firebase' => [
        'api_key' => env('FIREBASE_API_KEY')
    ]
];
