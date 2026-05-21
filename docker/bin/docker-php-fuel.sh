#!/bin/bash
#
# PHP FuelPHPコンテナ環境を操作
#

ARG="${1}"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "${SCRIPT_DIR}/../local/app-php-fuel"

if [ "${ARG}" = "up" ]; then
    if [ ! -f .env ]; then
        cp .env.example .env
    fi
    docker-compose -p showcase-php-fuel -f docker-compose.yml up -d --build --force-recreate
elif [ "${ARG}" = "down" ]; then
    docker-compose -p showcase-php-fuel -f docker-compose.yml down --rmi all --volumes
elif [ "${ARG}" = "exec" ]; then
    docker-compose -p showcase-php-fuel -f docker-compose.yml exec --user 1000 php bash
else
    echo "使い方: $0 {up|down|exec}"
    exit 1
fi
