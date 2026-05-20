# Web Server And WebSocket

PhalconKit applications can run behind any web server that can serve the
`public/` directory and forward PHP requests to PHP-FPM. Apache is not required;
Nginx, Caddy, containerized proxies, or platform web servers are also valid.

## Built-In PHP Server

Use PHP's built-in server only for local development and controlled demos:

```shell
php -S 127.0.0.1:8000 -t public public/index.php
```

It is single-process and not suitable for production.

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
