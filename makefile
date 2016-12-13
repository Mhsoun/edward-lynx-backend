enable-tests:
	composer dump-autoload

enable-tests2:
	php composer.phar dump-autoload

test:
	 php ./vendor/bin/phpunit
	 
test-win:
	.\vendor\bin\phpunit
	
run:
	php -S localhost:1337 -t public