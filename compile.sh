#!/bin/bash

set -o pipefail  # trace ERR through pipes
set -o errtrace  # trace ERR through 'time command' and other functions
set -o nounset   ## set -u : exit the script if you try to use an uninitialised variable
set -o errexit   ## set -e : exit the script if any statement returns a non-true return value

READLINK='readlink'

[[ `uname` == 'Darwin' ]] && {
	which greadlink > /dev/null && {
		READLINK='greadlink'
	} || {
		echo 'ERROR: GNU utils required for Mac. You may use homebrew to install them: brew install coreutils gnu-sed'
		exit 1
	}
}

SCRIPT_DIR=$(dirname $($READLINK -f "$0"))

OLD_PWD=`pwd`

## copy configs
cp "$SCRIPT_DIR/Documentation/Examples/clisync.yml"  "$SCRIPT_DIR/src/conf/"

## run composer
cd "$SCRIPT_DIR/src"
composer install --no-dev
composer dump-autoload --optimize --no-dev

## create phar
cd "$SCRIPT_DIR/"

if [[ ! -f vendor/box.phar ]]; then
    mkdir -p vendor
    wget -Ovendor/box.phar https://github.com/box-project/box2/releases/download/2.7.5/box-2.7.5.phar
fi

BOX_PATH=vendor/box.phar

php -d phar.readonly=0 "$BOX_PATH" build -c box.json

cd "$OLD_PWD"

echo "Build finished, for installation run: make install"
