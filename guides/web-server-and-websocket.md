# Web Server And WebSocket

PhalconKit applications can run behind any web server that can serve the
`public/` directory and forward PHP requests to PHP-FPM. Apache is not required;
Nginx, Caddy, containerized proxies, or platform web servers are also valid.

Official Phalcon references:

- Web server setup: https://docs.phalcon.io/5.17/webserver-setup/
- CLI applications: https://docs.phalcon.io/5.17/cli/
- Dependency injection: https://docs.phalcon.io/5.17/di/

## Built-In PHP Server

Use PHP's built-in server only for local development and controlled demos:

```shell
php -S 127.0.0.1:8000 -t public public/index.php
```

It is single-process and not suitable for production.

## Web Root Rules

- Point the web server document root at `public/`.
- Keep `.env`, `vendor/`, `app/`, `resources/`, and generated files outside the
  public document root.
- Forward missing files to `public/index.php`.
- Preserve the query string when rewriting.
- Terminate TLS at the web server or proxy and pass the expected HTTPS headers
  if the app depends on secure URL generation.

## Apache PHP-FPM Example

```apacheconf
<VirtualHost *:80>
    ServerName app.local
    DocumentRoot /path/to/app/public

    <Directory /path/to/app/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    <FilesMatch \.(php|phar)$>
        SetHandler "proxy:unix:/run/php/php-fpm.sock|fcgi://localhost"
    </FilesMatch>
</VirtualHost>
```

Apache can also proxy WebSocket traffic when the proxy modules are enabled. Use
Apache as one deployment option, not as a framework requirement.

## Nginx PHP-FPM Example

```nginx
server {
    listen 80;
    server_name app.local;
    root /path/to/app/public;
    index index.php index.html;

    location / {
        try_files $uri /index.php?_url=$uri&$args;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php-fpm.sock;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

For containerized PHP-FPM, keep host and container paths aligned with
`SCRIPT_FILENAME`. When the PHP worker sees a different root path than Nginx,
set `DOCUMENT_ROOT` and `SCRIPT_FILENAME` to the PHP container path.

## WebSocket Task

WebSocket entrypoints normally bootstrap the app in `ws` mode:

```php
#!/usr/bin/env php
<?php

use App\Bootstrap;

require 'loader.php';

echo (new Bootstrap('ws'))->run();
```

Run it with PHP directly, a container command, or a process supervisor:

```shell
php websocket
```

For local container testing, the worker can run with host networking or a
published port. For production, use a process supervisor rather than manually
running the command in a shell.

## WebSocket Proxying

Proxy WebSocket traffic to the Swoole worker. For Nginx:

```nginx
location /ws/ {
    proxy_pass http://127.0.0.1:8081;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";
    proxy_set_header Host $host;
}
```

For Apache:

```apacheconf
ProxyPass        "/ws/" "ws://swoole:8081/" timeout=3600
ProxyPassReverse "/ws/" "ws://swoole:8081/"
```

In production, run the WebSocket worker under systemd, Supervisor, a container
orchestrator, or the platform process manager. Log stdout/stderr and configure
a restart policy.

## systemd Example

```ini
[Unit]
Description=PHP Swoole WebSocket Server
After=network.target

[Service]
User=app
Group=app
WorkingDirectory=/var/www/app
ExecStart=/usr/bin/php /var/www/app/websocket
KillSignal=SIGINT
Restart=always
RestartSec=3
LimitNOFILE=65535
StandardOutput=append:/var/www/app/storage/logs/websocket.out.log
StandardError=append:/var/www/app/storage/logs/websocket.err.log

[Install]
WantedBy=multi-user.target
```
