# Web Servers And WebSockets

Phalcon Kit applications can run behind any web server that can serve the
`public/` directory and forward PHP requests to PHP-FPM. Apache is not required;
Nginx, Caddy, containerized proxies, or platform web servers are also valid.

Official Phalcon references:

- Web server setup: https://docs.phalcon.io/latest/webserver-setup/
- CLI applications: https://docs.phalcon.io/latest/cli/
- Dependency injection: https://docs.phalcon.io/latest/di/

## Built-In PHP Server

Use PHP's built-in server only for local development and controlled demos:

```shell
php -S 127.0.0.1:8080 -t public public/index.php
```

It is single-process and not suitable for production.

## Web Root Rules

- Point the web server document root at `public/`.
- Keep `.env`, `vendor/`, `src/`, `resources/`, and generated files outside the
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

if (!extension_loaded('swoole')) {
    fwrite(STDERR, "The optional Swoole extension is required to run bin/websocket.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/bootstrap.php';

echo (new Bootstrap(Bootstrap::MODE_WS))->run();
```

Run it with PHP directly, a container command, or a process supervisor:

```shell
./bin/websocket
```

For local container testing, the worker can run with host networking or a
published port. For production, use a process supervisor rather than manually
running the command in a shell.

## Try The Starter Protocol

With `./bin/websocket` running on the default loopback port 8081, run this in a
local browser console on an HTTP page (or use your WebSocket client):

```javascript
const socket = new WebSocket('ws://127.0.0.1:8081');
socket.addEventListener('open', () => socket.send(JSON.stringify({type: 'ping'})));
socket.addEventListener('message', event => console.log(JSON.parse(event.data)));
```

Expected message: `{"type":"pong"}`. An HTTPS page needs a secure `wss://`
endpoint through your TLS proxy.

| Sent frame | Starter response |
| --- | --- |
| `{"type":"ping"}` | `{"type":"pong"}` |
| Malformed JSON | `{"type":"error","message":"Invalid JSON"}` |
| `{}` or a non-string type | `{"type":"error","message":"A string message type is required"}` |
| `{"type":"subscribe"}` or another unknown type | `{"type":"error","message":"Unsupported message type"}` |

The starter implements this small protocol only. HTTP authentication does not
automatically authenticate a WebSocket connection, and the `ws` ACL role only
permits the worker task to run.

## Application Subscriptions

For a live project dashboard, implement the following protocol in your
application's `MainTask` and services. **These message types are an application
design example, not additional built-in starter actions.**

1. Authenticate the connection with a short-lived, single-use ticket obtained
   from an authenticated HTTPS endpoint, or an appropriate secure session
   handshake. Bind the verified identity to this connection. Browser WebSocket
   constructors cannot set an arbitrary bearer header.
2. Accept a subscribe request only after checking that identity may view the
   requested project. Derive its tenant and channel server-side.
3. Return an acknowledgement and an authorized snapshot with a version/cursor.
4. Publish changes from trusted application code after its database transaction
   commits; clients cannot broadcast arbitrary messages to other subscribers.
5. Remove subscriptions/identity when the connection closes or authentication
   expires. Re-check authorization when membership changes.

Example exchange after authentication:

```json
{"type":"subscribe","requestId":"req-1","resource":"project","id":42}
```

Application acknowledgement:

```json
{"type":"subscribed","requestId":"req-1","resource":"project","id":42,"cursor":108}
```

Application snapshot:

```json
{"type":"snapshot","resource":"project","id":42,"cursor":108,"data":{"label":"Community garden","status":"active"}}
```

A trusted server-side change later produces:

```json
{"type":"changed","resource":"project","id":42,"cursor":109,"data":{"status":"archived"}}
```

Denied subscription:

```json
{"type":"error","requestId":"req-1","code":"forbidden","message":"Subscription is not available."}
```

An unsubscribe request should be acknowledged and stop future delivery for that
connection. Bound message sizes, subscription counts, idle times, and queued
outbound data. Reject malformed IDs and unknown types before loading resources.
Do not echo credentials or private exception diagnostics to clients.

### Reconnect And Synchronize

A disconnected browser should reconnect with bounded exponential backoff and
jitter, obtain fresh authentication when needed, then subscribe again. Fetch a
new snapshot unless your application implements a durable replay log and verifies
the supplied cursor. Deduplicate events by resource/cursor and discard updates
older than the applied snapshot. A TCP/WebSocket reconnection alone does not
recover missed changes.

### Multiple Workers

PHP arrays in one Swoole worker are not a shared subscription registry. Track
connections locally, and use a trusted shared broker/event stream plus worker
fan-out for updates that must reach other workers or hosts. Redis Pub/Sub is one
possible live distribution mechanism; it does not provide durable replay by
itself. Keep replay/snapshot recovery an explicit application decision.

The application must implement ticket validation, permission lookup, subscription
storage, publication, and replay/snapshot services. Reuse Core's bootstrap,
models, and authorization rules inside those services, while clearing identity
and model authorization caches between logical messages. Test two users in
different projects and delivery across workers before deployment.

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
ExecStart=/usr/bin/php /var/www/app/bin/websocket
KillSignal=SIGINT
Restart=always
RestartSec=3
LimitNOFILE=65535
StandardOutput=append:/var/www/app/storage/log/websocket.out.log
StandardError=append:/var/www/app/storage/log/websocket.err.log

[Install]
WantedBy=multi-user.target
```

## Deployment Verification

After deploying or changing the proxy:

1. Request a static asset and confirm PHP is not invoked.
2. Request an application route and confirm it reaches the current release.
3. Verify HTTPS scheme and host detection behind the proxy.
4. Confirm `.env`, `vendor/`, logs, and source files are not publicly served.
5. Open a WebSocket connection and keep it alive through the proxy timeout.
6. Restart the worker and confirm the supervisor brings it back.
7. Check that HTTP, CLI, and WebSocket processes load the same intended config.

```shell
curl --include https://app.example.test/api
curl --include https://app.example.test/assets/app.css
```

Use [Troubleshooting](troubleshooting.md) when only one runtime mode fails, and
[Runtime Compatibility](phalcon-runtime-upgrades.md) when PHP or Phalcon differs
between processes.

## WebSocket Event Hooks

`PhalconKit\Modules\Ws\Tasks\AbstractTask` resolves the shared `swoole`
service and registers positional callbacks. Keep Swoole's `event_object` setting
disabled for this task. Override the `on*()` methods to implement application
behavior; call `parent::initialize()` when customizing task initialization.

The worker-error adapter receives Swoole's server, worker ID, worker PID, exit
code, and signal. For compatibility, the existing four-argument
`onWorkerError($server, $fd, $code, $reason)` hook remains: `$fd` means worker ID,
`$code` is the exit code, and `$reason` contains `pid=<pid>, signal=<signal>`.
Applications overriding that hook keep the same signature.

The static subscription map is local to each worker process. Application hooks
must remove closed client subscriptions and coordinate cross-worker delivery
when needed. Request, frame, open, close, and pipe-message callbacks reset Core's
model connection state before calling the application hook.
