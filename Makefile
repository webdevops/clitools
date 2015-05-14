build:
	bash compile.sh

install:
	cp clitools.phar /usr/local/bin/ct

all: build install

autoload:
	sh -c "cd src ; composer dump-autoload --optimize --no-dev"
