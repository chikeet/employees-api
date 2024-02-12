############################################################
# PROJECT ##################################################
############################################################
.PHONY: project
project: install setup

.PHONY: init
init:
	cp config/local.neon.example config/local.neon

.PHONY: install
install:
	composer install

.PHONY: setup
setup:
	mkdir -p var/tmp var/log
	chmod 0777 var/tmp var/log

.PHONY: fixtures
fixtures:
	cp data/xml/fixtures/user.xml data/xml/user.xml
	cp data/xml/fixtures/employee.xml data/xml/employee.xml
	chmod 0777 data/xml/user.xml data/xml/employee.xml

.PHONY: clean
clean:
	find var/tmp -mindepth 1 ! -name '.gitignore' -type f -or -type d -exec rm -rf {} +
	find var/log -mindepth 1 ! -name '.gitignore' -type f -or -type d -exec rm -rf {} +

############################################################
# DEVELOPMENT ##############################################
############################################################
.PHONY: qa
qa: cs phpstan

.PHONY: cs
cs:
	vendor/bin/codesniffer app tests

.PHONY: csf
csf:
	vendor/bin/codefixer app tests

.PHONY: phpstan
phpstan:
	vendor/bin/phpstan analyse -c phpstan.neon --memory-limit=512M

.PHONY: tests
tests:
	vendor/bin/tester -s -p php --colors 1 -C tests

.PHONY: coverage
coverage:
	vendor/bin/tester -s -p phpdbg --colors 1 -C --coverage ./coverage.xml --coverage-src ./app tests

.PHONY: dev
dev:
	XDEBUG_MODE=debug NETTE_DEBUG=1 NETTE_ENV=dev php -S 0.0.0.0:8000 -t www

############################################################
# DEPLOYMENT ###############################################
############################################################
.PHONY: deploy
deploy:
	$(MAKE) clean
	$(MAKE) project
	$(MAKE) clean
