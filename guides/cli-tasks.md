# Application CLI Tasks

CLI tasks run through the same application configuration and DI services as HTTP.
Use them for imports, scheduled jobs, reports, and explicit maintenance operations.

## Add A Task

Create `src/Modules/Cli/Tasks/StatusTask.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cli\Tasks;

/** Read-only runtime information for deployment checks. */
class StatusTask extends AbstractTask
{
    public function infoAction(): array
    {
        return [
            'php' => PHP_VERSION,
            'phalcon' => phpversion('phalcon'),
            'environment' => $this->config->path('app.env'),
        ];
    }
}
```

Add to `permissions.roles.cli.components` in `src/Config.php`:

```php
\App\Modules\Cli\Tasks\StatusTask::class => ['info'],
```

Run:

```shell
./bin/phalcon-kit cli status info
```

Output is JSON containing the actual PHP/Phalcon versions and environment. This
checks the CLI process, which may differ from PHP-FPM or a WebSocket worker.

## Arguments And Options

The inherited task parser accepts positional parameters. For options, define a
Docopt usage string on your task and read normalized names from the dispatcher:

```php
public string $cliDoc = <<<DOC
Usage:
  phalcon-kit cli report run [--limit=<limit>]

Options:
  --limit=<limit>  Maximum rows [default: 100]
DOC;

public function runAction(): array
{
    $limit = (int)$this->dispatcher->getParameter('limit');
    if ($limit < 1 || $limit > 1000) {
        throw new \InvalidArgumentException('Limit must be between 1 and 1000.');
    }
    return ['limit' => $limit];
}
```

This snippet belongs in a `ReportTask`, with a `run` grant. Run
`./bin/phalcon-kit cli report run --limit=25`; expected JSON is `{"limit":25}`.
Validate values before using them for file paths, SQL, or external actions.

The task formatter serializes returned arrays, booleans, and model messages.
Do not assume every JSON failure automatically sets a nonzero process exit code;
explicitly define the exit-status contract for commands used by automation and
test both success and failure.

## Reuse A Core Task

An application module dispatches in its own namespace. The App skeleton includes
this bridge for model generation:

```php
namespace App\Modules\Cli\Tasks;

class ScaffoldTask extends \PhalconKit\Modules\Cli\Tasks\ScaffoldTask
{
}
```

Its permission is `App\Modules\Cli\Tasks\ScaffoldTask::class => ['*']` under the
`cli` role. The model-generation wrappers select the output flags; do not grant
this task to HTTP or WebSocket clients.

The skeleton also registers Core's `UserTask` for `create`, `password`, `role`,
and `help`. Use the [account command recipe](authentication.md#create-an-account-with-the-cli)
for password input, role setup, and expected results. Other Core tasks need an
application subclass and explicit permission after reviewing their data scope.

## Scheduled Work

Have a task call an application service with an explicit batch size, progress
marker, and retry policy. Keep idempotency in that service so a scheduler retry
does not resend email or repeat a financial operation. Use an application lock
when overlapping runs would conflict.

A cron entry might invoke an application-defined task:

```cron
*/5 * * * * /usr/bin/php /var/www/app/bin/phalcon-kit cli notification deliver >> /var/www/app/storage/log/notification.log 2>&1
```

`notification deliver` is your task, not a built-in delivery queue. Set the
runtime user, environment, working paths, and log retention deliberately. Keep
credentials out of command arguments and logs.

## Migration And Scaffolding Commands

Use the skeleton's `scripts/migration-*.sh` and `scripts/*generate-models.sh`
wrappers, or their `.ps1` equivalents. They derive the project root from their
own path. Migration tools must be installed in the environment running them;
they are development dependencies in the skeleton.

See [Database Migrations](database-migrations.md) and
[Scaffolding](database-scaffolding.md) for exact commands and expected artifacts.
