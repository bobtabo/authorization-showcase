FROM amazoncorretto:21-al2023

# ライブラリのインストール
RUN dnf install -y \
      sudo \
      git \
      vim \
      glibc-langpack-ja \
      iputils \
      net-tools \
      shadow-utils \
      tar \
      gzip \
    && dnf clean all

# gosu のインストール（Amazon Linux リポジトリに含まれないため GitHub から取得）
RUN ARCH=$(uname -m | sed 's/x86_64/amd64/;s/aarch64/arm64/') \
    && curl -sSLo /usr/local/bin/gosu \
       "https://github.com/tianon/gosu/releases/download/1.17/gosu-${ARCH}" \
    && chmod +x /usr/local/bin/gosu \
    && gosu nobody true

# ユーザーを作成
ARG DOCKER_UID=1000
ARG DOCKER_USER=docker
ARG DOCKER_PASSWORD=docker
RUN useradd -m --uid ${DOCKER_UID} --non-unique ${DOCKER_USER} \
      && echo "${DOCKER_USER}:${DOCKER_PASSWORD}" | chpasswd \
      && echo "${DOCKER_USER} ALL=(ALL) NOPASSWD:ALL" >> /etc/sudoers

# ロケール
ENV LANG="ja_JP.UTF-8" \
    LANGUAGE="ja_JP:ja" \
    LC_ALL="ja_JP.UTF-8"

WORKDIR /var/www

USER root
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["docker-entrypoint.sh"]
