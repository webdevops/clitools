#!/bin/bash

set -o pipefail  # trace ERR through pipes
set -o errtrace  # trace ERR through 'time command' and other functions
set -o nounset   ## set -u : exit the script if you try to use an uninitialised variable
set -o errexit   ## set -e : exit the script if any statement returns a non-true return value

SCRIPT_DIR=$(dirname $(readlink -f "$0"))

OLD_PWD=`pwd`

cd "$SCRIPT_DIR/src"
composer install

cd "$SCRIPT_DIR/"
box.phar build -c build.json

cd "$OLD_PWD"
