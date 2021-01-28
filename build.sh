#!/usr/bin/env bash

set -e

if ! command -v zip
then	
  echo "Zip package is not currently installed"
  echo "Or use docker/build-package.sh"
  exit
fi

if ! command -v php5.6
then
  echo "PHP 5.6 package is not currently installed"
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
php5.6 $(command -v composer) install --no-dev -o
zip dist/altapay-for-woocommerce.zip -r * -x "dist/*" "tests/*" "bin/*" build.sh guide.md .gitignore phpunit.xml.dist phpstan.neon.dist composer.json composer.lock @
composer install
