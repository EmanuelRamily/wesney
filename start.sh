#!/bin/sh
set -eu

# No plano gratuito do Render, o banco é recriado quando o contêiner reinicia.
DB_PASSWORD="${DB_PASSWORD:-demo-local-apenas}"

# A senha não pode conter aspas simples ou barras invertidas para este bootstrap.
case "$DB_PASSWORD" in
  *"'"*|*"\\"*) echo 'DB_PASSWORD contém caracteres não suportados.' >&2; exit 1;;
esac

mkdir -p /run/mysqld
chown mysql:mysql /run/mysqld

# Inicializa os arquivos de dados se necessário.
if [ ! -d /var/lib/mysql/mysql ]; then
  mariadb-install-db --user=mysql --datadir=/var/lib/mysql --skip-test-db
fi

mysqld --user=mysql --datadir=/var/lib/mysql --bind-address=127.0.0.1 &
MYSQL_PID=$!

ready=0
for i in $(seq 1 60); do
  if mariadb-admin ping --silent >/dev/null 2>&1; then ready=1; break; fi
  sleep 1
done
if [ "$ready" -ne 1 ]; then echo 'Banco MySQL não iniciou' >&2; exit 1; fi

mariadb -uroot <<SQL
CREATE DATABASE IF NOT EXISTS autopecas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'autopecas_app'@'127.0.0.1' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON autopecas.* TO 'autopecas_app'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL

mariadb -uroot < /opt/bootstrap/banco.sql

echo 'PHP + MariaDB inicializados. Banco de demonstração pronto.'
exec apache2-foreground
