-- 1. テスト用データベースを作成（存在しない場合のみ）
CREATE DATABASE IF NOT EXISTS `authorization_test`;

-- 2. 既存の 'develop' ユーザーにテスト用DBの全権限を付与
-- ホスト '%'(ワイルドカード) を指定して、コンテナ内外からのアクセスを許可します
GRANT ALL PRIVILEGES ON `authorization_test`.* TO 'develop'@'%';

-- 3. 権限設定を即座に反映
FLUSH PRIVILEGES;