#!/usr/bin/env bash

if [ -z "$1" ]
  then
    echo "No PHP version supplied"
    echo "Usage: ./build.sh <php_version>"
    echo "./build.sh 7.4"
    echo "./build.sh 8.2"
    echo "composer.lock will be updated for php > 7.4"
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

if [ "$1" = "7.4" ]
then
  composer_command="install"
else
  composer_command="update"
  cp composer.lock composer.lock.backup
fi

if [ ! -d dist ];
then
  mkdir -p dist
else
  rm -rf dist/altapay-for-woocommerce.zip
fi

mkdir -p dist
rm -rf vendor build
php$1 $(command -v composer) $composer_command --no-dev -o --no-interaction
yes | php$1 vendor/bin/php-scoper add-prefix
rsync -a build/vendor/* vendor/ && rm -rf build/
php$1 $(command -v composer) remove humbug/php-scoper
php$1 $(command -v composer) remove phpstan/extension-installer phpunit/phpunit szepeviktor/phpstan-wordpress --dev
php$1 $(command -v composer) dump-autoload --working-dir ./ --classmap-authoritative
zip dist/altapay-for-woocommerce.zip -r * -x "dist/*" "tests/*" "bin/*" "terminal-config/*" "docs/*" "docker/*" wiki.md build.sh scoper.inc.php README.md CHANGELOG.md guide.md .gitignore phpunit.xml.dist phpstan.neon.dist composer.json composer.lock composer.lock.backup @

if [ -f composer.lock.backup ]; then
    rm -rf composer.lock
    mv composer.lock.backup composer.lock
fi
