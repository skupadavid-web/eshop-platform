.DEFAULT_GOAL := help
.PHONY: help up down restart sh logs console test stan cs fix ci

help: ## Seznam příkazů
	@grep -hE '^[a-z].*:.*##' $(MAKEFILE_LIST) | sed 's/:.*## /\t/' | column -t -s $$'\t'

up: ## Spustit DDEV
	ddev start
down: ## Zastavit DDEV
	ddev stop
restart: ## Restart DDEV
	ddev restart
sh: ## Shell ve web kontejneru
	ddev ssh
logs: ## Logy
	ddev logs -f
console: ## bin/console (make console c="cache:clear")
	ddev exec php bin/console $(c)
test: ## PHPUnit
	ddev exec php bin/phpunit
stan: ## PHPStan
	ddev exec vendor/bin/phpstan analyse --no-progress
cs: ## php-cs-fixer (kontrola)
	ddev exec vendor/bin/php-cs-fixer fix --dry-run --diff
fix: ## php-cs-fixer (oprava)
	ddev exec vendor/bin/php-cs-fixer fix
ci: cs stan test ## Vše co běží v CI
