#!/usr/bin/env bash
#
# PostToolUse(Edit|Write|MultiEdit): 編集した「そのファイルのみ」を対象に軽量な
# Lint/静的解析を実行し、警告があれば stderr に通知する（非ブロッキング, exit 0）。
# Stop 時のフル解析は重くなりがちなため、変更ファイル単位に絞っている。
# 整形フック(format.sh)の後に走る想定。

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=lib.sh
. "$DIR/lib.sh"

INPUT="$(cat)"
FILE="$(json_field '.tool_input.file_path' "$INPUT")"
[ -z "$FILE" ] && exit 0

case "$FILE" in
  "$PROJECT_DIR"/*) REL="${FILE#"$PROJECT_DIR"/}" ;;
  /*)               exit 0 ;;
  *)                REL="$FILE" ;;
esac
ABS="$PROJECT_DIR/$REL"
[ -f "$ABS" ] || exit 0

out=""
case "$REL" in
  backends/go-gin/*.go)
    if have go; then out="$( cd "$PROJECT_DIR/backends/go-gin" && run go vet "./$(dirname "${REL#backends/go-gin/}")/..." 2>&1 )"; fi ;;
  backends/php-cakephp/*.php)
    if [ -x "$PROJECT_DIR/backends/php-cakephp/vendor/bin/phpcs" ]; then
      out="$( cd "$PROJECT_DIR/backends/php-cakephp" && run vendor/bin/phpcs "${REL#backends/php-cakephp/}" 2>&1 )"
    fi ;;
  backends/python-django/*.py)
    if have ruff; then out="$( cd "$PROJECT_DIR/backends/python-django" && run ruff check "${REL#backends/python-django/}" 2>&1 )"; fi ;;
  backends/ruby-rails/*.rb)
    if have bundle; then out="$( cd "$PROJECT_DIR/backends/ruby-rails" && run bundle exec rubocop --format simple "${REL#backends/ruby-rails/}" 2>&1 )"; fi ;;
  frontend/*|e2e/*)
    root="frontend"; case "$REL" in e2e/*) root="e2e" ;; esac
    case "${REL##*.}" in
      js|jsx|ts|tsx|vue|mjs|cjs)
        if [ -d "$PROJECT_DIR/$root/node_modules" ] && have npx; then
          out="$( cd "$PROJECT_DIR/$root" && run npx --no-install eslint "$ABS" 2>&1 )"
        fi ;;
    esac ;;
esac

if [ -n "$out" ]; then
  log "Lint 警告 ($REL):"
  printf '%s\n' "$out" >&2
fi
exit 0
