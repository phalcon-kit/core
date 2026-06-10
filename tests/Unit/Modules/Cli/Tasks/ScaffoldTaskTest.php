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

    public function testDefinitionsPreserveOriginalSourceName(): void
    {
        $task = $this->createScaffoldTask();
        $definitions = $task->getDefinitionsAction('legacy_user_accounts');

        $this->assertSame('LegacyUserAccounts', $definitions['table']);
        $this->assertSame('legacy_user_accounts', $definitions['source']);
    }

    public function testDefaultAbstractOutputInitializesOriginalSourceName(): void
    {
        $task = $this->createScaffoldTask([
            'noLicense' => true,
            'noStrictTypes' => true,
        ]);
        $definitions = $task->getDefinitionsAction('legacy_user_accounts');

        $output = $task->createAbstractOutput(
            $definitions,
            [$this->createIdColumn()],
            $this->createEmptyRelationships(),
            []
        );

        $this->assertStringContainsString(
            <<<'PHP'
    public function initialize(): void
    {
        parent::initialize();
        $this->setSource('legacy_user_accounts');
    }
PHP,
            $output
        );
        $this->assertStringNotContainsString("setSource('LegacyUserAccounts')", $output);
    }

    public function testModelTestOutputAssertsOriginalSourceName(): void
    {
        $task = $this->createScaffoldTask([
            'noLicense' => true,
            'noStrictTypes' => true,
        ]);

        $output = $task->createModelTestOutput(
            $task->getDefinitionsAction('legacy_user_accounts'),
            [$this->createIdColumn()]
        );

        $this->assertStringContainsString(
            "\$this->assertSame('legacy_user_accounts', \$this->legacyUserAccounts->getSource());",
            $output
        );
        $this->assertStringNotContainsString(
            "\$this->assertSame('legacy_user_accounts', \$this->legacyUserAccounts->getSource());\t",
            $output
        );
    }

    public function testNoSetSourceOmitsAbstractInitializeMethod(): void
    {
        $task = $this->createScaffoldTask([
            'noLicense' => true,
            'noSetSource' => true,
            'noStrictTypes' => true,
        ]);
        $definitions = $task->getDefinitionsAction('legacy_user_accounts');

        $output = $task->createAbstractOutput(
            $definitions,
            [$this->createIdColumn()],
            $this->createEmptyRelationships(),
            []
        );

        $this->assertStringNotContainsString('function initialize(): void', $output);
        $this->assertStringNotContainsString('setSource(', $output);
    }

    public function testNoGetSetMethodsOmitsGeneratedAccessors(): void
    {
        $task = $this->createScaffoldTask([
            'noGetSetMethods' => true,
            'noLicense' => true,
            'noStrictTypes' => true,
        ]);
        $columns = [$this->createNameColumn()];

        $this->assertSame('', $task->getGetSetMethods($columns));

        $output = $task->createAbstractOutput(
            $task->getDefinitionsAction('legacy_user_accounts'),
            $columns,
            $this->createEmptyRelationships(),
            []
        );

        $this->assertStringNotContainsString('function getName()', $output);
        $this->assertStringNotContainsString('function setName(', $output);
    }

    public function testNoTestsSkipsModelTestGeneration(): void
    {
        $directory = $this->createTemporaryDirectory();

        try {
            $task = $this->createScaffoldTaskWithDatabase([
                'directory' => $directory,
                'noAbstracts' => true,
                'noEnums' => true,
                'noInterfaces' => true,
                'noModels' => true,
                'noTests' => true,
            ]);

            $result = $task->runAction();

            $this->assertSame([], $result);
            $this->assertFileDoesNotExist($directory . '/tests/Unit/Models/LegacyUserAccountsTest.php');
        } finally {
            $this->removeDirectory($directory);
        }
    }

    public function testNoEnumsSkipsEnumGeneration(): void
    {
        $directory = $this->createTemporaryDirectory();

        try {
            $task = $this->createScaffoldTaskWithDatabase([
                'directory' => $directory,
                'noAbstracts' => true,
                'noEnums' => true,
                'noInterfaces' => true,
                'noModels' => true,
                'noTests' => true,
            ]);

            $result = $task->runAction();

            $this->assertSame([], $result);
            $this->assertFileDoesNotExist($directory . '/src/Models/Enums/LegacyUserAccountsStatus.php');
        } finally {
            $this->removeDirectory($directory);
        }
    }

    public function testSaveFileTrimsTrailingWhitespaceFromGeneratedLines(): void
    {
        $directory = $this->createTemporaryDirectory();
        $file = $directory . '/Output.php';

        try {
            $task = $this->createScaffoldTask();

            $this->assertTrue($task->saveFile($file, "first  \n\t\nsecond\t \n", true));
            $this->assertSame("first\n\nsecond\n", file_get_contents($file));
        } finally {
            $this->removeDirectory($directory);
        }
    }

    public function testCanonicalJoinTableKeepsShortManyToManyAlias(): void
    {
        $task = $this->createScaffoldTaskWithTables([
            'left' => [
                $this->createIdColumn(),
            ],
            'left_right' => [
                $this->createIdColumn(),
                $this->createIntegerColumn('left_id'),
                $this->createIntegerColumn('right_id'),
            ],
            'right' => [
                $this->createIdColumn(),
            ],
        ]);

        $relationships = $task->getRelationshipItems(
            'left',
            $task->describeColumns('left'),
            ['left', 'left_right', 'right']
        );

        $this->assertStringContainsString(
            "\$this->hasMany('id', LeftRight::class, 'leftId', ['alias' => 'LeftRightList']);",
            $relationships['items']
        );
        $this->assertStringContainsString("['alias' => 'RightList']", $relationships['items']);
        $this->assertStringNotContainsString("['alias' => 'LeftRightRightList']", $relationships['items']);
    }

    public function testDirectHasManyAliasTakesPriorityOverCanonicalManyToManyAlias(): void
    {
        $task = $this->createScaffoldTaskWithTables([
            'left' => [
                $this->createIdColumn(),
            ],
            'left_right' => [
                $this->createIdColumn(),
                $this->createIntegerColumn('left_id'),
                $this->createIntegerColumn('right_id'),
            ],
            'right' => [
                $this->createIdColumn(),
                $this->createIntegerColumn('left_id'),
            ],
        ]);

        $relationships = $task->getRelationshipItems(
            'left',
            $task->describeColumns('left'),
            ['left', 'left_right', 'right']
        );

        $this->assertStringContainsString(
            "\$this->hasMany('id', Right::class, 'leftId', ['alias' => 'RightList']);",
            $relationships['items']
        );
        $this->assertStringContainsString("['alias' => 'LeftRightRightList']", $relationships['items']);
        $this->assertSame(1, substr_count($relationships['items'], "['alias' => 'RightList']"));
    }

    public function testContextualIntermediateUsesExplicitAliasesWithoutCollidingWithDirectRelations(): void
    {
        $task = $this->createScaffoldTaskWithTables([
            'communication' => [
                $this->createIdColumn(),
            ],
            'communication_contact' => [
                $this->createIdColumn(),
                $this->createIntegerColumn('communication_id'),
                $this->createIntegerColumn('contact_id'),
                $this->createIntegerColumn('user_id'),
            ],
            'communication_update' => [
                $this->createIdColumn(),
                $this->createIntegerColumn('communication_id'),
            ],
            'communication_update_user' => [
                $this->createIdColumn(),
                $this->createIntegerColumn('communication_id'),
                $this->createIntegerColumn('communication_update_id'),
                $this->createIntegerColumn('communication_contact_id'),
                $this->createIntegerColumn('user_id'),
            ],
            'contact' => [
                $this->createIdColumn(),
            ],
            'user' => [
                $this->createIdColumn(),
            ],
        ]);

        $relationships = $task->getRelationshipItems(
            'communication',
            $task->describeColumns('communication'),
            [
                'communication',
                'communication_contact',
                'communication_update',
                'communication_update_user',
                'contact',
                'user',
            ]
        );

        $this->assertStringContainsString(
            "\$this->hasMany('id', CommunicationContact::class, 'communicationId', ['alias' => 'CommunicationContactList']);",
            $relationships['items']
        );
        $this->assertStringContainsString(
            "\$this->hasMany('id', CommunicationUpdate::class, 'communicationId', ['alias' => 'CommunicationUpdateList']);",
            $relationships['items']
        );
        $this->assertStringContainsString("['alias' => 'ContactList']", $relationships['items']);
        $this->assertStringContainsString(
            "['alias' => 'CommunicationContactUserList']",
            $relationships['items']
        );
        $this->assertStringContainsString(
            "['alias' => 'CommunicationUpdateUserCommunicationUpdateList']",
            $relationships['items']
        );
        $this->assertStringContainsString(
            "['alias' => 'CommunicationUpdateUserCommunicationContactList']",
            $relationships['items']
        );
        $this->assertStringContainsString(
            "['alias' => 'CommunicationUpdateUserUserList']",
            $relationships['items']
        );

        $this->assertSame(1, substr_count($relationships['items'], "['alias' => 'CommunicationContactList']"));
        $this->assertSame(1, substr_count($relationships['items'], "['alias' => 'CommunicationUpdateList']"));
        $this->assertSame(0, substr_count($relationships['items'], "['alias' => 'UserList']"));
    }

    private function createScaffoldTask(array $params = []): ScaffoldTask
    {
        $dispatcher = new Dispatcher();
        $dispatcher->setParams(array_merge([
            'namespace' => 'App',
            'noAbstracts' => false,
            'noColumnMap' => false,
            'noComments' => false,
            'noControllers' => false,
            'noEnums' => false,
            'noGetSetMethods' => false,
            'noInterfaces' => false,
            'noLicense' => false,
            'noModels' => false,
            'noRelationships' => false,
            'noSetSource' => false,
            'noStrictTypes' => false,
            'noTests' => false,
            'noValidations' => false,
            'noTypings' => false,
            'granularTypings' => false,
            'addRawValueType' => false,
            'protectedProperties' => false,
        ], $params));
        $this->di?->set('dispatcher', $dispatcher);

        $task = new ScaffoldTask();
        $task->setDI($this->di);

        return $task;
    }

    private function createScaffoldTaskWithDatabase(array $params = []): ScaffoldTask
    {
        $columns = [
            $this->createIdColumn(),
            new Column('status', [
                'type' => Column::TYPE_ENUM,
                'size' => "'draft','published'",
                'notNull' => true,
            ]),
        ];

        $database = new class ($columns) {
            public function __construct(private readonly array $columns)
            {
            }

            public function listTables(): array
            {
                return ['legacy_user_accounts'];
            }

            public function describeColumns(string $table): array
            {
                return $this->columns;
            }

            public function describeIndexes(string $table): array
            {
                return [];
            }

            public function describeReferences(string $table): array
            {
                return [];
            }
        };
        $this->di?->set('db', $database);

        return $this->createScaffoldTask($params);
    }

    private function createScaffoldTaskWithTables(array $columnsByTable, array $params = []): ScaffoldTask
    {
        $database = new class ($columnsByTable) {
            public function __construct(private readonly array $columnsByTable)
            {
            }

            public function listTables(): array
            {
                return array_keys($this->columnsByTable);
            }

            public function describeColumns(string $table): array
            {
                return $this->columnsByTable[$table] ?? [];
            }

            public function describeIndexes(string $table): array
            {
                return [];
            }

            public function describeReferences(string $table): array
            {
                return [];
            }
        };
        $this->di?->set('db', $database);

        return $this->createScaffoldTask($params);
    }

    private function createIdColumn(): Column
    {
        return new Column('id', [
            'type' => Column::TYPE_INTEGER,
            'primary' => true,
            'notNull' => true,
            'autoIncrement' => true,
        ]);
    }

    private function createIntegerColumn(string $name): Column
    {
        return new Column($name, [
            'type' => Column::TYPE_INTEGER,
            'notNull' => true,
        ]);
    }

    private function createNameColumn(): Column
    {
        return new Column('name', [
            'type' => Column::TYPE_VARCHAR,
            'size' => 255,
            'notNull' => true,
        ]);
    }

    private function createEmptyRelationships(): array
    {
        return [
            'interfaceInjectableItems' => ' *',
            'injectableItems' => ' *',
            'useItems' => '',
            'interfaceUseItems' => '',
            'items' => '// no default relationship found',
        ];
    }

    private function createTemporaryDirectory(): string
    {
        $directory = sys_get_temp_dir() . '/phalconkit-scaffold-' . bin2hex(random_bytes(8));

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
