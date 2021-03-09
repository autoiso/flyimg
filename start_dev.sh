#!/bin/bash

docker rm -v -f flyimg_dev
docker build -t flyimg_dev -f ./Dockerfile.dev .

docker run -d \
-v $(pwd)/:/var/www/html:rw \
--name flyimg_dev \
-p 2081:80 \
flyimg_dev

docker logs -f flyimg_dev