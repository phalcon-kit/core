# PhalconKit CLI And WebSocket Recipes

Use this reference when adding or reviewing CLI tasks, WebSocket tasks, Swoole
runtime code, Redis pub/sub bridges, or module error wrappers in a PhalconKit
application.

## Phalcon Baseline

Native Phalcon references:

- CLI applications: https://docs.phalcon.io/5.13/cli/
- Dependency injection: https://docs.phalcon.io/5.13/di/
- Dispatcher API: https://docs.phalcon.io/5.13/api/phalcon_mvc/#mvcdispatcher
- Events manager: https://docs.phalcon.io/5.13/events/

PhalconKit CLI and WebSocket modules reuse native Phalcon CLI, DI, dispatcher,
and event concepts. Use this file for app task wrappers, Swoole runtime,
Redis pub/sub, and WebSocket channel recipes.

## CLI Task Wrappers

Apps usually keep thin task wrappers so application code can extend them later
without changing every task.

```php
namespace App\Modules\Cli\Tasks;

class AbstractTask extends \PhalconKit\Modules\Cli\Tasks\AbstractTask
{
}
```

Keep the app error task wrapper too. It preserves the app namespace for router
defaults and future error customization.

```php
namespace App\Modules\Cli\Tasks;

class ErrorTask extends \PhalconKit\Modules\Cli\Tasks\ErrorTask
{
}
```

The module wrapper should add shared app namespaces while preserving the parent
module namespaces:

```php
namespace App\Modules\Cli;

class Module extends \PhalconKit\Modules\Cli\Module
{
    final public function getNamespaces(): array
    {
        return array_merge([
            'App\\Models' => APP_PATH . '/Models/',
        ], parent::getNamespaces());
    }
}
```

Register CLI tasks in root config permissions under the `cli` role:

```php
'permissions' => [
    'roles' => [
        'cli' => [
            'components' => [
                \App\Modules\Cli\Tasks\CronTask::class => ['*'],
                \App\Modules\Cli\Tasks\DatabaseTask::class => ['*'],
            ],
        ],
    ],
],
```

CLI tasks inherit Docopt parsing from `PhalconKit\Modules\Cli\Task`. Returned
values are rendered by `afterExecuteRoute()` using the requested output format,
defaulting to pretty JSON. Keep task actions focused and return arrays, strings,
or scalar status payloads instead of echoing everywhere.

CLI entrypoints should reuse the shared app loader and run bootstrap in `cli`
mode:

```php
#!/usr/bin/env php
<?php
use App\Bootstrap;

$loader = require 'loader.php';
echo new Bootstrap('cli')->run();
```

## WebSocket Task Wrappers

WebSocket modules follow the same wrapper pattern.

```php
namespace App\Modules\Ws\Tasks;

abstract class AbstractTask extends \PhalconKit\Modules\Ws\Tasks\AbstractTask
{
}
```

```php
namespace App\Modules\Ws\Tasks;

class ErrorTask extends \PhalconKit\Modules\Ws\Tasks\ErrorTask
{
}
```

The module wrapper should keep parent namespaces and add shared app models:

```php
namespace App\Modules\Ws;

class Module extends \PhalconKit\Modules\Ws\Module
{
    final public function getNamespaces(): array
    {
        return array_merge([
            'App\\Models' => APP_PATH . '/Models/',
        ], parent::getNamespaces());
    }
}
```

Root config should register the module, router namespace, Swoole settings, and
permissions:

