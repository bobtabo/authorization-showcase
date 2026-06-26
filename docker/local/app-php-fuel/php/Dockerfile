FROM php:7.4-fpm

# ライブラリのインストール
RUN apt-get update
RUN apt-get install -y \
      sudo \
      curl \
      libpq-dev \
      libonig-dev \
      git \
      zip \
      unzip \
      vim \
      locales \
      iputils-ping \
      net-tools \
      libssl-dev \
      openssl \
      ssl-cert
RUN docker-php-ext-install \
      fileinfo \
      mbstring \
      pdo \
      bcmath \
      pdo_mysql

# Xdebugのインストール（PHP 7.4 対応の最終バージョン）
RUN apt-get install -y $PHPIZE_DEPS \
    && pecl install xdebug-3.1.6 \
    && docker-php-ext-enable xdebug \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS

# Composerのインストール
COPY --from=composer /usr/bin/composer /usr/bin/composer

# ユーザーを作成
ARG DOCKER_UID=1000
ARG DOCKER_USER=docker
ARG DOCKER_PASSWORD=docker
RUN useradd -m --uid ${DOCKER_UID} --groups sudo ${DOCKER_USER} \
      && echo ${DOCKER_USER}:${DOCKER_PASSWORD} | chpasswd \
      && echo "docker    ALL=(ALL)       ALL" >> /etc/sudoers

# ロケール
RUN localedef -f UTF-8 -i ja_JP ja_JP.UTF-8
ENV LANG="ja_JP.UTF-8" \
    LANGUAGE="ja_JP:ja" \
    LC_ALL="ja_JP.UTF-8"

WORKDIR /var/www
