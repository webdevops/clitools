build:
	bash compile.sh

install:
	cp clitools.phar /usr/local/bin/ct

all: build install
