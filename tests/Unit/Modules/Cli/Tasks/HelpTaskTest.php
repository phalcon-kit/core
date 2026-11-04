<?php

declare(strict_types=1);

/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhalconKit\Tests\Unit\Modules\Cli\Tasks;

use PhalconKit\Cli\Dispatcher;
use PhalconKit\Modules\Cli\Tasks\HelpTask;
use PhalconKit\Tests\Unit\AbstractUnit;
use PhalconKit\Tests\Unit\Modules\Cli\Tasks\Fixtures\ReportTask;

class HelpTaskTest extends AbstractUnit
{
    private function helpTask(string $namespace, string $action): HelpTask
    {
        $this->di->setShared('dispatcher', new Dispatcher());
        $help = new HelpTask();
        $help->setDI($this->di);
        $help->dispatcher->setNamespaceName($namespace);
        $help->dispatcher->setTaskName('help');
        $help->dispatcher->setActionName($action);
        return $help;
    }

    public function testUndeclaredTaskIsForwardedToItsHelpAction(): void
    {
        $this->assertTrue(class_exists(ReportTask::class));
        $help = $this->helpTask('PhalconKit\\Tests\\Unit\\Modules\\Cli\\Tasks\\Fixtures', 'report');

        $help->reportAction();

        $this->assertSame('report', $help->dispatcher->getTaskName());
        $this->assertSame('help', $help->dispatcher->getActionName());
    }

    public function testDeclaredTaskUsesTheDashedTaskName(): void
    {
        $help = $this->helpTask('PhalconKit\\Modules\\Cli\\Tasks', 'dataLifeCycle');

        $help->dataLifeCycleAction();

        $this->assertSame('data-life-cycle', $help->dispatcher->getTaskName());
        $this->assertSame('help', $help->dispatcher->getActionName());
    }

    public function testUnknownTaskIsForwardedToTheNotFoundRoute(): void
    {
        $help = $this->helpTask('PhalconKit\\Modules\\Cli\\Tasks', 'nosuch');
        $route = $this->di->get('config')->pathToArray('router.notFound');

        $help->nosuchAction();

        $this->assertSame($route['task'] ?? 'error', $help->dispatcher->getTaskName());
        $this->assertSame($route['action'] ?? 'notFound', $help->dispatcher->getActionName());
    }

    public function testUnknownNonActionMethodsStillFail(): void
    {
        $help = $this->helpTask('PhalconKit\\Modules\\Cli\\Tasks', 'main');

        $this->expectException(\BadMethodCallException::class);
        $help->missingMethod();
    }

    public function testTaskNamesAreNotParsedAgainstTheHelpUsage(): void
    {
        $help = $this->helpTask('PhalconKit\\Tests\\Unit\\Modules\\Cli\\Tasks\\Fixtures', 'report');
        $help->dispatcher->setModuleName('cli');
        $help->dispatcher->setParameters(['format' => 'json']);

        // Docopt would print the usage and exit if it parsed `help report`.
        $help->beforeExecuteRoute();

        $this->assertSame(['format' => 'json'], $help->dispatcher->getParameters());
    }
}
