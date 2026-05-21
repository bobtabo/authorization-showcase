#!/bin/bash
set -e

APP_DIR=/var/www/app-java

for dir in build .gradle; do
    mkdir -p "${APP_DIR}/${dir}"
    chown docker:docker "${APP_DIR}/${dir}"
done

exec gosu docker "$@"
