#!/usr/bin/env bash
if ! command -v zip
then
  echo "Zip package is not currently installed"
  exit
fi

if ! command -v php5.6
then
  echo "PHP 5.6 package is not currently installed"
  exit
fi

if ! command -v composer
then
  echo "Composer package is not currently installed"
  exit
fi

mkdir dist
rm -rf vendor
php5.6 $(composer) install --no-dev -o
zip dist/altapay-for-woocommerce.zip -r * -x "dist/*" "tests/*" "bin/*" build.sh guide.md .gitignore phpunit.xml.dist phpstan.neon.dist composer.json composer.lock @
composer install
