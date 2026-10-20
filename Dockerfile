# syntax=docker/dockerfile:1

FROM composer:2.10.3 AS composer

FROM php:8.3.33-cli-bookworm AS test-runner

WORKDIR /app

COPY --link --from=composer /usr/bin/composer /usr/local/bin/composer

RUN --mount=type=cache,target=/var/cache/apt,sharing=locked \
    --mount=type=cache,target=/var/lib/apt,sharing=locked \
    set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends unzip; \
    groupadd --gid 10001 test-runner; \
    useradd --uid 10001 --gid test-runner --create-home --shell /usr/sbin/nologin test-runner; \
    mkdir -p /app/vendor /tmp/composer-home /tmp/composer-cache; \
    chmod 0777 /app/vendor /tmp/composer-home /tmp/composer-cache

COPY --link docker/test-entrypoint.sh /usr/local/bin/test-entrypoint

ENV HOME=/tmp/composer-home \
    COMPOSER_HOME=/tmp/composer-home \
    COMPOSER_CACHE_DIR=/tmp/composer-cache

USER 10001:10001

ENTRYPOINT ["sh", "/usr/local/bin/test-entrypoint"]
CMD ["composer", "test"]
