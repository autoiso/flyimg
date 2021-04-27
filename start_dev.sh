#!/bin/bash

# postgres
docker rm -f fi_dev_postgres
docker run -d  -e POSTGRES_PASSWORD=postgres \
                    --name fi_dev_postgres \
                    -p 45432:5432  \
                    postgres:9.6
sleep 5
docker exec -i fi_dev_postgres sh -c "localedef -i pl_PL -c -f UTF-8 -A /usr/share/locale/locale.alias pl_PL.UTF-8 && locale -a"
docker restart fi_dev_postgres
sleep 5
docker exec -i fi_dev_postgres psql -U postgres < $(pwd)/resources/postgres/db_user.sql
docker exec -i fi_dev_postgres psql -U fi_dev -d fi_dev < $(pwd)/resources/postgres/db_schema.sql

# app
docker rm -v -f flyimg_dev
docker build -t flyimg_dev -f ./Dockerfile.dev .

docker run -d \
--link fi_dev_postgres:fi_dev_postgres \
-v $(pwd)/:/var/www/html:rw \
--name flyimg_dev \
--env-file $(pwd)/resources/app_envs \
-p 2081:80 \
flyimg_dev

docker logs -f flyimg_dev