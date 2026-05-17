#!/bin/bash
set -e

# named volume と GRADLE_USER_HOME を gradle ユーザー所有に変更（root として起動時のみ実行）
chown -R gradle:gradle /home/gradle/.gradle 2>/dev/null || true

for dir in build .gradle .kotlin; do
    mkdir -p "/var/www/app-kotlin/$dir"
    chown gradle:gradle "/var/www/app-kotlin/$dir"
done

# gradle ユーザーに降格してコマンドを実行
exec gosu gradle "$@"
