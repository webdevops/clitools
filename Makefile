TAG ?= $(shell git describe --tags)

all: clean autoload build

clean:
	rm -f clitools.phar

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

install-box:
	curl -LSs https://box-project.github.io/box2/installer.php | php
	mv box.phar /usr/local/bin

release: all
ifndef desc
	@echo "Run it as 'make release desc=tralala'"
else
	github-release release -u webdevops -r clitools -t "$(TAG)" -n "$(TAG)" --description "$(desc)"
	echo "Uploading clitools.phar" && \
	github-release upload -u webdevops \
	                      -r clitools \
	                      -t $(TAG) \
	                      -f "clitools.phar" \
	                      -n "clitools.phar"
endif

