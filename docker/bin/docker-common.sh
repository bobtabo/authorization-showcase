#!/bin/bash
#
# 共通コンテナ環境を操作
#

ARG="${1}"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "${SCRIPT_DIR}/../local/common"

if [ "${ARG}" = "up" ]; then
    docker network create --driver bridge showcase
    docker-compose up -d --build
elif [ "${ARG}" = "down" ]; then
    docker-compose down --rmi all --volumes
    docker network rm showcase
    rm -fdR data
    rm -fdR logs
elif [ "${ARG}" = "start" ]; then
    docker-compose start
elif [ "${ARG}" = "stop" ]; then
    docker-compose stop
else
    echo "使い方: $0 {up|down|start|stop}"
    exit 1
fi
