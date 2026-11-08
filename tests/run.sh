#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

sudo apt-get update
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y docker.io mariadb-server apparmor curl gnupg ca-certificates

sudo docker pull php@sha256:6ce95208609dc66df163ab936c970b3b34cd901b85c747102c5999f08ade9143

RUNTIME=$(mktemp -d)
GNUPGHOME=$(mktemp -d)
export GNUPGHOME
chmod 700 "$GNUPGHOME"
trap 'if [ -f "$RUNTIME/mysqld.pid" ]; then kill "$(cat "$RUNTIME/mysqld.pid")" 2>/dev/null || true; fi; rm -rf "$RUNTIME" "$GNUPGHOME"' EXIT

curl -fsSL -o "$RUNTIME/phpunit-4.8.36.phar" https://phar.phpunit.de/phpunit-4.8.36.phar
curl -fsSL -o "$RUNTIME/phpunit-4.8.36.phar.asc" https://phar.phpunit.de/phpunit-4.8.36.phar.asc
echo "ab8bc3017a64cf75a2112f5e089a1d5b0c4cc2116556d60c1af5e3f584e85c5e  $RUNTIME/phpunit-4.8.36.phar" | sha256sum -c -
gpg --batch --keyserver hkps://keyserver.ubuntu.com --recv-keys D8406D0D82947747293778314AA394086372C20A
gpg --batch --status-fd 1 --verify "$RUNTIME/phpunit-4.8.36.phar.asc" "$RUNTIME/phpunit-4.8.36.phar" \
  | awk '$1=="[GNUPG:]" && $2=="VALIDSIG" && $NF=="D8406D0D82947747293778314AA394086372C20A" { ok=1 } END { exit ok?0:1 }'

mkdir -p "$RUNTIME/data"
if command -v mariadb-install-db >/dev/null 2>&1; then
  mariadb-install-db --datadir="$RUNTIME/data" --auth-root-authentication-method=normal
elif command -v mysql_install_db >/dev/null 2>&1; then
  mysql_install_db --datadir="$RUNTIME/data"
else
  echo "No mariadb-install-db or mysql_install_db found" >&2
  exit 1
fi

if command -v aa-exec >/dev/null 2>&1 && aa-status 2>/dev/null | grep -q 'mariadbd.*enforce'; then
  aa-exec -p unconfined -- mariadbd --no-defaults --datadir="$RUNTIME/data" --socket="$RUNTIME/mysqld.sock" \
    --pid-file="$RUNTIME/mysqld.pid" --skip-networking --skip-grant-tables \
    --character-set-server=utf8mb4 --collation-server=utf8mb4_unicode_ci &
else
  mariadbd --no-defaults --datadir="$RUNTIME/data" --socket="$RUNTIME/mysqld.sock" \
    --pid-file="$RUNTIME/mysqld.pid" --skip-networking --skip-grant-tables \
    --character-set-server=utf8mb4 --collation-server=utf8mb4_unicode_ci &
fi

mysqladmin --protocol=socket --socket="$RUNTIME/mysqld.sock" --wait=30 ping
mysql --protocol=socket --socket="$RUNTIME/mysqld.sock" \
  -e "CREATE DATABASE IF NOT EXISTS statsstock CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

sudo docker build -t statsstock-php56:local -f tests/docker/php56.Dockerfile .

export STATSSTOCK_RUNTIME="$RUNTIME"
export STATSSTOCK_PHPUNIT_PHAR="$RUNTIME/phpunit-4.8.36.phar"

sudo docker run --rm --network none \
  -v "$PWD":/src -v "$RUNTIME":/runtime -w /src \
  -e STATSSTOCK_MYSQL_SOCKET=/runtime/mysqld.sock \
  -e STATSSTOCK_RUNTIME=/runtime \
  -e STATSSTOCK_PHPUNIT_PHAR=/runtime/phpunit-4.8.36.phar \
  statsstock-php56:local \
  php -d error_reporting=-1 -d display_errors=1 -d display_startup_errors=1 -d html_errors=0 \
      /src/tests/run-suite.php