```php
'modules' => [
    \App\Modules\Ws\Module::NAME_WS => [
        'className' => \App\Modules\Ws\Module::class,
        'path' => APP_PATH . '/Modules/Ws/Module.php',
    ],
],
'router' => [
    'ws' => [
        'namespace' => 'App\\Modules\\Ws\\Tasks',
        'module' => \App\Modules\Ws\Module::NAME_WS,
        'task' => 'main',
        'action' => 'listen',
    ],
],
'swoole' => [
    'host' => \PhalconKit\Support\Env::get('SWOOLE_HOST', '0.0.0.0'),
    'port' => \PhalconKit\Support\Env::get('SWOOLE_PORT', '8081'),
    'settings' => [
        'worker_num' => \PhalconKit\Support\Env::get('SWOOLE_WORKER_NUM', 4),
        'max_conn' => \PhalconKit\Support\Env::get('SWOOLE_MAX_CONN', 1000),
        'heartbeat_check_interval' => 60,
        'heartbeat_idle_time' => 120,
    ],
],
'permissions' => [
    'roles' => [
        'ws' => [
            'components' => [
                \App\Modules\Ws\Tasks\MainTask::class => ['*'],
            ],
        ],
    ],
],
```

For Docker Compose, reverse-proxy WebSocket setup, and Swoole service setup,
see `environment.md`.

The WebSocket entrypoint should reuse the same loader and run bootstrap in
`ws` mode:

```php
#!/usr/bin/env php
<?php
use App\Bootstrap;

$loader = require 'loader.php';
echo (new Bootstrap('ws'))->run();
```

Run this entrypoint as a long-running process, not from a normal web request.
In containers, prefer a dedicated `swoole` service. For one-off local debugging,
Podman can run the same entrypoint:

```bash
podman run -it --init --rm \
  -v /home/me/Projects:/app \
  --network="host" \
  localhost/php-app:8.4 \
  php /app/my-app/websocket
```

If host networking is not appropriate, bind the WebSocket port locally and let
Apache, Nginx, or another reverse proxy expose the public `/ws/` path:

```bash
podman run -it --init --rm \
  -v /home/me/Projects:/app \
  -p 127.0.0.1:8081:8081 \
  localhost/php-app:8.4 \
  php /app/my-app/websocket
```

## Main WebSocket Task Shape

Extend the app `AbstractTask`, then override only the lifecycle hooks needed by
the app. Reuse PhalconKit expose traits when broadcasts should match REST API
response shapes.

```php
namespace App\Modules\Ws\Tasks;

use App\Config\Exposers;
use Phalcon\Support\Collection;
use PhalconKit\Mvc\Controller\Traits\Expose;
use PhalconKit\Mvc\Controller\Traits\Query\Fields\ExposeFields;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

class MainTask extends AbstractTask
{
    use ExposeFields;
    use Expose;

    public const string CHANNEL_FUNDRAISING = 'fundraising';
    public const string CHANNEL_VOTE = 'vote';

    public Collection $channels;
    public Collection $exposers;

    public function initialize(): void
    {
        parent::initialize();

        $this->channels = new Collection([
            self::CHANNEL_FUNDRAISING,
            self::CHANNEL_VOTE,
        ]);

        $this->exposers = new Exposers();
    }

    public function onMessage(Server $server, Frame $frame): void
    {
        parent::onMessage($server, $frame);

        if (!json_validate($frame->data)) {
            $server->push($frame->fd, json_encode([
                'type' => 'error',
                'message' => 'Invalid JSON',
            ]));
            return;
        }

        $message = json_decode($frame->data, true);

        if (($message['type'] ?? null) === 'ping') {
            $server->push($frame->fd, json_encode(['type' => 'pong']));
            return;
        }

        // Subscribe, unsubscribe, and broadcast handling belongs here.
    }

    public function onClose(Server $server, int $fd): void
    {
        parent::onClose($server, $fd);
        $this->unsubscribeClient($fd);
    }
}
```

Protocol conventions:

- Accept only valid JSON frames.
- Require a `type`.
- Support `ping`/`pong` for clients that need heartbeat confirmation.
- Use channel names like `vote:123` or `fundraising:456`.
- Validate the channel type against an allow-list before subscribing.
- Validate auth/JWT before subscribing to private channels.
- Push an initial domain snapshot to a newly subscribed file descriptor.

