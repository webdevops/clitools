all: autoload build install

build:
	bash compile.sh

install:
	cp clitools.phar /usr/local/bin/ct

autoload:
	sh -c "cd src ; composer dump-autoload --optimize --no-dev"

update:
	sh -c "cd src ; composer update"

sloccount:
	sloccount src/app/ src/command.php
