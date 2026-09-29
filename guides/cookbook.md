# Application Cookbook

Use these recipes alongside the complete [Project tutorial](first-rest-resource.md).
Each example states the application code or configuration it requires.

## A JSON Health Endpoint

Create `src/Modules/Api/Controllers/HealthController.php`. A non-model endpoint
can extend `Rest` directly:

```php
<?php
namespace App\Modules\Api\Controllers;

class HealthController extends \PhalconKit\Mvc\Controller\Rest
{
    public function indexAction(): \Phalcon\Http\ResponseInterface
    {
        $this->setRestViewVar('healthy', true);
        return $this->setRestResponse(true);
    }
}
```

Grant `HealthController::class => ['index']` under the intended role, then call:

```shell
curl http://127.0.0.1:8080/api/health
```

Expected HTTP 200 with `response: true` and `view.healthy: true`. This proves
application dispatch. Add separate private readiness checks for dependencies;
do not expose database details, secrets, or full runtime configuration publicly.

## A Shared Application Service

For a service you implement at `src/Service/ReportExporter.php`, register its
constructor dependencies in `src/Provider/Report/ServiceProvider.php`:

```php
<?php
namespace App\Provider\Report;

use App\Service\ReportExporter;
use PhalconKit\Di\DiInterface;
use PhalconKit\Provider\AbstractServiceProvider;

class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'reportExporter';

    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), static fn () => new ReportExporter(
            $di->getTyped('db', \Phalcon\Contracts\Db\Adapter\Adapter::class)
        ));
    }
}
```

The example assumes your `ReportExporter` constructor accepts that database
contract; it is an application class, not shipped by Core. Register the provider
in `src/Config.php`:

```php
'providers' => [
    \App\Provider\Report\ServiceProvider::class =>
        \App\Provider\Report\ServiceProvider::class,
],
```

Resolve it with `$this->di->getShared('reportExporter')` in a controller/task.
Use ordinary constructor injection inside services.

## A Project State Transition

Add this business method to the tutorial's concrete `App\Models\Project`:

```php
/** Activate a draft project once its budget has been assigned. */
public function activate(): void
{
    if ($this->getStatus() !== 'draft' || (int)$this->getBudget() <= 0) {
        throw new \DomainException('Only a funded draft project can be activated.');
    }
    $this->setStatus('active');
}
```

Call it from a controller action after resolving the record through the
controller's authorized query:

```php
public function activateAction(): \Phalcon\Http\ResponseInterface
{
    $project = $this->findFirst();
    if (!$project instanceof \App\Models\Project) {
        return $this->setRestErrorResponse(404);
    }
    try {
        $project->activate();
    } catch (\DomainException $exception) {
        $this->setRestViewVar('messages', [$exception->getMessage()]);
        return $this->setRestErrorResponse(422, response: false);
    }
    if (!$project->save()) {
        $this->setRestViewVar('messages', $project->getMessages());
        return $this->setRestErrorResponse(422, response: false);
    }
    $this->setRestViewVar('data', $this->expose($project));
    return $this->setRestResponse(true);
}
```

Grant `activate`, allow POST in the controller's method map, and grant model
`find`/`update` for the write role. Request:

```shell
curl http://127.0.0.1:8080/api/project/activate \
  -b cookies.txt -c cookies.txt \
  -H "X-Authorization: Bearer $API_TOKEN" \
  -H 'Content-Type: application/json' --data '{"id":2}'
```

On the original fixture, project 2 changes from draft to active and returns
HTTP 200 with its exposed data. Repeating the transition returns 422. A missing
or hidden record returns 404. For concurrent transitions, add locking or an
application version check inside a service transaction.

## A Stable Custom Representation

For an application-defined transformer, call it explicitly in the action:

```php
$data = [
    'id' => $project->getId(),
    'label' => $project->getLabel(),
    'status' => $project->getStatus(),
    'links' => ['self' => '/api/project/find-first?id=' . $project->getId()],
];
$this->setRestViewVar('data', $data);
return $this->setRestResponse(true);
```

A transformer class is not automatically discovered just because it exists.
Use controller exposure for simple field selection; use an explicit transformer
when a client contract needs derived fields or a different structure.

## Common Application Workflows

| Build | Recipe |
| --- | --- |
| Paginated search screen | [Filters, search, stable order, totals](rest-filtering.md) |
| Detail screen with child rows | [Controlled eager graph](rest-relationships.md#load-a-controlled-graph) |
| Parent/child edit form | [Nested writes and ownership](rest-relationships.md#write-children) |
| Facet menu and dashboard totals | [Distinct and aggregates](rest-aggregates.md) |
| CSV download | [Export format, columns, and limits](rest-aggregates.md#csv-xml-and-excel) |
| Account provisioning | [Existing CLI user commands](authentication.md#create-an-account-with-the-cli) |
| Scheduled notification job | [CLI task calling a service](cli-tasks.md#scheduled-work) |
| Live project notifications | [WebSocket application protocol](web-server-and-websocket.md#application-subscriptions) |

Test the final application behavior with [Application Testing](application-testing.md).
