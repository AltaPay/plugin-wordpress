#!/usr/bin/env bash

set -e

if ! command -v zip
then	
  echo "Zip package is not currently installed"
  echo "Or use docker/build-package.sh"
  exit
fi

if ! command -v php7.0
then
  echo "PHP 7.0 package is not currently installed"
  echo "Or use docker/build-package.sh"
  exit
fi

if ! command -v composer
then
  echo "Composer package is not currently installed"
  echo "Or use docker/build-package.sh"
  exit
fi

mkdir -p dist
rm -rf vendor
php7.0 $(command -v composer) install --no-dev -o
zip dist/altapay-for-woocommerce.zip -r * -x "dist/*" "tests/*" "bin/*" "terminal-config/*" build.sh README.md guide.md .gitignore phpunit.xml.dist phpstan.neon.dist composer.json composer.lock @
composer install
