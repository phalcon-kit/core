# PhalconKit Environment And Deployment

Use this reference when adding or reviewing Docker, local development services,
PHP extension setup, web-server/PHP-FPM routing, WebSocket proxying, `.env`
files, or deployment-facing configuration for a PhalconKit application.

## Phalcon Baseline

Native Phalcon references:

- Installation: https://docs.phalcon.io/5.16/installation/
- Webserver setup: https://docs.phalcon.io/5.16/webserver-setup/
- Docker environment: https://docs.phalcon.io/5.16/environments-docker/
- CLI applications: https://docs.phalcon.io/5.16/cli/

PhalconKit app runtime still depends on normal Phalcon extension installation,
web-server rewrite rules, PHP-FPM configuration, and CLI execution. Use native
docs for base environment requirements and this file for PhalconKit Docker,
Swoole, Redis/Valkey, MySQL, proxy, and deployment examples.

## Local Stack Shape

A practical local PhalconKit stack often has five services:

- `valkey`: Redis-compatible cache/pub-sub/session service.
- `mysql`: MySQL 8.x database with a healthcheck.
- `php`: PHP-FPM app runtime for MVC/API requests and CLI commands.
- `swoole`: Swoole PHP runtime for WebSocket tasks.
- `apache`: example HTTP/HTTPS frontend that serves `public/`, proxies PHP to
  FPM, and proxies `/ws/` to Swoole. Nginx, Caddy, LiteSpeed, or another
  reverse proxy can fill the same role.

Keep persistent dev data under `docker/data/*` and mount application config
from `docker/.env` into `/app/.env`.

Recommended app-side files:

```text
docker-compose.yml
docker/
  .env
  Dockerfile
  apache/
    httpd.conf
    ssl/
      server.crt
      server.key
  nginx/
    site.conf
  php/
    php.ini
  data/
    mysql/
    valkey/
websocket
cli
index.php
loader.php
public/
  index.php
```

```yaml
services:
  valkey:
    image: docker.io/valkey/valkey:7.2-alpine
    volumes:
      - ./docker/data/valkey:/data

  mysql:
    image: docker.io/library/mysql:8.4
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: app
      MYSQL_USER: app
      MYSQL_PASSWORD: app
    ports:
      - "3308:3306"
    volumes:
      - ./docker/data/mysql:/var/lib/mysql
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "127.0.0.1", "-proot"]
      interval: 2s
      timeout: 3s
      retries: 30

  php:
    build:
      context: .
      dockerfile: docker/Dockerfile
      args:
        PHP_VARIANT: php:8.5-fpm
        DEV_MODE: 1
    volumes:
      - ./:/app
      - ./docker/.env:/app/.env
      - ./docker/php/php.ini:/usr/local/etc/php/conf.d/custom.ini
    working_dir: /app
    depends_on:
      mysql:
        condition: service_healthy
      valkey:
        condition: service_started

  swoole:
    build:
      context: .
      dockerfile: docker/Dockerfile
      args:
        PHP_VARIANT: docker.io/phpswoole/swoole:6.0.1-php8.5-dev
        DEV_MODE: 0
    command: ["php", "websocket"]
    ports:
      - "8081:8081"
    volumes:
      - ./:/app
      - ./docker/.env:/app/.env
      - ./docker/php/php.ini:/usr/local/etc/php/conf.d/custom.ini
    working_dir: /app
    depends_on:
      - mysql
      - valkey

  apache:
    image: docker.io/library/httpd:2.4
    ports:
      - "8080:80"
      - "8443:443"
    volumes:
      - ./:/app
      - ./docker/apache/httpd.conf:/usr/local/apache2/conf/httpd.conf
      - ./docker/apache/ssl:/usr/local/apache2/conf/ssl
    depends_on:
      - php
      - swoole
```

Use `docker-compose.yml` or `compose.yml` consistently. Avoid committing local
service-account JSON, generated TLS keys, or real `.env` secrets.

## Docker PHP Image

The app PHP image should install Composer, Phalcon, and the extensions required
by the configured providers.

Common extensions for a full PhalconKit app:

