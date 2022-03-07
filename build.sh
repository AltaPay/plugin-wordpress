#!/usr/bin/env bash

if [ -z "$1" ]
  then
    echo "No PHP version supplied"
    echo "Usage: ./build.sh <php_version>"
    echo "./build.sh 7.0"
    echo "./build.sh 8.1"
    echo "composer.lock will be updated for php > 7.0"
    exit
fi

set -e

if ! command -v zip
then
  echo "Zip package is not currently installed"
  echo "Or use './docker/build-package.sh <php_version>'"
  exit
fi

if ! command -v php$1
then
  echo "PHP $1 package is not currently installed"
  echo "Or use './docker/build-package.sh <php_version>'"
  exit
fi

if ! command -v composer
then
  echo "Composer package is not currently installed"
  echo "Or use './docker/build-package.sh <php_version>'"
  exit
fi

if [ "$1" == "7.0" ]
then
  composer_command="install"
else
  composer_command="update"
  cp composer.lock{,.backup}
fi

mkdir -p dist
rm -rf vendor
php$1 $(command -v composer) $composer_command --no-dev -o --no-interaction

if [ -f composer.lock.backup ]; then
    rm -rf composer.lock
    mv composer.lock.backup composer.lock
fi

zip dist/altapay-for-woocommerce.zip -r * -x "dist/*" "tests/*" "bin/*" "terminal-config/*" "docs/*" wiki.md build.sh README.md guide.md .gitignore phpunit.xml.dist phpstan.neon.dist composer.json composer.lock @
composer $composer_command --no-interaction


