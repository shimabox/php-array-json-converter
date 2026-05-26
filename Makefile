.PHONY: build test serve shell composer-dump

build:
	docker compose build

test:
	docker compose run --rm app vendor/bin/phpunit

serve:
	docker compose up app

shell:
	docker compose run --rm app sh

composer-dump:
	docker compose run --rm app composer dump-autoload

composer-install:
	docker compose run --rm app composer install
