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

namespace PhalconKit\Tests\Unit\Modules\Cli;

use Phalcon\Messages\Message;
use PhalconKit\Modules\Cli\Task;
use PhalconKit\Tests\Unit\AbstractUnit;

class TaskTest extends AbstractUnit
{
    public function testCliPayloadNormalizationRecursivelySerializesMessages(): void
    {
        $message = new Message('Invalid email.', 'email', 'PresenceOf', 422);
        $task = new class extends Task {
            public function exposeNormalizeCliPayload(mixed $payload): mixed
            {
                return $this->normalizeCliPayload($payload);
            }
        };

        $this->assertSame([
            'errors' => [
                [
                    'message' => 'Invalid email.',
                    'field' => 'email',
                    'type' => 'PresenceOf',
                    'code' => 422,
                ],
            ],
        ], $task->exposeNormalizeCliPayload([
            'errors' => [$message],
        ]));
    }

    /**
     * @return iterable<string, array{string, string, list<string>, list<string>, array<string, mixed>}>
     */
    public static function positionalShapes(): iterable
    {
        $cacheDoc = "Usage:\n  phalcon-kit cli cache clear\n  phalcon-kit cli cache has <key>\n";
        $reportDoc = "Usage:\n  phalcon-kit cli report <action> [<params>...] [--force] [--table=<table>]\n\n"
            . "Options:\n  --force          Overwrite\n  --table=<table>  Table name\n";

        yield 'named positional is kept' => [$cacheDoc, 'has', ['my-key'], ['my-key'], ['key' => 'my-key']];
        yield 'flag and inline value are removed' => [
            $reportDoc, 'run', ['--force', 'a', '--table=users', 'b'], ['a', 'b'],
            ['force' => true, 'table' => 'users'],
        ];
        yield 'spaced option value is removed' => [
            $reportDoc, 'run', ['a', '--table', 'users'], ['a'], ['table' => 'users'],
        ];
        yield 'abbreviated option is removed' => [
            "Usage:\n  phalcon-kit cli user <action> [--password-stdin] [<params> ...]\n",
            'password', ['a@b.c', '--pass'], ['a@b.c'], ['passwordStdin' => true],
        ];
        yield 'double dash keeps later tokens' => [
            $reportDoc, 'run', ['a', '--', '--force'], ['a', '--', '--force'], ['force' => false],
        ];
    }

    /**
     * @param list<string> $tokens
     * @param list<string> $expected
     * @param array<string, mixed> $named
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('positionalShapes')]
    public function testBeforeExecuteRouteRemovesOnlyParsedOptionTokens(
        string $doc,
        string $action,
        array $tokens,
        array $expected,
        array $named
    ): void {
        $this->di->setShared('dispatcher', new \PhalconKit\Cli\Dispatcher());
        $task = new class extends Task {
        };
        $task->setDI($this->di);
        $task->cliDoc = $doc;
        $task->dispatcher->setModuleName('cli');
        $task->dispatcher->setTaskName(str_contains($doc, 'cache') ? 'cache' : (str_contains($doc, 'cli user') ? 'user' : 'report'));
        $task->dispatcher->setActionName($action);
        $task->dispatcher->setParameters(array_merge($tokens, ['format' => 'json']));

        $task->beforeExecuteRoute();

        $parameters = $task->dispatcher->getParameters();
        $this->assertSame($expected, array_values(array_filter($parameters, 'is_int', ARRAY_FILTER_USE_KEY)));
        $this->assertSame('json', $parameters['format']);
        foreach ($named as $key => $value) {
            $this->assertSame($value, $parameters[$key], $key);
        }
    }

    public function testHelpInheritedMainAndErrorTaskSkipUsageParsing(): void
    {
        $this->di->setShared('dispatcher', new \PhalconKit\Cli\Dispatcher());
        $strictDoc = "Usage:\n  phalcon-kit cli cache clear\n";
        $help = new class extends Task {
        };
        $help->setDI($this->di);
        $help->cliDoc = $strictDoc;
        $help->dispatcher->setModuleName('cli');
        $help->dispatcher->setTaskName('cache');
        $help->dispatcher->setActionName('help');
        $help->dispatcher->setParameters(['--force', 'format' => 'json']);

        // Docopt would print the usage and exit if it parsed `cache help`.
        $help->beforeExecuteRoute();
        $this->assertSame(['--force', 'format' => 'json'], $help->dispatcher->getParameters());

        $help->dispatcher->setActionName('main');
        $help->beforeExecuteRoute();
        $this->assertSame(['--force', 'format' => 'json'], $help->dispatcher->getParameters(), 'The inherited main action only prints the usage.');

        $main = new class extends Task {
            #[\Override]
            public function mainAction(): ?array
            {
                return [];
            }
        };
        $main->setDI($this->di);
        $main->cliDoc = "Usage:\n  phalcon-kit cli report main [--force]\n";
        $main->dispatcher->setTaskName('report');
        $main->dispatcher->setActionName('main');
        $main->dispatcher->setParameters(['--force']);
        $main->beforeExecuteRoute();
        $this->assertTrue($main->dispatcher->getParameter('force'), 'An overridden main action is parsed.');

        $error = new \PhalconKit\Modules\Cli\Tasks\ErrorTask();
        $error->setDI($this->di);
        $error->cliDoc = $strictDoc;
        $error->dispatcher->setModuleName('');
        $error->dispatcher->setTaskName('error');
        $error->dispatcher->setActionName('notFound');
        $error->dispatcher->setParameters(['format' => 'json']);

        $error->beforeExecuteRoute();
        $this->assertSame(['format' => 'json'], $error->dispatcher->getParameters());
    }
}