- `phalcon`
- `intl`
- `gettext`
- `apcu`
- `pdo_mysql`
- `mysqli`
- `sockets`
- `pcntl`
- `zip`
- `redis`
- `opcache`
- `xdebug` in development only

The image can use an overridable base so PHP-FPM and Swoole share most of the
same extension setup:

```dockerfile
ARG PHP_VARIANT=php:8.5-fpm
ARG COMPOSER_VARIANT=composer:2
ARG PHALCON_VERSION=5.16.0

FROM docker.io/library/${COMPOSER_VARIANT} AS composer
FROM ${PHP_VARIANT}

ENV DEBIAN_FRONTEND=noninteractive
ENV TERM=xterm-color

ARG DEV_MODE
ENV DEV_MODE=$DEV_MODE

COPY --from=composer /usr/bin/composer /usr/local/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_HOME=/tmp/composer

WORKDIR /app
```

Compile Phalcon from the matching cphalcon version when no prebuilt package is
available for the selected PHP image:

```dockerfile
RUN set -eux; \
    apt-get update; \
    apt-get install -y $PHPIZE_DEPS curl; \
    mkdir -p /tmp/phalcon-src; \
    cd /tmp/phalcon-src; \
    curl -sSL "https://github.com/phalcon/cphalcon/archive/v${PHALCON_VERSION}.tar.gz" | tar -xz --strip-components=1; \
    cd build/phalcon; \
    phpize; \
    export CFLAGS="${CFLAGS:-} -fpermissive"; \
    ./configure --enable-phalcon; \
    make; \
    make install; \
    echo "extension=phalcon.so" > /usr/local/etc/php/conf.d/docker-php-ext-phalcon.ini
```

Install runtime extensions in grouped layers:

```dockerfile
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends libicu-dev libzip-dev zlib1g-dev; \
    docker-php-ext-install -j"$(nproc)" intl gettext pdo_mysql mysqli sockets pcntl zip opcache; \
    pecl channel-update pecl.php.net; \
    yes '' | pecl install apcu; \
    docker-php-ext-enable apcu; \
    yes '' | pecl install --configureoptions 'enable-redis-igbinary="no" enable-redis-lzf="no" enable-redis-zstd="no"' redis; \
    docker-php-ext-enable redis; \
    rm -rf /var/lib/apt/lists/*
```

Development notes:

- Keep Xdebug installed but disabled by default unless the app needs it on
  every request.
- Use the Swoole base image for the `swoole` service instead of enabling
  Swoole in the PHP-FPM image.
- Keep `DEV_MODE` explicit so Dockerfile conditionals can avoid dev tooling in
  long-running worker images.
- Rebuild the image when `PHALCON_VERSION`, PHP minor version, or required
  extensions change.

## Web Server Frontend

PhalconKit does not require Apache. The web-server layer only needs to:

- serve the app's `public/` directory
- route non-file MVC requests to `public/index.php`
- pass PHP scripts to the correct PHP-FPM runtime
- proxy `/ws/` WebSocket traffic to the Swoole runtime when WebSockets are used
- terminate TLS or sit behind another TLS terminator

Use the server already standard for the app's hosting environment. The examples
below show Apache and Nginx because they are common local and production
frontends.

## Apache Example

Apache should serve only `public/`, proxy PHP scripts to `php:9000`, and proxy
WebSocket traffic to the Swoole service.

Core settings:

```apacheconf
ServerRoot "/usr/local/apache2"
ServerName localhost

Listen 80
Listen 443

DocumentRoot "/app/public"
DirectoryIndex index.php index.html

<Directory />
    AllowOverride None
    Require all denied
</Directory>

<Directory "/app/public">
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

Required modules include:

```apacheconf
LoadModule rewrite_module        modules/mod_rewrite.so
LoadModule headers_module        modules/mod_headers.so
LoadModule proxy_module          modules/mod_proxy.so
LoadModule proxy_http_module     modules/mod_proxy_http.so
LoadModule proxy_fcgi_module     modules/mod_proxy_fcgi.so
LoadModule proxy_wstunnel_module modules/mod_proxy_wstunnel.so
LoadModule ssl_module            modules/mod_ssl.so
```

Local HTTP-to-HTTPS redirect can preserve the path and force the exposed
development HTTPS port:

```apacheconf
<VirtualHost *:80>
    ServerName localhost
    RewriteEngine On
    RewriteCond %{HTTP_HOST} ^([^:]+)
    RewriteRule ^/(.*)$ https://%1:8443/$1 [R=301,L]
