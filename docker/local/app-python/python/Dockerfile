FROM python:3.13

# ライブラリのインストール
RUN apt-get update && apt-get install -y \
      sudo \
      git \
      vim \
      locales \
      iputils-ping \
      net-tools

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

# pip を最新化
RUN pip install --upgrade pip
