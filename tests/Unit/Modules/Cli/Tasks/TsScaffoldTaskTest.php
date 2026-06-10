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

use Phalcon\Db\Column;
use PhalconKit\Bootstrap;
use PhalconKit\Cli\Dispatcher;
use PhalconKit\Modules\Cli\Tasks\TsScaffoldTask;
use PhalconKit\Tests\Unit\AbstractUnit;

class TsScaffoldTaskTest extends AbstractUnit
{
    protected string $mode = Bootstrap::MODE_CLI;

    public function testDefinitionsAndOutputsUseStableTypescriptNames(): void
    {
        $task = $this->createTsScaffoldTask();
        $definitions = $task->getDefinitionsAction('legacy_user_accounts');
        $columns = [
            $this->createColumn('id', Column::TYPE_INTEGER),
            $this->createColumn('display_name', Column::TYPE_VARCHAR),
            $this->createColumn('settings', Column::TYPE_JSON),
            $this->createColumn('is_active', Column::TYPE_BOOLEAN),
        ];

        $this->assertSame('LegacyUserAccounts', $definitions['table']);
        $this->assertSame('legacy-user-accounts', $definitions['slug']);
        $this->assertSame('LegacyUserAccountsModel.ts', $definitions['model']['file']);
        $this->assertSame('LegacyUserAccountsService.ts', $definitions['service']['file']);
        $this->assertSame('LegacyUserAccountsModelInterface.ts', $definitions['interface']['file']);
        $this->assertSame('LegacyUserAccountsModelAbstract.ts', $definitions['abstract']['file']);

        $interfaceOutput = $task->createInterfaceOutput($definitions, $columns);
        $this->assertStringContainsString('export interface LegacyUserAccountsModelInterface {', $interfaceOutput);
        $this->assertStringContainsString('  id: number;', $interfaceOutput);
        $this->assertStringContainsString('  displayName: string;', $interfaceOutput);
        $this->assertStringContainsString('  settings: object;', $interfaceOutput);
        $this->assertStringContainsString('  isActive: boolean;', $interfaceOutput);

        $abstractOutput = $task->createAbstractOutput($definitions, $columns);
        $this->assertStringContainsString(
            "import { AbstractModel } from '../AbstractModel';\n",
            $abstractOutput
        );
        $this->assertStringContainsString(
            "import { LegacyUserAccountsModelInterface } from './interfaces/LegacyUserAccountsModelInterface';\n",
            $abstractOutput
        );
        $this->assertStringContainsString(
            'export class LegacyUserAccountsModelAbstract extends AbstractModel implements LegacyUserAccountsModelInterface',
            $abstractOutput
        );
        $this->assertStringContainsString('  displayName!: string;', $abstractOutput);

        $modelOutput = $task->createModelOutput($definitions, [
            'import' => [
                'OwnerModel' => '',
            ],
            'data' => [
                'Owner' => 'OwnerModel',
                'OwnerList' => 'OwnerModel[]',
            ],
        ]);
        $this->assertStringContainsString("import { Type } from 'class-transformer';", $modelOutput);
        $this->assertStringContainsString(
            "import { LegacyUserAccountsModelAbstract } from './abstracts/LegacyUserAccountsModelAbstract';",
            $modelOutput
        );
        $this->assertStringContainsString("import { OwnerModel } from './OwnerModel';", $modelOutput);
        $this->assertStringContainsString('export class LegacyUserAccountsModel extends LegacyUserAccountsModelAbstract', $modelOutput);
        $this->assertStringContainsString('  @Type(() => OwnerModel)', $modelOutput);
        $this->assertStringContainsString('  Owner!: OwnerModel;', $modelOutput);
        $this->assertStringContainsString('  OwnerList!: OwnerModel[];', $modelOutput);

        $serviceOutput = $task->createServiceOutput($definitions);
        $this->assertStringContainsString('export class LegacyUserAccountsService extends AbstractService', $serviceOutput);
        $this->assertStringContainsString("    modelUrl = 'legacy-user-accounts';", $serviceOutput);
        $this->assertStringContainsString('    model = LegacyUserAccountsModel;', $serviceOutput);
    }

    public function testGenerateExportsWritesIndexFileForTypescriptDirectory(): void
    {
        $directory = $this->createTemporaryDirectory();

        try {
            $this->assertIsInt(file_put_contents($directory . '/Alpha.ts', ''));
            $this->assertIsInt(file_put_contents($directory . '/Beta.ts', ''));
            $task = $this->createTsScaffoldTask([
                'directory' => $directory . '/',
            ]);

            $result = $task->generateExportsAction();

            $expectedExports = [
                "export {Alpha} from './Alpha'",
                "export {Beta} from './Beta'",
            ];
            $this->assertSame($expectedExports, $result['exports']);
            $this->assertSame($directory . '/index.ts', $result['filePath']);
            $this->assertTrue($result['saved']);
            $this->assertSame(implode("\n", $expectedExports), file_get_contents($directory . '/index.ts'));
        } finally {
            $this->removeDirectory($directory);
        }
    }

    public function testSaveFileRespectsForceForTypescriptOutput(): void
    {
        $directory = $this->createTemporaryDirectory();
        $file = $directory . '/models/Output.ts';

        try {
            $task = $this->createTsScaffoldTask();

            $this->assertTrue($task->saveFile($file, 'first'));
            $this->assertFalse($task->saveFile($file, 'second'));
            $this->assertSame('first', file_get_contents($file));
            $this->assertTrue($task->saveFile($file, 'second', true));
            $this->assertSame('second', file_get_contents($file));
        } finally {
            $this->removeDirectory($directory);
        }
    }

    private function createTsScaffoldTask(array $params = []): TsScaffoldTask
    {
        $dispatcher = new Dispatcher();
        $dispatcher->setParams(array_merge([
            'directory' => './sdk/',
            'force' => false,
            'table' => '',
        ], $params));
        $this->di?->set('dispatcher', $dispatcher);

        $task = new TsScaffoldTask();
        $task->setDI($this->di);

        return $task;
    }

    private function createColumn(string $name, int $type): Column
    {
        return new Column($name, [
            'type' => $type,
            'notNull' => true,
        ]);
    }

    private function createTemporaryDirectory(): string
    {
        $directory = sys_get_temp_dir() . '/phalconkit-ts-scaffold-' . bin2hex(random_bytes(8));

        $this->assertTrue(mkdir($directory, 0755, true));

        return $directory;
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
                continue;
            }

            unlink($item->getPathname());
        }

        rmdir($directory);
    }
}
