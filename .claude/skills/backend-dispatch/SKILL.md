---
name: backend-dispatch
description: >-
  「Goのバックエンドで◯◯を直して」のように言語名だけで指示された際に、正しい
  backends/ 配下のディレクトリと docker/bin/docker-*.sh のラッパースクリプトに
  振り分ける際に使う。曖昧なPHP指示（CakePHP/CodeIgniter/FuelPHPのどれか不明）の
  ときにも使う。
allowed-tools: Bash(docker:*), Bash(git:*)
---

# backend-dispatch

このリポジトリは同一の認可サーバーAPI（JWT発行／検証）を7言語・FWで実装するショーケース。
どのバックエンドを指しているかを機械的に確定させ、実行は必ずDockerラッパースクリプト
経由で行う。

## 対応表

| 呼ばれ方の例 | ソースディレクトリ | Docker操作 | execで入るシェル |
|---|---|---|---|
| Go, Gin | `backends/go-gin/` | `docker/bin/docker-go.sh` | `sh` |
| Java, Spring Boot | `backends/java-springboot/` | `docker/bin/docker-java.sh` | `bash` |
| CakePHP, Cake | `backends/php-cakephp/` | `docker/bin/docker-php-cake.sh` | `bash` |
| CodeIgniter, CI | `backends/php-codeigniter/` | `docker/bin/docker-php-codeigniter.sh` | `bash` |
| FuelPHP, Fuel | `backends/php-fuelphp/` | `docker/bin/docker-php-fuel.sh` | `bash` |
| Python, Django | `backends/python-django/` | `docker/bin/docker-python.sh` | `bash` |
| Ruby, Rails | `backends/ruby-rails/` | `docker/bin/docker-ruby.sh` | `bash` |

- 「PHPのバックエンドで」のように3フレームワークのどれか特定できない指示を受けた場合は、
  推測で決めずに CakePHP / CodeIgniter / FuelPHP のどれかをユーザーに確認する。
- 「全バックエンドで」「7言語すべてで」という指示は、上記7ディレクトリ全てに同じ変更を
  展開する。一括Docker操作は `docker/bin/docker-backends.sh {up|down}` を使う
  （docker-ops Skill参照）。

## 実行方針

- **ソース編集**: `backends/<dir>/` はDockerコンテナへbind mountされているため、
  ホスト側で直接編集してよい（Edit/Writeツールで通常通り編集する）。
- **テスト・ビルド・CLI実行**: 必ずコンテナ内で行う。ホストに言語ランタイムが
  入っていても、ホストで直接 `go test` 等を実行しない。
  ```bash
  X=go   # 対応表の値に置き換える: go/java/php-cake/php-codeigniter/php-fuel/python/ruby
  "docker/bin/docker-$X.sh" exec
  # コンテナ内シェルに入った状態で、通常のテスト/ビルドコマンドを実行する
  ```
  非対話的に1コマンドだけ実行したい場合は、対応する `docker/local/app-<x>/docker-compose.yml`
  を直接指定する（`docker exec <コンテナ名>` のようにコンテナ名を直書きしない。
  プロジェクト名・サービス名がバックエンドごとに異なるため取り違えやすい）。
  `CMD` は文字列ではなく配列にし、`"${CMD[@]}"` で展開する（文字列のまま
  `$CMD` と書くと、引数中のクォートや `*` がホスト側のシェルで単語分割・
  パス展開されてしまい、意図しない引数がコンテナに渡る）:
  ```bash
  X=go            # 対応表の値に置き換える
  SERVICE=go      # execで指定するサービス名: go=go, java=java, php系=php, python=python, ruby=rb-rails
  CMD=(go test ./...)   # 実行したいコマンド（配列で書く）

  docker compose -p "showcase-$X" -f "docker/local/app-$X/docker-compose.yml" \
    exec -T --user 1000 "$SERVICE" "${CMD[@]}"
  ```
  `-T` は必須（付けないとTTY割り当てを試みて、非対話環境では失敗しうる）。
- コンテナが起動していない場合の対処は docker-ops Skillを参照。
