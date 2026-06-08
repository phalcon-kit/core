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

namespace PhalconKit\Tests\Unit\Modules\Cli\Tasks;

use PhalconKit\Bootstrap;
use PhalconKit\Cli\Dispatcher;
use PhalconKit\Modules\Cli\Tasks\ScaffoldTask;
use PhalconKit\Tests\Unit\AbstractUnit;
use PHPUnit\Framework\Attributes\DataProvider;

class ScaffoldTaskTest extends AbstractUnit
{
    protected string $mode = Bootstrap::MODE_CLI;

    #[DataProvider('phpHeaderProvider')]
    public function testGeneratedModelInterfaceOutputUsesNormalizedPhpHeader(
        array $params,
        string $expectedHeader
    ): void {
        $task = $this->createScaffoldTask($params);
        $output = $task->createModelInterfaceOutput([
            'modelInterface' => [
                'name' => 'WidgetInterface',
            ],
            'abstractInterface' => [
                'name' => 'WidgetAbstractInterface',
            ],
        ]);

        $namespacePosition = strpos($output, 'namespace ');
        $this->assertIsInt($namespacePosition);
        $actualHeader = substr($output, 0, $namespacePosition);

        $this->assertSame($expectedHeader, $actualHeader);
        $this->assertStringStartsWith(
            $expectedHeader . "namespace App\\Models\\Interfaces;\n\n",
            $output
        );
        $this->assertStringNotContainsString("<?php\n\n\ndeclare", $output);
        $this->assertDoesNotMatchRegularExpression('/[ \t]+$/m', $actualHeader);
    }

    public static function phpHeaderProvider(): array
    {
        $license = <<<PHP
/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
PHP;

        return [
            'license and strict types' => [
                [],
                "<?php\n\n{$license}\n\ndeclare(strict_types=1);\n\n",
            ],
            'no license and strict types' => [
                ['noLicense' => true],
                "<?php\n\ndeclare(strict_types=1);\n\n",
            ],
            'license and no strict types' => [
                ['noStrictTypes' => true],
                "<?php\n\n{$license}\n\n",
            ],
            'no license and no strict types' => [
                [
                    'noLicense' => true,
                    'noStrictTypes' => true,
                ],
                "<?php\n\n",
            ],
        ];
    }

    private function createScaffoldTask(array $params): ScaffoldTask
    {
        $dispatcher = new Dispatcher();
        $dispatcher->setParams(array_merge([
            'namespace' => 'App',
            'noLicense' => false,
            'noStrictTypes' => false,
        ], $params));
        $this->di?->set('dispatcher', $dispatcher);

        $task = new ScaffoldTask();
        $task->setDI($this->di);

        return $task;
    }
}
