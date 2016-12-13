<?php

return [
	'driver' => 'smtp',
	'host' => 'mail.datacenter.se',
	'port' => 587,
    'from' => array('address' => 'lynx.tool@edwardlynx.com', 'name' => 'Edward Lynx'),
	'encryption' => 'tls',
	'username' => 'lynxtool@edwardlynx.com',
	'password' => '24J9%f2jdJ',
	'sendmail' => '/usr/sbin/sendmail -bs',
	'pretend' => false,
];

// return [
//     'driver' => 'mailgun',
//     'host' => 'smtp.mailgun.org',
//     'port' => 587,
//     'from' => array('address' => 'noreply@EdwardLynx.com', 'name' => 'Edward Lynx'),
//     'encryption' => 'tls',
// ];
