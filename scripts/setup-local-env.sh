#!/bin/bash
#
# tflocal apply 後に LocalStack から API Gateway ID を取得し、
# docker/local/.env を生成するスクリプト
#
# 使い方:
#   bash scripts/setup-local-env.sh [--profile <profile>]
#
# デフォルトは --profile localstack を使用。
#

set -euo pipefail

PROFILE="localstack"
while [ $# -gt 0 ]; do
  case "$1" in
    --profile)
      PROFILE="${2:-localstack}"
      shift 2
      ;;
    --profile=*)
      PROFILE="${1#--profile=}"
      shift
      ;;
    *)
      echo "❌ 不明な引数です: $1"
      echo "   使い方: bash scripts/setup-local-env.sh [--profile <profile>]"
      exit 1
      ;;
  esac
done
ENDPOINT="http://localhost:4566"

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
ENV_FILE="${ROOT_DIR}/docker/local/.env"

echo "🔍 LocalStack から API Gateway ID を取得中..."

API_ID=$(aws --profile "${PROFILE}" --endpoint-url "${ENDPOINT}" \
  apigateway get-rest-apis \
  --query 'items[0].id' \
  --output text 2>/dev/null || true)

if [ -z "${API_ID}" ] || [ "${API_ID}" = "None" ]; then
  echo "❌ API Gateway ID を取得できませんでした。"
  echo "   認可サーバーで tflocal apply が完了しているか確認してください。"
  exit 1
fi

echo "✅ API Gateway ID: ${API_ID}"

cat > "${ENV_FILE}" <<EOF
API_GATEWAY_ID=${API_ID}
EOF

echo "✅ ${ENV_FILE} を生成しました。"
echo "   docker compose up -d でコンテナを再起動してください。"
