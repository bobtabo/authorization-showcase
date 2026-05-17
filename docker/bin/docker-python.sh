#!/bin/bash
#
# Pythonコンテナ環境を操作
#

ARG="${1}"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "${SCRIPT_DIR}/../local/app-python"

if [ "${ARG}" = "up" ]; then
    if [ ! -f .env ]; then
        cp .env.example .env
    fi
    docker-compose -p app-python -f docker-compose.yml up -d --build --force-recreate
elif [ "${ARG}" = "down" ]; then
    docker-compose -p app-python -f docker-compose.yml down --rmi all --volumes
elif [ "${ARG}" = "exec" ]; then
    docker-compose -p app-python -f docker-compose.yml exec --user 1000 python bash
else
    echo "使い方: $0 {up|down|exec}"
    exit 1
fi
