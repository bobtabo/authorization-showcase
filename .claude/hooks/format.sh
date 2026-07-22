#!/usr/bin/env bash
#
# PostToolUse(Edit|Write|MultiEdit): 編集ファイルを言語/フレームワーク別に
# 「① コードフォーマット → ② インポート最適化 → ③ 再フォーマット」の順で整える。
# ② で崩れる整形を ③ で戻すため順序は固定。常に exit 0（非ブロッキング）。
#
# PHP 3FW は拡張子が同じ(*.php)なので、ディレクトリパスで分岐する。

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=lib.sh
. "$DIR/lib.sh"

INPUT="$(cat)"
FILE="$(json_field '.tool_input.file_path' "$INPUT")"
[ -z "$FILE" ] && exit 0

# リポジトリ相対パスに正規化
case "$FILE" in
  "$PROJECT_DIR"/*) REL="${FILE#"$PROJECT_DIR"/}" ;;
  /*)               exit 0 ;;   # プロジェクト外は対象外
  *)                REL="$FILE" ;;
esac
ABS="$PROJECT_DIR/$REL"
[ -f "$ABS" ] || exit 0

# バックエンドルート(リポジトリ相対) と ルートからのサブパスを求める
backend_rel=""; sub=""
for b in backends/go-gin backends/java-springboot backends/php-cakephp \
         backends/php-codeigniter backends/php-fuelphp backends/python-django \
         backends/ruby-rails; do
  case "$REL" in
    "$b"/*) backend_rel="$b"; sub="${REL#"$b"/}"; break ;;
  esac
done

case "$REL" in
  # ---- Go (gofmt はGo標準で導入済み / goimports は任意) ----
  backends/go-gin/*.go)
    if have gofmt; then ( cd "$PROJECT_DIR/$backend_rel" && run gofmt -w "$sub" ); fi
    # goimports があれば import 最適化 + 整形（gofmt 兼用なので再整形は省略可）
    if have goimports; then ( cd "$PROJECT_DIR/$backend_rel" && run goimports -w "$sub" )
    elif container_up backends/go-gin; then
      in_container backends/go-gin sh -c "command -v goimports >/dev/null 2>&1 && goimports -w '$sub' || gofmt -w '$sub'"
    fi
    ;;

  # ---- Java (google-java-format があれば使用。spotless 等は未設定) ----
  backends/java-springboot/*.java)
    if have google-java-format; then
      ( cd "$PROJECT_DIR/$backend_rel" && run google-java-format -i "$sub" )
    else
      log "java: google-java-format が見つからないためスキップ ($sub)"
    fi
    ;;

  # ---- PHP CakePHP (vendor/bin/phpcbf 導入済み。ordered/unused import も内包) ----
  backends/php-cakephp/*.php)
    if [ -x "$PROJECT_DIR/$backend_rel/vendor/bin/phpcbf" ]; then
      ( cd "$PROJECT_DIR/$backend_rel" && run vendor/bin/phpcbf "$sub" )
    elif container_up backends/php-cakephp; then
      in_container backends/php-cakephp sh -c "[ -x vendor/bin/phpcbf ] && vendor/bin/phpcbf '$sub' || true"
    else
      log "php-cakephp: phpcbf が見つからないためスキップ ($sub)"
    fi
    ;;

  # ---- PHP CodeIgniter (php-cs-fixer / phpcbf。未導入の場合はスキップ) ----
  backends/php-codeigniter/*.php)
    if [ -x "$PROJECT_DIR/$backend_rel/vendor/bin/php-cs-fixer" ]; then
      ( cd "$PROJECT_DIR/$backend_rel" && run vendor/bin/php-cs-fixer fix "$sub" )
    elif [ -x "$PROJECT_DIR/$backend_rel/vendor/bin/phpcbf" ]; then
      ( cd "$PROJECT_DIR/$backend_rel" && run vendor/bin/phpcbf "$sub" )
    elif container_up backends/php-codeigniter; then
      in_container backends/php-codeigniter sh -c "[ -x vendor/bin/php-cs-fixer ] && vendor/bin/php-cs-fixer fix '$sub' || { [ -x vendor/bin/phpcbf ] && vendor/bin/phpcbf '$sub'; } || true"
    else
      log "php-codeigniter: php-cs-fixer/phpcbf 未導入のためスキップ ($sub)"
    fi
    ;;

  # ---- PHP FuelPHP (vendor-dir が fuel/vendor。未導入の場合はスキップ) ----
  backends/php-fuelphp/*.php)
    if [ -x "$PROJECT_DIR/$backend_rel/fuel/vendor/bin/php-cs-fixer" ]; then
      ( cd "$PROJECT_DIR/$backend_rel" && run fuel/vendor/bin/php-cs-fixer fix "$sub" )
    elif [ -x "$PROJECT_DIR/$backend_rel/fuel/vendor/bin/phpcbf" ]; then
      ( cd "$PROJECT_DIR/$backend_rel" && run fuel/vendor/bin/phpcbf "$sub" )
    elif container_up backends/php-fuelphp; then
      in_container backends/php-fuelphp sh -c "[ -x fuel/vendor/bin/php-cs-fixer ] && fuel/vendor/bin/php-cs-fixer fix '$sub' || { [ -x fuel/vendor/bin/phpcbf ] && fuel/vendor/bin/phpcbf '$sub'; } || true"
    else
      log "php-fuelphp: php-cs-fixer/phpcbf 未導入のためスキップ ($sub)"
    fi
    ;;

  # ---- Python (black で整形 → ruff で import 最適化。未導入ならコンテナ→スキップ) ----
  backends/python-django/*.py)
    if have black; then ( cd "$PROJECT_DIR/$backend_rel" && run black -q "$sub" ); fi
    if have ruff; then ( cd "$PROJECT_DIR/$backend_rel" && run ruff check --fix --select I,F401 -q "$sub"; run ruff format -q "$sub" ); fi
    if ! have black && ! have ruff; then
      if container_up backends/python-django; then
        in_container backends/python-django sh -c "command -v black >/dev/null 2>&1 && black -q '$sub'; command -v ruff >/dev/null 2>&1 && { ruff check --fix --select I,F401 -q '$sub'; ruff format -q '$sub'; }; true"
      else
        log "python-django: black/ruff 未導入のためスキップ ($sub)"
      fi
    fi
    ;;

  # ---- Ruby (bundle exec rubocop -a。rubocop-rails-omakase 導入済み) ----
  backends/ruby-rails/*.rb)
    if have bundle; then
      ( cd "$PROJECT_DIR/$backend_rel" && run bundle exec rubocop -a "$sub" )
    elif container_up backends/ruby-rails; then
      in_container backends/ruby-rails sh -c "bundle exec rubocop -a '$sub' || true"
    else
      log "ruby-rails: bundle が見つからないためスキップ ($sub)"
    fi
    ;;

  # ---- frontend / e2e (prettier で整形 → eslint --fix で import 最適化) ----
  frontend/*|e2e/*)
    root="frontend"; case "$REL" in e2e/*) root="e2e" ;; esac
    ext="${REL##*.}"
    case "$ext" in
      js|jsx|ts|tsx|vue|mjs|cjs|json|css|scss|md|yml|yaml)
        if [ -d "$PROJECT_DIR/$root/node_modules" ] && have npx; then
          ( cd "$PROJECT_DIR/$root" && run npx --no-install prettier --write "$ABS" 2>/dev/null
            case "$ext" in js|jsx|ts|tsx|vue|mjs|cjs) run npx --no-install eslint --fix "$ABS" 2>/dev/null ;; esac )
        else
          log "$root: prettier/eslint 未導入(node_modules無)のためスキップ ($sub)"
        fi
        ;;
    esac
    ;;
esac

exit 0
