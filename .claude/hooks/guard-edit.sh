#!/usr/bin/env bash
#
# PreToolUse(Edit|Write|MultiEdit): 機密ファイルへの誤編集をブロックする。
# 該当した場合は stderr に理由を出して exit 2（ツール呼び出し自体を拒否）。
# `.env.example` はテンプレートなので許可する。

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=lib.sh
. "$DIR/lib.sh"

INPUT="$(cat)"
FILE="$(json_field '.tool_input.file_path' "$INPUT")"
if [ -z "$FILE" ]; then
  # ガードは「ブロックする」ことが目的なので、入力を解析できないまま
  # 素通りさせる（フェイルオープン）と機密ファイル保護が無効化されてしまう。
  # jq/python3 のどちらも無い場合はその旨を必ず警告する。
  json_parser_available || echo "[claude-hook] 警告: jq/python3 が無く入力を解析できないため機密ファイルガードが無効です" >&2
  exit 0
fi

base="$(basename "$FILE")"

# 許可（テンプレート類）
case "$base" in
  .env.example|.env.sample|.env.dist) exit 0 ;;
esac

# ブロック対象（機密ファイル）
case "$FILE" in
  *.env|*/.env|*.env.*|*/.env.*) blocked=1 ;;
esac
case "$base" in
  .env|credentials|credentials.json|*.pem|*.key|*.p12|*.pfx|*.keystore|id_rsa|id_ed25519|*.secret) blocked=1 ;;
esac

if [ "${blocked:-0}" = "1" ]; then
  echo "機密ファイルの可能性があるため編集をブロックしました: $FILE" >&2
  echo "テンプレート(.env.example 等)を編集し、実ファイルは手動で更新してください。" >&2
  echo "どうしても必要な場合はこのフックを一時的に無効化してください。" >&2
  exit 2
fi
exit 0