</VirtualHost>
```

HTTPS virtual host:

```apacheconf
<VirtualHost *:443>
    ServerName localhost

    SSLEngine on
    SSLCertificateFile    "/usr/local/apache2/conf/ssl/server.crt"
    SSLCertificateKeyFile "/usr/local/apache2/conf/ssl/server.key"

    DocumentRoot "/app/public"

    ProxyPassMatch "^/(.*\.php(/.*)?)$" "fcgi://php:9000/app/public/$1"

    ProxyPass        "/ws/" "ws://swoole:8081/" timeout=3600
    ProxyPassReverse "/ws/" "ws://swoole:8081/"

    ProxyPass        "/swoole/" "http://swoole:8081/"
    ProxyPassReverse "/swoole/" "http://swoole:8081/"

    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
</VirtualHost>
```

Apache rules:

- Keep `DocumentRoot` at `/app/public`, not `/app`.
- Enable `mod_proxy_wstunnel` for WebSocket proxying.
- Keep `/ws/` for WebSocket traffic and `/swoole/` only for optional Swoole HTTP
  endpoints.
- Keep generated local certificates out of reusable examples unless they are
  clearly dummy development certificates.
- Use production TLS, hostnames, redirects, and HSTS policy outside local dev.

## Nginx Example

Nginx should also serve only `public/`, rewrite non-file requests to
`index.php`, pass PHP to PHP-FPM, and proxy `/ws/` to Swoole.

```nginx
server {
    listen 80;
    server_name app.local www.app.local;

    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl;
    http2 on;
    server_name app.local www.app.local;

    ssl_certificate /etc/nginx/ssl/self-signed.crt;
    ssl_certificate_key /etc/nginx/ssl/self-signed.key;

    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers 'TLS_AES_128_GCM_SHA256:TLS_AES_256_GCM_SHA384:ECDHE-RSA-AES128-GCM-SHA256';
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 1d;
    ssl_session_tickets off;

    root /home/appuser/example.test/public/;
    index index.php index.html index.htm;

    location / {
        try_files $uri /index.php?_url=$uri&$args;
    }

    location ~ \.php$ {
        include fastcgi_params;

        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php-fpm/php-fpm.sock;

        fastcgi_param QUERY_STRING $query_string;
    }

    location ~ /\.ht {
        deny all;
    }

    location /ws/ {
        proxy_pass http://127.0.0.1:8081;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
    }
}
```

When Nginx runs on the host and PHP-FPM runs in a container or Podman-managed
runtime, point FastCGI at the exposed container socket and set `SCRIPT_FILENAME`
to the path as PHP sees it:

```nginx
location ~ \.php$ {
    include fastcgi_params;

    set $container_root /app/public;

    fastcgi_param DOCUMENT_ROOT $container_root;
    fastcgi_param SCRIPT_FILENAME $container_root$fastcgi_script_name;
    fastcgi_pass unix:/home/appuser/containers/php/sockets/php85/php-fpm.sock;

    fastcgi_param QUERY_STRING $query_string;
}
```

Nginx rules:

- Keep `root` pointed at the public directory.
- Keep `SCRIPT_FILENAME` aligned with the filesystem path visible to PHP-FPM.
  Host PHP-FPM usually uses `$document_root`; container PHP-FPM may need a
  container path such as `/app/public`.
- Preserve the Phalcon rewrite convention with `try_files`.
- Add WebSocket upgrade headers for `/ws/`.
- Deny hidden files and private app paths from the public server context.

## Environment Values

`docker/.env` should match service names from Compose:

```ini
DATABASE_HOST=mysql
DATABASE_PORT=3306
DATABASE_DBNAME=app
DATABASE_USERNAME=app
DATABASE_PASSWORD=app

