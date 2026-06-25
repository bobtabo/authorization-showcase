#!/bin/sh
set -e

API_ID=$(curl -sf http://host.docker.internal:4566/restapis 2>/dev/null \
  | grep -o '"id":"[^"]*"' | head -1 | cut -d'"' -f4 || true)

if [ -n "${API_ID}" ]; then
  export AUTH_SERVER_URL="http://host.docker.internal:4566/restapis/${API_ID}/local/_user_request_"
  echo "✅ AUTH_SERVER_URL=${AUTH_SERVER_URL}"
else
  echo "⚠️  LocalStack の api-id を取得できませんでした。AUTH_SERVER_URL はデフォルト値を使用します。"
fi

exec "$@"
