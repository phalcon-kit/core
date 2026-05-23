<?php

/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Cli;

use PhalconKit\Bootstrap;
use PhalconKit\Models\Log;
use PhalconKit\Modules\Cli\Tasks\DataLifeCycleTask;
use PhalconKit\Tests\Unit\AbstractUnit;

class DataLifeCycleTaskTest extends AbstractUnit
{
    protected string $mode = Bootstrap::MODE_CLI;

    public function testInitializePreservesSubclassLifecycleModelsAndPolicies(): void
    {
        $task = new class extends DataLifeCycleTask {
            #[\Override]
            public function initialize(): void
            {
                $this->models = [
                    \App\Models\Incident::class => 'incident',
                    \App\Models\Data::class => 'data',
                ];

                $this->policies = array_merge($this->config->pathToArray('dataLifeCycle.policies') ?? [], [
                    'incident' => [
                        'query' => [
                            'conditions' => 'incident-condition',
                        ],
                    ],
                    'data' => [
                        'query' => [
                            'conditions' => 'data-condition',
                        ],
                    ],
                ]);

                parent::initialize();
            }
        };
        $task->setDI($this->di);

        $task->initialize();

        $models = $task->getDataLifeCycleModels();
        $this->assertSame('triennially', $models[Log::class]);
        $this->assertSame('incident', $models[\App\Models\Incident::class]);
        $this->assertSame('data', $models[\App\Models\Data::class]);

        $policies = $task->getDataLifeCyclePolicies();
        $this->assertArrayHasKey('triennially', $policies);
        $this->assertSame('incident-condition', $policies['incident']['query']['conditions']);
        $this->assertSame('data-condition', $policies['data']['query']['conditions']);
    }
}
