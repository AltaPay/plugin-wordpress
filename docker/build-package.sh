#!/bin/bash

docker build ../ --file Dockerfile-build-image -t plugin-wordpress-package-build
docker run --mount type=bind,source="$(pwd)/..",target=/app plugin-wordpress-package-build /bin/bash -c 'cd /app && bash build.sh'