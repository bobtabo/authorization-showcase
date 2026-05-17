FROM amazoncorretto:latest

# ライブラリのインストール
RUN apt-get update && apt-get install -y \
      sudo \
      git \
      vim \
      locales \
      iputils-ping \
      net-tools \
      gosu

# ユーザーを作成
ARG DOCKER_UID=1000
ARG DOCKER_USER=docker
ARG DOCKER_PASSWORD=docker
RUN useradd -m --uid ${DOCKER_UID} --non-unique --groups sudo ${DOCKER_USER} \
      && echo ${DOCKER_USER}:${DOCKER_PASSWORD} | chpasswd \
      && echo "docker    ALL=(ALL)       ALL" >> /etc/sudoers

# ロケール
RUN localedef -f UTF-8 -i ja_JP ja_JP.UTF-8
ENV LANG="ja_JP.UTF-8" \
    LANGUAGE="ja_JP:ja" \
    LC_ALL="ja_JP.UTF-8"

WORKDIR /var/www

# amazoncorretto:latest は USER gradle に切り替えるため、エントリーポイントで
# root として権限修正してから gradle ユーザーに降格する
USER root
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["docker-entrypoint.sh"]
