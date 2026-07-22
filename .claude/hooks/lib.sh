# shellcheck shell=bash
#
# Claude Code Hooks 共通ライブラリ
#
# このリポジトリは Go / Java / PHP(CakePHP,CodeIgniter,FuelPHP) / Python / Ruby /
# frontend(Nuxt) が混在し、各バックエンドは基本 Docker コンテナ内で動かす前提。
# フックは Claude Code(Devin) のホスト側で実行されるため、ツールが
#   1. ホストの PATH（or vendor/bin 等）にある      → そのまま実行
#   2. 無ければ対応コンテナが起動している           → docker compose exec 経由で実行
#   3. どちらも無い                                 → 何もせず正常終了（開発を止めない）
# の順にフォールバックする。整形/最適化系フックは常に exit 0（非ブロッキング）。

set -uo pipefail

# リポジトリルート（Claude Code が自動で渡す。無い場合はスクリプト位置から解決）
PROJECT_DIR="${CLAUDE_PROJECT_DIR:-$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)}"

# コマンドが長時間ハングしないよう全ての実行を timeout でラップ
TOOL_TIMEOUT="${CLAUDE_HOOK_TIMEOUT:-120}"

log() { printf '[claude-hook] %s\n' "$*" >&2; }

have() { command -v "$1" >/dev/null 2>&1; }

run() { timeout "${TOOL_TIMEOUT}" "$@"; }

# JSON パーサ(jq / python3)のいずれかが使えるか
json_parser_available() { have jq || have python3; }

# stdin の JSON から値を取り出す。jq を優先し、無ければ python3 にフォールバック。
# どちらも無い場合は空文字（ガード側で無効化を検知して警告する）。
json_field() {
  local field="$1" input="$2"
  if have jq; then
    printf '%s' "$input" | jq -r "$field // empty" 2>/dev/null
  elif have python3; then
    printf '%s' "$input" | python3 -c '
import sys, json
path = [p for p in sys.argv[1].lstrip(".").split(".") if p]
try:
    v = json.load(sys.stdin)
    for k in path:
        v = v.get(k) if isinstance(v, dict) else None
    print("" if v is None else v)
except Exception:
    print("")
' "$field"
  fi
}

# バックエンドディレクトリ(リポジトリ相対) → docker-compose の (project service compose_file)
# docker/bin/docker-*.sh の設定に一致させている。
docker_target_for() {
  case "$1" in
    backends/go-gin)           echo "showcase-go go docker/local/app-go/docker-compose.yml" ;;
    backends/java-springboot)  echo "showcase-java java docker/local/app-java/docker-compose.yml" ;;
    backends/php-cakephp)      echo "showcase-php-cake php docker/local/app-php-cake/docker-compose.yml" ;;
    backends/php-codeigniter)  echo "showcase-php-codeigniter php docker/local/app-php-codeigniter/docker-compose.yml" ;;
    backends/php-fuelphp)      echo "showcase-php-fuel php docker/local/app-php-fuel/docker-compose.yml" ;;
    backends/python-django)    echo "showcase-python python docker/local/app-python/docker-compose.yml" ;;
    backends/ruby-rails)       echo "showcase-ruby rb-rails docker/local/app-ruby/docker-compose.yml" ;;
    *)                         return 1 ;;
  esac
}

# docker compose コマンド名を解決（docker compose / docker-compose）
_compose() {
  if have docker && docker compose version >/dev/null 2>&1; then
    echo "docker compose"
  elif have docker-compose; then
    echo "docker-compose"
  else
    return 1
  fi
}

# バックエンドの service コンテナが起動中か
container_up() {
  local backend_rel="$1" project service compose_file cc
  read -r project service compose_file <<<"$(docker_target_for "$backend_rel")" || return 1
  cc="$(_compose)" || return 1
  [ -n "$($cc -p "$project" -f "$PROJECT_DIR/$compose_file" ps -q "$service" 2>/dev/null)" ]
}

# コンテナ内でコマンドを実行（working_dir はバックエンドルート = 相対パスがそのまま使える）
in_container() {
  local backend_rel="$1"; shift
  local project service compose_file cc
  read -r project service compose_file <<<"$(docker_target_for "$backend_rel")" || return 1
  cc="$(_compose)" || return 1
  run $cc -p "$project" -f "$PROJECT_DIR/$compose_file" exec -T "$service" "$@"
}
