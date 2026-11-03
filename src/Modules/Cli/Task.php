<?php

declare(strict_types=1);

/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

namespace PhalconKit\Modules\Cli;

use Phalcon\Cli\Dispatcher;
use Phalcon\Messages\MessageInterface;
use PhalconKit\Exception\CliException;
use PhalconKit\Support\Helper;
use PhalconKit\Support\Utils;

class Task extends \PhalconKit\Cli\Task
{
    public string $cliDoc = <<<DOC
Usage:
  phalcon-kit cli <task> <action> [<params> ...]

Options:
  task: build,cache,cron,errors,help,scaffold


DOC;
    
    /**
     * Parse task-specific arguments and options with this task's `cliDoc`.
     *
     * Bootstrap already parsed the global options (`--format`, `--quiet`,
     * `--debug`, ...) and stored them as named dispatcher parameters. The task
     * parser therefore receives only the module, task, action, and positional
     * tokens, so global options never reach a task usage string that does not
     * declare them. Parsed task values are stored as camelCase dispatcher
     * parameters, for example `--password-stdin` becomes `passwordStdin`.
     *
     * Invalid task arguments make Docopt print the task usage and exit with a
     * non-zero status.
     */
    public function beforeExecuteRoute(): void
    {
        $payload = (new \Docopt())->handle($this->cliDoc, ['argv' => $this->getTaskArgv(), 'optionsFirst' => false]);
        foreach ($payload as $key => $value) {
            if (!is_null($value) && preg_match('/(<(.*?)>|\-\-(.*))/', $key, $matches)) {
                $match = array_pop($matches);
                if (!empty($match)) {
                    $key = lcfirst(Helper::camelize(Helper::uncamelize($match)));
                    $this->dispatcher->setParameter($key, $value);
                }
            }
        }
    }
    
    /**
     * Build the argument vector parsed by the task-level Docopt usage.
     *
     * The vector is rebuilt from the dispatcher instead of `$_SERVER['argv']`:
     * `[module, task, action, ...positional tokens]`. Positional tokens are the
     * integer-keyed dispatcher parameters that Bootstrap left after consuming
     * its global options; named parameters such as `format` or `quiet` are
     * excluded.
     *
     * @return list<string>
     */
    protected function getTaskArgv(): array
    {
        $positional = array_filter($this->dispatcher->getParameters(), 'is_int', ARRAY_FILTER_USE_KEY);
        ksort($positional);

        return array_map('strval', [
            $this->dispatcher->getModuleName(),
            $this->dispatcher->getTaskName(),
            $this->dispatcher->getActionName(),
            ...array_values($positional),
        ]);
    }

    public function helpAction(): void
    {
        echo $this->cliDoc;
    }
    
    public function mainAction(): ?array
    {
        $this->helpAction();
        
        return null;
    }
    
    /**
     * Handle rest response automagically
     * @param Dispatcher $dispatcher
     * @return void
     * @throws CliException
     */
    public function afterExecuteRoute(Dispatcher $dispatcher): void
    {
        // Merge response into view variables
        $payload = $this->normalizeCliPayload($dispatcher->getReturnedValue());
        
        // Quiet output
        $quiet = $this->dispatcher->getParameter('quiet');
        if ($quiet) {
            exit(!$payload ? 1 : 0);
        }
        
        // Format response
        $format = $this->dispatcher->getParameter('format');
        $format ??= 'json';
        switch (strtolower($format)) {
            case 'dump':
                dump($payload);
                break;
            
            case 'var_export':
                var_export($payload);
                break;
            
            case 'print_r':
                print_r($payload);
                break;
            
            case 'serialize':
                echo serialize($payload);
                break;
            
            case 'json':
                echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                echo PHP_EOL;
                break;
            
            case 'string':
                if (is_string($payload)) {
                    echo $payload;
                }
                else if (is_bool($payload)) {
                    echo $payload ? 'true' : 'false';
                }
                else if (is_null($payload)) {
                    echo 'null';
                }
                else if (is_numeric($payload)) {
                    echo $payload;
                }
                else {
                    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    echo PHP_EOL;
                }
                break;
            
            case 'raw':
                if (is_string($payload) || is_bool($payload) || is_null($payload) || is_numeric($payload)) {
                    echo $payload;
                }
                else {
                    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    echo PHP_EOL;
                }
                break;
            
            default:
                throw new CliException('Unknown output format `' . $format . '` expected one of the string value: `json` `serialize` `dump` `raw`');
        }
    }

    /**
     * Normalize values before CLI output serializers see them.
     *
     * Phalcon message objects are useful inside the framework but are opaque for
     * JSON automation. This helper recursively converts them into scalar arrays
     * while leaving other payload values unchanged.
     */
    protected function normalizeCliPayload(mixed $payload): mixed
    {
        if ($payload instanceof MessageInterface) {
            return $this->normalizeCliMessage($payload);
        }

        if (is_array($payload)) {
            foreach ($payload as $key => $value) {
                $payload[$key] = $this->normalizeCliPayload($value);
            }
        }

        return $payload;
    }

    /**
     * Normalize a list of model messages and optionally add a fallback entry.
     *
     * @param iterable<mixed> $messages Messages returned by a model or resultset.
     *
     * @return list<array{message: string, field: string|null, type: string|null, code: int|null}>
     */
    protected function normalizeCliMessages(iterable $messages, ?string $fallbackMessage = null): array
    {
        $normalized = [];
        foreach ($messages as $message) {
            if ($message instanceof MessageInterface) {
                $normalized[] = $this->normalizeCliMessage($message);
                continue;
            }

            $normalized[] = [
                'message' => $this->stringifyCliMessage($message),
                'field' => null,
                'type' => get_debug_type($message),
                'code' => null,
            ];
        }

        if ($normalized === [] && $fallbackMessage !== null) {
            $normalized[] = [
                'message' => $fallbackMessage,
                'field' => null,
                'type' => 'SaveFailed',
                'code' => null,
            ];
        }

        return $normalized;
    }

    /**
     * Convert one Phalcon message object to JSON-safe scalar fields.
     *
     * @return array{message: string, field: string|null, type: string|null, code: int|null}
     */
    private function normalizeCliMessage(MessageInterface $message): array
    {
        return [
            'message' => (string) $message->getMessage(),
            'field' => $this->normalizeCliMessageValue($message->getField()),
            'type' => $this->normalizeCliMessageValue($message->getType()),
            'code' => $message->getCode(),
        ];
    }

    /**
     * Convert optional Phalcon message fields and types to JSON-safe strings.
     */
    private function normalizeCliMessageValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_scalar($value) ? (string) $value : get_debug_type($value);
    }

    /**
     * Convert non-standard message values to a concise CLI-safe string.
     */
    private function stringifyCliMessage(mixed $message): string
    {
        if ($message instanceof \Stringable || is_scalar($message)) {
            return (string) $message;
        }

        $json = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return is_string($json) ? $json : get_debug_type($message);
    }
}
