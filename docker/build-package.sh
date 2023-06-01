#!/bin/bash

supported_versions_msg="This script supports only PHP versions 7.3 & 8.1 with docker"

if [ -z "$1" ]
  then
    echo "No PHP version supplied"
    echo "$supported_versions_msg"
    echo "Usage: ./build-package.sh <php_version>"
    echo "./build-package.sh 7.3"
    echo "./build-package.sh 8.1"
    echo "composer.lock will be updated for php 8.1"
    exit 1
fi

if [ "$1" == "7.3" ]
  then
    docker build . --file docker/Dockerfile-build-image-php-7.3 -t plugin-wordpress-package-build
elif [ "$1" == "8.1" ]
  then
    docker build . --file docker/Dockerfile-build-image-php-8.1 -t plugin-wordpress-package-build
else
  echo "$supported_versions_msg"
  exit 1
fi

docker run --rm --mount type=bind,source="$(pwd)",target=/app plugin-wordpress-package-build ../bin/bash -c 'cd /app && bash build.sh '$1