## Channel Broadcasts

Use `subscribeClientToChannel()`, `unsubscribeClientFromChannel()`,
`unsubscribeClient()`, and `broadcastToChannel()` from the PhalconKit WS
abstract task.

```php
if ($message['type'] === 'subscribe') {
    $channel = (string)($message['channel'] ?? '');
    [$type, $id] = array_pad(explode(':', $channel, 2), 2, null);

    if (!in_array($type, $this->channels->toArray(), true)) {
        $server->push($frame->fd, json_encode([
            'type' => 'error',
            'message' => 'Channel is not allowed',
        ]));
        return;
    }

    $this->subscribeClientToChannel($frame->fd, $channel);
    $server->push($frame->fd, json_encode([
        'type' => 'subscribed',
        'channel' => $channel,
    ]));

    $fdList = [$frame->fd];
    if ($type === self::CHANNEL_VOTE) {
        $this->broadcastVote($server, (int)$id, $fdList);
    }
}
```

Keep client-originated `broadcast` messages separate from server-originated
domain broadcasts. Domain broadcasts should be generated from current model
state, not trusted client payloads.

## Redis Pub/Sub Bridge

Use a dedicated Swoole process for blocking Redis subscriptions. Create or
retrieve the Redis connection inside the process; do not share a parent process
connection across workers.

```php
use Phalcon\Di\Di;
use Redis;
use Swoole\Process;
use Swoole\WebSocket\Server;

public function addSubscriberProcess(Server $server): void
{
    $process = new Process(function () use ($server) {
        \Swoole\Runtime::enableCoroutine(SWOOLE_HOOK_ALL);

        $redis = Di::getDefault()->getRaw('redis')();
        $redis->setOption(Redis::OPT_READ_TIMEOUT, -1);

        $redis->subscribe(['websocket'], function ($redis, $channel, $message) use ($server) {
            if (!$message || !json_validate($message)) {
                return;
            }

            $payload = json_decode($message, true);

            foreach (range(0, $server->setting['worker_num'] - 1) as $workerId) {
                $server->sendMessage(json_encode($payload), $workerId);
            }
        });
    }, false, SOCK_DGRAM, true);

    $server->addProcess($process);
}
```

Handle forwarded messages in `onPipeMessage()`:

```php
public function onPipeMessage(Server $server, int $srcWorkerId, mixed $data): void
{
    parent::onPipeMessage($server, $srcWorkerId, $data);

    $payload = json_decode($data, true);
    if (!isset($payload['type'], $payload['id'])) {
        return;
    }

    switch ($payload['type']) {
        case self::CHANNEL_FUNDRAISING:
            $this->broadcastFundraising($server, (int)$payload['id']);
            break;
        case self::CHANNEL_VOTE:
            $this->broadcastVote($server, (int)$payload['id']);
            break;
    }
}
```

## Domain Snapshot Broadcasts

Broadcast current model state with eager-loaded relations and the same exposers
used by REST controllers.

```php
use App\Models\Vote;
use Phalcon\Db\Column;
use Phalcon\Support\Collection;
use PhalconKit\Mvc\Model\EagerLoading\QueryBuilder;
use Swoole\WebSocket\Server;

public function broadcastVote(Server $server, int $id, ?array $fdList = null): void
{
    $this->setExposeFields(new Collection($this->exposers->get('Vote')));

    $vote = Vote::findFirstWith([
        'VoteSubmissionList.VoteAnswerList',
        'VoteAllowedParticipantList' => function (QueryBuilder $query) {
            $query->where('deleted = 0');
        },
        'VoteAllowedParticipantList.ParticipantEntity.UserEntity',
        'VoteParticipantList' => function (QueryBuilder $query) {
            $query->where('deleted = 0');
        },
        'VoteParticipantList.ParticipantEntity.UserEntity',
    ], [
        'id = :id:',
        'bind' => ['id' => $id],
        'bindTypes' => ['id' => Column::BIND_PARAM_INT],
    ]);

    if (!$vote instanceof Vote) {
        return;
    }

    $channel = self::CHANNEL_VOTE . ':' . $id;

    $this->broadcastToChannel($server, $channel, [
        'type' => self::CHANNEL_VOTE,
        'channel' => $channel,
        'id' => $id,
        'view' => [
            'messages' => $vote->getMessages(),
            'data' => [
                ...$this->expose($vote),
                'resultList' => $vote->getResultList(),
            ],
        ],
    ], $fdList);
}
```

