#!/bin/bash
#
# tflocal apply 後に API Gateway ID を受け取り、
# .github/workflows/ 内の全 CI ワークフローの AUTH_SERVER_URL を更新するスクリプト
#
# 使い方:
#   bash scripts/update-ci-auth-server-url.sh <api-gateway-id> [ngrok-domain]
#
# 認可サーバーリポジトリの terraform/local/scripts/setup-env.sh から呼び出す想定:
#   API_GW_ID=$(cd terraform/local && tflocal output -raw api_gateway_id)
#   bash /path/to/authorization-showcase/scripts/update-ci-auth-server-url.sh "${API_GW_ID}"
#

set -euo pipefail

API_GATEWAY_ID="${1:-}"
NGROK_DOMAIN="${2:-ample-precise-knee.ngrok-free.dev}"

if [ -z "${API_GATEWAY_ID}" ]; then
  echo "❌ Usage: $0 <api-gateway-id> [ngrok-domain]"
  exit 1
fi

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
WORKFLOWS_DIR="${ROOT_DIR}/.github/workflows"

NEW_URL="https://${NGROK_DOMAIN}/restapis/${API_GATEWAY_ID}/local/_user_request_"

echo "🔍 AUTH_SERVER_URL を更新中..."
echo "   ${NEW_URL}"
echo ""

for file in "${WORKFLOWS_DIR}"/*-ci.yml; do
  sed -i.bak "s|AUTH_SERVER_URL:.*|AUTH_SERVER_URL: ${NEW_URL}|g" "${file}"
  rm "${file}.bak"
  if grep -q "${NEW_URL}" "${file}"; then
    echo "✅ Updated: $(basename "${file}")"
  else
    echo "⚠️  Warning: $(basename "${file}") が更新されていない可能性があります"
  fi
done

echo ""
echo "✅ 完了。変更をコミットしてプッシュしてください。"
echo "   git add .github/workflows && git commit -m 'chore: update AUTH_SERVER_URL' && git push"
