FROM php:8.3-apache-bookworm

# Instalar MariaDB e extensão MySQL do PHP
RUN apt-get update \
    && DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends mariadb-server \
    && docker-php-ext-install pdo_mysql \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Configurar Apache para a porta 10000 do Render
RUN sed -i 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:10000>/' /etc/apache2/sites-available/000-default.conf

# Configurar MariaDB para aceitar conexões locais
RUN printf '%s\n' \
    '[mysqld]' \
    'bind-address = 127.0.0.1' \
    > /etc/mysql/mariadb.conf.d/99-demo-local.cnf

# Copiar o projeto
COPY . /var/www/html/

# Preparar scripts e banco de dados
RUN mkdir -p /opt/bootstrap \
    && mv /var/www/html/start.sh /opt/bootstrap/start.sh \
    && cp /var/www/html/banco.sql /opt/bootstrap/banco.sql \
    && chmod +x /opt/bootstrap/start.sh

# Bloquear acesso público a arquivos internos
RUN printf '%s\n' \
    '<FilesMatch "^(banco\.sql|Dockerfile|compose\.yaml|INSTRUCOES-RENDER\.md|\.env.*)$">' \
    '    Require all denied' \
    '</FilesMatch>' \
    > /etc/apache2/conf-available/block-internal.conf \
    && a2enconf block-internal

# Configurar permissões
RUN chown -R www-data:www-data /var/www/html

# Verificar a sintaxe do Apache durante o build
RUN apache2ctl configtest

# Porta do Render
EXPOSE 10000

# Inicializar PHP, Apache e MariaDB
CMD ["/opt/bootstrap/start.sh"]