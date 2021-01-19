#!/usr/bin/env bash
if command -v dpkg-query -l zip
then
  mkdir dist
  php5.6 /usr/bin/composer install --no-dev -o
  zip dist/altapay-for-woocommerce.zip -r * -x "dist/*" "tests/*" "bin/*" build.sh guide.md .gitignore phpunit.xml.dist phpstan.neon.dist composer.json composer.lock @
else
  echo "Zip package is not currently installed"
fi