Use the same pattern for fundraising, notifications, or other live resources:
query the current entity, eager-load the exact relation graph, expose the result,
and broadcast a typed payload.

## Watchers And Timers

Use Swoole timers for periodic background checks, such as closing expired votes.
Store the timer id and clear it when there is no active work.

```php
use Swoole\Timer;
use Swoole\WebSocket\Server;

public function startVoteWatcher(Server $server): void
{
    $this->voteTimerId ??= Timer::tick(1000, function () use ($server) {
        $activeVotes = $this->closeExpiredVotes($server);

        if (!$activeVotes) {
            Timer::clear($this->voteTimerId);
            $this->voteTimerId = null;
        }
    });
}
```

Watcher rules:

- Make timer startup idempotent with `??=`.
- Clear the timer when no active records remain.
- Bind query values and bind types.
- Log model save messages when a watcher mutates state.
- Keep long-running work out of request/subscribe handlers.

## Error And Fallback Classes

Keep thin app wrappers for module error classes:

```php
namespace App\Modules\Api\Controllers;

class ErrorController extends \PhalconKit\Modules\Api\Controllers\ErrorController
{
}
```

```php
namespace App\Modules\Cli\Tasks;

class ErrorTask extends \PhalconKit\Modules\Cli\Tasks\ErrorTask
{
}
```

```php
namespace App\Modules\Ws\Tasks;

class ErrorTask extends \PhalconKit\Modules\Ws\Tasks\ErrorTask
{
}
```

These wrappers make router defaults and future app-level error handling explicit
even when they do not add behavior yet.

## Process Management

The WebSocket entrypoint must be supervised in staging and production. A
systemd unit should run the same bootstrap entrypoint, send a graceful signal,
restart on failure, and write logs somewhere the app owner can inspect.

```ini
[Unit]
Description=PHP Swoole WebSocket Server
After=network.target

[Service]
User=appuser
Group=appuser
ExecStart=/opt/alt/php84/usr/bin/php /home/appuser/example.test/websocket

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

On shared hosting with CageFS or CloudLinux, the command may need the host's
PHP wrapper instead of the raw PHP binary:

```ini
ExecStart=/usr/bin/lve_suwrapper 1006 /opt/alt/php84/usr/bin/php /home/appuser/example.test/websocket
```

Process rules:

- Use `SIGINT` so Swoole can shut down cleanly.
- Restart the process automatically, but keep restart delay nonzero.
- Raise `LimitNOFILE` when the server expects many simultaneous sockets.
- Keep logs outside the public web root.
- Do not expose the Swoole port directly unless the network boundary is
  intentional. Prefer Apache/Nginx proxying from `/ws/`.

## Choosing The Runtime

- Use MVC/Frontend for browser pages, layouts, and SPA host shells.
- Use API REST controllers for request/response CRUD, search, filters,
  permissions, and domain actions.
- Use CLI tasks for scheduled jobs, one-shot maintenance, imports, migrations,
  and queue-like work triggered by cron or operators.
- Use WebSocket tasks for live subscriptions, server-pushed snapshots, and
  channel-based state changes.

Do not put long-running watchers in REST controllers. Do not put HTTP response
formatting in WS tasks. Keep each runtime's surface small and explicit.
