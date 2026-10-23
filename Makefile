check:
	find src tests -name '*.php' -print0 | xargs -0 -n1 php -l

test:
	./vendor/bin/phpunit
