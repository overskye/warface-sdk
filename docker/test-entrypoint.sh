#!/bin/sh
set -eu

composer install --no-interaction --prefer-dist --no-progress
exec "$@"
