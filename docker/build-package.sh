#!/bin/bash

docker build . --file docker/Dockerfile-build-image -t plugin-wordpress-package-build
docker run --rm --mount type=bind,source="$(pwd)",target=/app plugin-wordpress-package-build ../bin/bash -c 'cd /app && bash build.sh'
