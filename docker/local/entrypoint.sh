#!/bin/sh
set -e

if [ -n "${API_GATEWAY_ID}" ]; then
  export AUTH_SERVER_URL="http://host.docker.internal:4566/restapis/${API_GATEWAY_ID}/local/_user_request_"
  echo "✅ AUTH_SERVER_URL=${AUTH_SERVER_URL}"
else
  echo "⚠️  API_GATEWAY_ID が未設定のため AUTH_SERVER_URL はデフォルト値を使用します。"
  echo "   scripts/setup-local-env.sh を実行してください。"
fi

exec "$@"
