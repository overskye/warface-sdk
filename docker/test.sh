#!/bin/sh
set -eu

export LOCAL_UID="$(id -u)"
export LOCAL_GID="$(id -g)"

exec docker compose run --build --rm tests "$@"