REDIS_HOST=valkey
REDIS_PORT=6379
REDIS_DB=0

SWOOLE_HOST=0.0.0.0
SWOOLE_PORT=8081
SWOOLE_WORKER_NUM=4
SWOOLE_MAX_CONN=1000

ROUTER_DEFAULT_NAMESPACE=App\\Modules\\Frontend\\Controllers
ROUTER_DEFAULT_MODULE=frontend
ROUTER_WS_DEFAULT_NAMESPACE=App\\Modules\\Ws\\Tasks
ROUTER_WS_DEFAULT_MODULE=ws
```

Secret handling:

- Commit `.env.example`, not real `.env`.
- Mount Firebase or other service-account JSON from a private location.
- Keep API keys, JWT secrets, mail credentials, OAuth secrets, and database
  passwords out of prompts, logs, docs, and committed Compose files.
- If an example needs a password, use obviously local credentials like
  `app/app` and label them as development-only.

## Runtime Commands

Common development commands:

```bash
docker compose up -d mysql valkey php swoole apache
docker compose exec php composer install
docker compose exec php ./vendor/bin/phalcon-kit cli scaffold run --src-dir=app/ --namespace=App --models-extend=\\App\\Models\\AbstractModel --force --no-models
docker compose exec php ./vendor/bin/phalcon migration run --config=./devtools.php --directory=./ --migrations=./resources/migrations/ --no-auto-increment --force --verbose --log-in-db
docker compose logs -f swoole
```

Use `docker compose exec php` for migrations, scaffold generation, tests, and
one-shot CLI tasks. Use the `swoole` service for the long-running WebSocket
runtime.

For local Podman debugging, run the same `websocket` bootstrap entrypoint in a
container. Host networking is convenient on a trusted development machine; for
safer local binding, publish only `127.0.0.1:8081`.

```bash
podman run -it --init --rm \
  -v /home/me/Projects:/app \
  --network="host" \
  localhost/php-app:8.5 \
  php /app/my-app/websocket
```

```bash
podman run -it --init --rm \
  -v /home/me/Projects:/app \
  -p 127.0.0.1:8081:8081 \
  localhost/php-app:8.5 \
  php /app/my-app/websocket
```

For staging or production without a container orchestrator, run the WebSocket
entrypoint under a process manager such as systemd. The unit belongs outside
the public web root and should use the host's approved PHP binary or wrapper.

```ini
[Unit]
Description=PHP Swoole WebSocket Server
After=network.target

[Service]
User=appuser
Group=appuser
ExecStart=/opt/alt/php85/usr/bin/php /home/appuser/example.test/websocket
KillSignal=SIGINT
Restart=always
RestartSec=3
LimitNOFILE=65535
NoNewPrivileges=true
PrivateTmp=true
ProtectSystem=full
ProtectHome=true
StandardOutput=append:/home/appuser/example.test/storage/logs/websocket.out.log
StandardError=append:/home/appuser/example.test/storage/logs/websocket.err.log

[Install]
WantedBy=multi-user.target
```

On CloudLinux/CageFS hosts, use the host-provided wrapper when required:

```ini
ExecStart=/usr/bin/lve_suwrapper 1006 /opt/alt/php85/usr/bin/php /home/appuser/example.test/websocket
```

## Deployment Checklist

When an agent changes environment/deployment files:

1. Confirm whether the target is local dev, staging, or production.
2. Keep public web roots pointed at `public/`.
3. Match config keys to PhalconKit providers: `database`, `redis`, `swoole`,
   `session`, `cache`, `mailer`, `oauth2`, `openai`, and custom provider keys.
4. Keep PHP-FPM and Swoole runtime concerns separate.
5. Verify the chosen web server proxies WebSocket upgrade traffic correctly.
6. Avoid exposing Redis/Valkey and MySQL ports unless local development needs
   host access.
7. Supervise WebSocket runtimes with Docker Compose, systemd, or another
   process manager; do not rely on an interactive shell for staging or
   production.
8. Do not run destructive migrations, dependency upgrades, or deployments
   without explicit approval.
