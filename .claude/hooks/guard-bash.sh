#!/usr/bin/env bash
#
# PreToolUse(Bash): 危険/非推奨なコマンドをブロックまたは誘導する。
#   1. 危険な git 操作(push --force / reset --hard / branch -D / clean -fd 等)
#   2. Docker 停止コマンドの直接実行(docker/bin のスクリプト利用へ誘導)
#   3. git commit 前のシークレット混入スキャン(gitleaks があれば実行)
# 該当時は理由を stderr に出して exit 2（ツール呼び出しを拒否）。

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=lib.sh
. "$DIR/lib.sh"

INPUT="$(cat)"
CMD="$(json_field '.tool_input.command' "$INPUT")"
if [ -z "$CMD" ]; then
  # ガードは危険コマンドを「ブロックする」ことが目的。入力を解析できないまま
  # 素通りさせるのは危険なので、パーサが無い場合は必ず警告する。
  json_parser_available || echo "[claude-hook] 警告: jq/python3 が無く入力を解析できないため危険コマンドガードが無効です" >&2
  exit 0
fi

deny() { echo "$1" >&2; exit 2; }

# 空白を潰した比較用文字列
norm="$(printf '%s' "$CMD" | tr -s '[:space:]' ' ')"

# ---- 1. 危険な git 操作 ----
if printf '%s' "$norm" | grep -Eq '(^|[;&|] *)git .*push .*(--force([^-]|$)|-f([^a-z]|$))' \
   && ! printf '%s' "$norm" | grep -q -- '--force-with-lease'; then
  deny "危険: 'git push --force' を検知。--force-with-lease を使うか、意図を確認してから手動実行してください。"
fi
printf '%s' "$norm" | grep -Eq '(^|[;&|] *)git .*reset .*--hard' \
  && deny "危険: 'git reset --hard' を検知。作業内容を失う恐れがあります。必要なら手動で実行してください。"
printf '%s' "$norm" | grep -Eq '(^|[;&|] *)git .*branch .*-D([^a-z]|$)' \
  && deny "危険: 'git branch -D'（強制削除）を検知。-d を使うか手動で確認してください。"
printf '%s' "$norm" | grep -Eq '(^|[;&|] *)git .*clean .*-[a-z]*f' \
  && deny "危険: 'git clean -f' を検知。未追跡ファイルを失う恐れがあります。手動で確認してください。"
printf '%s' "$norm" | grep -Eq '(^|[;&|] *)git .*(checkout|restore) .*(--|\.)' \
  && printf '%s' "$norm" | grep -Eq '(^|[;&|] *)git .*(checkout \.|restore \.)' \
  && deny "危険: 作業ツリー全体の破棄操作を検知。手動で確認してください。"

# ---- 2. Docker 停止コマンドのガード ----
if printf '%s' "$norm" | grep -Eq '(^|[;&|] *)docker(-compose)? +(stop|kill)( |$)' \
   || printf '%s' "$norm" | grep -Eq '(^|[;&|] *)docker +compose +(stop|kill|down)( |$)' \
   || printf '%s' "$norm" | grep -Eq '(^|[;&|] *)docker-compose +down( |$)'; then
  # docker/bin のラッパスクリプト経由なら許可
  if printf '%s' "$norm" | grep -Eq 'docker/bin/docker-[a-z-]+\.sh'; then
    :
  else
    cat >&2 <<'EOS'
Docker コンテナは直接停止せず、リポジトリのラッパスクリプトを使ってください:
  docker/bin/docker-common.sh   down   # 共通コンテナ(nginx-proxy)
  docker/bin/docker-backends.sh down   # 全バックエンド一括
  docker/bin/docker-go.sh       down   # 言語別(go/java/php-cake/php-codeigniter/php-fuel/python/ruby)
EOS
    exit 2
  fi
fi

# ---- 3. git commit 前のシークレットスキャン ----
if printf '%s' "$norm" | grep -Eq '(^|[;&|] *)git +commit( |$)'; then
  if have gitleaks; then
    if ! run gitleaks git --no-banner --staged "$PROJECT_DIR" >/tmp/gitleaks-hook.log 2>&1 \
       && ! run gitleaks protect --no-banner --staged --source "$PROJECT_DIR" >/tmp/gitleaks-hook.log 2>&1; then
      echo "gitleaks がステージ済みの変更にシークレットを検知しました。コミットを中止します。" >&2
      tail -n 40 /tmp/gitleaks-hook.log >&2
      exit 2
    fi
  else
    log "gitleaks が見つからないためシークレットスキャンをスキップ（導入推奨: https://github.com/gitleaks/gitleaks）"
  fi
fi

exit 0
