# Developer Cookbook

These focused recipes are designed to be copied, renamed, and adapted. Each
recipe identifies the ownership boundary so the example remains maintainable as
the application grows.

!!! note

    Namespace and route conventions can differ between application skeletons.
    Keep the Phalcon Kit API usage, but adapt `App\...` namespaces and URLs to
    the project’s module layout.

## Return A JSON Health Response

Use the non-model-backed `Rest` controller for health checks, webhooks, and
workflow endpoints that do not represent CRUD over one model.

```php
<?php

namespace App\Modules\Api\Controllers;

use Phalcon\Http\ResponseInterface;
use PhalconKit\Mvc\Controller\Rest;

final class HealthController extends Rest
{
    public function indexAction(): ResponseInterface
    {
        return $this->setRestResponse([
            'ok' => true,
            'time' => new \DateTimeImmutable()->format(DATE_ATOM),
        ]);
    }
}
```

Try it:

```bash
curl --include http://127.0.0.1:8000/api/health
```

Use the model-backed application base controller only for resources that need
the REST query/save policy surface.

## Register An Application Service

Services shared by controllers, tasks, or other services belong in a provider.

```php
<?php

namespace App\Provider\Report;

use App\Service\ReportExporter;
use PhalconKit\Di\DiInterface;
use PhalconKit\Provider\AbstractServiceProvider;

final class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'reportExporter';

    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), static function () use ($di) {
            return new ReportExporter(
                $di->getTyped('db', \Phalcon\Db\Adapter\AdapterInterface::class),
                $di->getTyped('logger', \Phalcon\Logger\LoggerInterface::class)
            );
        });
    }
}
```

Register it in app config:

```php
'providers' => [
    \App\Provider\Report\ServiceProvider::class =>
        \App\Provider\Report\ServiceProvider::class,
],
```

Resolve by the stable service name from an injectable controller or task:

```php
$file = $this->reportExporter->exportProject($projectId);
```

Constructor injection remains preferable inside plain domain services. The DI
provider is the composition boundary, not a reason to make every class
container-aware.

## Load A Relationship Graph Efficiently

Use `findWith()` or `findFirstWith()` when the graph is known by server code:

```php
$projects = Project::findWith(
    ['OwnerEntity', 'TaskList.AssigneeEntity'],
    [
        'conditions' => 'status = :status:',
        'bind' => ['status' => 'active'],
        'order' => 'createdAt DESC',
        'limit' => 25,
    ]
);
```

For one record:

```php
$project = Project::findFirstWith(
    ['OwnerEntity', 'TaskList'],
    [
        'conditions' => 'id = :id:',
        'bind' => ['id' => $projectId],
    ]
);
```

Use aliases generated from the actual relationships. Do not guess an alias from
the table name—inspect the generated abstract model after scaffolding.

## Add A Workflow Action

Business transitions are clearer as named actions than as unrestricted field
updates.

```php
<?php

namespace App\Modules\Api\Controllers;

use Phalcon\Http\ResponseInterface;
use PhalconKit\Mvc\Controller\Attributes\PermissionFeature;

final class ProjectController extends AbstractController
{
    #[PermissionFeature('project.manage')]
    public function archiveProjectAction(): ResponseInterface
    {
        $project = $this->findFirst();

        if (!$project) {
            return $this->setRestErrorResponse(404, response: false);
        }

        $project->archive();

        if (!$project->save()) {
            return $this->setRestErrorResponse(
                $this->getRestActionFailureStatusCode($project->getMessages()),
                response: $project->getMessages()
            );
        }

        return $this->setRestResponse(true);
    }
}
```

Keep the state-transition rule in `Project::archive()`. The controller owns
request lookup, authorization, response mapping, and HTTP status.

## Expose A Safe Distinct-Value Endpoint

Distinct values are useful for filters and autocomplete controls, but the
endpoint is closed until a controller explicitly approves fields.

```php
public function initializeDistinctActionFields(): void
{
    $this->setDistinctActionFields([
        'status',
        'type',
        'ownerEmail' => 'Owner.email',
    ]);
}
```

Clients can then request:

```http
GET /api/project/distinct?field=status
GET /api/project/distinct?field=ownerEmail&search=example.com
```

The endpoint reuses normal query filters, joins, identity conditions, and
permission policy. Do not expose sensitive or high-cardinality fields simply
because they are filterable.

## Return A Stable Public Representation

Use a transformer when API output should not mirror the model’s internal field
names or relationship layout.

```php
<?php

namespace App\Transformers;

use App\Models\Project;
use League\Fractal\TransformerAbstract;

final class ProjectTransformer extends TransformerAbstract
{
    public function transform(Project $project): array
    {
        return [
            'id' => $project->getId(),
            'name' => $project->getName(),
            'status' => $project->getStatus(),
            'links' => [
                'self' => '/api/project/' . $project->getId(),
            ],
        ];
    }
}
```

Transformers are especially useful for long-lived clients. The model can evolve
without forcing its database naming and helper methods into the public contract.

## Add A CLI Task

CLI tasks use the same configured services as the HTTP application:

```php
<?php

namespace App\Modules\Cli\Tasks;

use PhalconKit\Cli\Task;

final class ProjectTask extends Task
{
    public function archiveInactiveAction(int $days = 90): void
    {
        $count = $this->projectArchiver->archiveInactive($days);
        $this->logger->info('Archived inactive projects', [
            'days' => $days,
            'count' => $count,
        ]);

        echo "Archived {$count} projects", PHP_EOL;
    }
}
```

Route syntax depends on the application CLI entrypoint. A common invocation is:

```bash
php cli project archive-inactive 90
```

Keep orchestration in the task and reusable business behavior in a service so
HTTP actions, scheduled jobs, and tests can share it.

## Add A Focused Model Test

Test domain behavior in the concrete model without asserting generated getters
one by one:

```php
public function testDraftProjectCanBeActivated(): void
{
    $project = new Project();
    $project->setStatus('draft');

    $project->activate();

    self::assertSame('active', $project->getStatus());
}

public function testArchivedProjectCannotBeActivated(): void
{
    $project = new Project();
    $project->setStatus('archived');

    $this->expectException(\DomainException::class);
    $project->activate();
}
```

Add database-backed tests when the behavior depends on relationships,
transactions, indexes, generated defaults, or adapter-specific SQL.

## Recipe Selection Guide

| Need | Put it here |
| --- | --- |
| One model’s invariant | Concrete model |
| Several models or an external API | Domain/application service |
| Shared dependency construction | Service provider |
| HTTP input, query policy, and response | Controller |
| Stable client-facing shape | Transformer |
| Record visibility | Permission/query condition |
| Operational orchestration | CLI or WebSocket task |

For the complete concepts behind these recipes, continue with
[Architecture](architecture.md), [Configuration](configuration.md),
[REST APIs](rest-api.md), and
[Models And Eager Loading](models-and-eager-loading.md).
