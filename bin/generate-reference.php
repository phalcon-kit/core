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

/**
 * Generate the reference pages in guides/reference/: environment variables and
 * services from the default configuration in src/Bootstrap/Config.php, and CLI
 * commands from the usage text of Bootstrap and Core's CLI tasks.
 *
 * Usage:
 *   php bin/generate-reference.php          Write guides/reference/*.md
 *   php bin/generate-reference.php --check  Exit 1 when they are stale
 *
 * The parser reads the source instead of evaluating the config, so each row
 * shows the default as written (constants, expressions, and nested
 * `Env::get()` fallbacks) and the config path that receives the value.
 */

namespace PhalconKit\Bin\Reference;

const CONFIG_FILE = 'src/Bootstrap/Config.php';
const ENVIRONMENT_PAGE = 'guides/reference/environment.md';
const SERVICES_PAGE = 'guides/reference/services.md';
const CLI_PAGE = 'guides/reference/cli.md';

/**
 * One `Env::get()` call inside the default configuration array.
 *
 * @psalm-type Entry = array{variable: string, path: list<string>, default: string, comment: string, fallback: bool, line: int}
 */

/**
 * Parse every `Env::get()` call in the constructor's default config array.
 *
 * @return array{entries: list<array{variable: string, path: list<string>, default: string, comment: string, fallback: bool, line: int}>, sections: array<string, string>, constants: list<array{constant: string, variable: string, default: string}>}
 */
function parse(string $source): array
{
    $tokens = \PhpToken::tokenize($source);
    $count = count($tokens);

    $start = null;
    for ($i = 0; $i < $count; $i++) {
        if ($tokens[$i]->is(T_STRING) && $tokens[$i]->text === 'internalMergeAppend') {
            for ($j = $i + 1; $j < $count; $j++) {
                if ($tokens[$j]->text === '[') {
                    $start = $j;
                    break 2;
                }
            }
        }
    }
    if ($start === null) {
        throw new \RuntimeException('The default config array was not found in ' . CONFIG_FILE . '.');
    }

    /** @var list<array{type: string, key: ?string, env: bool}> $stack */
    $stack = [];
    $pendingKey = null;
    $keyTokens = '';
    $docComment = '';
    $sections = [];
    $entries = [];

    for ($i = $start; $i < $count; $i++) {
        $token = $tokens[$i];
        $text = $token->text;
        $inBracket = $stack !== [] && $stack[array_key_last($stack)]['type'] === '[';

        if ($token->is(T_DOC_COMMENT)) {
            $docComment = $text;
            continue;
        }
        if ($token->is([T_WHITESPACE, T_COMMENT])) {
            continue;
        }

        if (isEnvGet($tokens, $i)) {
            $open = nextSignificant($tokens, $i + 3);
            $close = matching($tokens, $open);
            $arguments = splitArguments($tokens, $open, $close);
            $variable = trim($arguments[0] ?? '', " '\"");
            $entries[] = [
                'variable' => $variable,
                'path' => currentPath($stack, $inBracket ? $pendingKey : null),
                'default' => isset($arguments[1]) ? normalize($arguments[1]) : 'null',
                'comment' => trailingComment($tokens, $close),
                'fallback' => array_filter($stack, static fn (array $frame): bool => $frame['env']) !== [],
                'line' => $token->line,
            ];
            $stack[] = ['type' => '(', 'key' => $inBracket ? $pendingKey : null, 'env' => true];
            $i = $open;
            continue;
        }

        if ($text === '[' || $text === '(') {
            $stack[] = ['type' => $text, 'key' => $inBracket ? $pendingKey : null, 'env' => false];
            $pendingKey = null;
            $keyTokens = '';
            continue;
        }

        if ($text === ']' || $text === ')') {
            array_pop($stack);
            if ($stack === []) {
                break;
            }
            $keyTokens = '';
            continue;
        }

        if (!$inBracket) {
            continue;
        }

        if ($token->is(T_DOUBLE_ARROW)) {
            $pendingKey = keyName($keyTokens);
            if (count($stack) === 1) {
                $sections[$pendingKey] = cleanDocComment($docComment);
            }
            $docComment = '';
            $keyTokens = '';
            continue;
        }

        if ($text === ',') {
            $pendingKey = null;
            $keyTokens = '';
            $docComment = '';
            continue;
        }

        $keyTokens .= $text;
    }

    return ['entries' => $entries, 'sections' => $sections, 'constants' => parseConstants($tokens, $start)];
}

/**
 * Parse `define('NAME', Env::get(...))` path constants set before the config.
 *
 * @param list<\PhpToken> $tokens
 * @return list<array{constant: string, variable: string, default: string}>
 */
function parseConstants(array $tokens, int $end): array
{
    $constants = [];
    for ($i = 0; $i < $end; $i++) {
        if (!$tokens[$i]->is(T_STRING) || $tokens[$i]->text !== 'define') {
            continue;
        }
        $open = nextSignificant($tokens, $i + 1);
        if ($tokens[$open]->text !== '(') {
            continue;
        }
        $close = matching($tokens, $open);
        $arguments = splitArguments($tokens, $open, $close);
        $value = $arguments[1] ?? '';
        if (preg_match('/^Env::get\(\s*\'([A-Z0-9_]+)\'\s*(?:,(.*))?\)$/s', $value, $match) === 1) {
            $constants[] = [
                'constant' => trim($arguments[0], " '\""),
                'variable' => $match[1],
                'default' => isset($match[2]) ? normalize($match[2]) : 'null',
            ];
        }
        $i = $close;
    }

    return $constants;
}

/** @param list<\PhpToken> $tokens */
function isEnvGet(array $tokens, int $i): bool
{
    return $tokens[$i]->is(T_STRING) && $tokens[$i]->text === 'Env'
        && ($tokens[$i + 1]->text ?? '') === '::'
        && strcasecmp($tokens[$i + 2]->text ?? '', 'get') === 0
        && $tokens[nextSignificant($tokens, $i + 3)]->text === '(';
}

/** @param list<\PhpToken> $tokens */
function nextSignificant(array $tokens, int $i): int
{
    while (isset($tokens[$i]) && $tokens[$i]->is([T_WHITESPACE, T_COMMENT, T_DOC_COMMENT])) {
        $i++;
    }

    return $i;
}

/** @param list<\PhpToken> $tokens */
function matching(array $tokens, int $open): int
{
    $depth = 0;
    for ($i = $open, $count = count($tokens); $i < $count; $i++) {
        $text = $tokens[$i]->text;
        if ($text === '(' || $text === '[') {
            $depth++;
        } elseif ($text === ')' || $text === ']') {
            $depth--;
            if ($depth === 0) {
                return $i;
            }
        }
    }

    throw new \RuntimeException('Unbalanced Env::get() call on line ' . $tokens[$open]->line . '.');
}

/**
 * @param list<\PhpToken> $tokens
 * @return list<string>
 */
function splitArguments(array $tokens, int $open, int $close): array
{
    $arguments = [];
    $current = '';
    $depth = 0;
    for ($i = $open + 1; $i < $close; $i++) {
        $text = $tokens[$i]->text;
        if ($text === '(' || $text === '[') {
            $depth++;
        } elseif ($text === ')' || $text === ']') {
            $depth--;
        } elseif ($text === ',' && $depth === 0) {
            $arguments[] = trim($current);
            $current = '';
            continue;
        }
        $current .= $tokens[$i]->is(T_COMMENT) ? '' : $text;
    }
    if (trim($current) !== '') {
        $arguments[] = trim($current);
    }

    return $arguments;
}

/** @param list<\PhpToken> $tokens */
function trailingComment(array $tokens, int $close): string
{
    for ($i = $close + 1, $count = count($tokens); $i < $count; $i++) {
        $token = $tokens[$i];
        if ($token->is(T_COMMENT) && str_starts_with($token->text, '//')) {
            return trim(substr($token->text, 2));
        }
        if ($token->is(T_WHITESPACE) && str_contains($token->text, "\n")) {
            return '';
        }
        if (!$token->is(T_WHITESPACE) && $token->text !== ',') {
            return '';
        }
    }

    return '';
}

/**
 * @param list<array{type: string, key: ?string, env: bool}> $stack
 * @return list<string>
 */
function currentPath(array $stack, ?string $pendingKey): array
{
    $path = [];
    foreach ($stack as $frame) {
        if ($frame['key'] !== null && ($path === [] || end($path) !== $frame['key'] || $frame['type'] === '[')) {
            $path[] = $frame['key'];
        }
    }
    if ($pendingKey !== null) {
        $path[] = $pendingKey;
    }

    // A key followed by a call or nested Env::get() is pushed once per frame.
    return array_values(array_reduce($path, static function (array $carry, string $key): array {
        if (end($carry) !== $key) {
            $carry[] = $key;
        }
        return $carry;
    }, []));
}

function keyName(string $tokens): string
{
    $key = trim($tokens);
    if (preg_match('/^([\'"])(.*)\1$/s', $key, $match) === 1) {
        return $match[2];
    }

    return normalize($key);
}

function normalize(string $expression): string
{
    return trim((string) preg_replace('/\s+/', ' ', $expression));
}

function cleanDocComment(string $comment): string
{
    $lines = preg_split('/\R/', $comment) ?: [];
    $text = [];
    foreach ($lines as $line) {
        $line = trim((string) preg_replace('#^\s*(/\*\*|\*/|\*)#', '', $line));
        if ($line !== '' && $line !== '/') {
            $text[] = $line;
        }
    }

    return implode(' ', $text);
}

function cell(string $value): string
{
    return str_replace(['|', "\n"], ['\|', ' '], $value);
}

function code(string $value): string
{
    if ($value === '') {
        return '';
    }
    $fence = str_contains($value, '`') ? '``' : '`';

    return $fence . ($fence === '``' ? ' ' : '') . cell($value) . ($fence === '``' ? ' ' : '') . $fence;
}

/**
 * @param array{entries: list<array{variable: string, path: list<string>, default: string, comment: string, fallback: bool, line: int}>, sections: array<string, string>, constants: list<array{constant: string, variable: string, default: string}>} $parsed
 */
function renderEnvironment(array $parsed): string
{
    $bySection = [];
    foreach ($parsed['entries'] as $entry) {
        $bySection[$entry['path'][0] ?? ''][] = $entry;
    }

    $variables = array_unique(array_column($parsed['entries'], 'variable'));
    $out = [];
    $out[] = '<!-- Generated by bin/generate-reference.php from ' . CONFIG_FILE . '. Do not edit. -->';
    $out[] = '';
    $out[] = '# Environment Variables';
    $out[] = '';
    $out[] = 'Every variable Core reads in its default configuration, grouped by the';
    $out[] = 'top-level config key it sets. Set a variable in `.env` or the process';
    $out[] = 'environment to change the default; set the config path in your';
    $out[] = 'application `Config` to override it in code. The default column shows the';
    $out[] = 'source expression: a nested `Env::get()` is a fallback variable, and';
    $out[] = 'constants such as `APP_PATH` are resolved at bootstrap.';
    $out[] = '';
    $out[] = sprintf('Core reads %d variables in %d sections.', count($variables), count($bySection));
    $out[] = '';

    if ($parsed['constants'] !== []) {
        $out[] = '## Path Constants';
        $out[] = '';
        $out[] = 'Core defines these constants before it builds the config, unless your';
        $out[] = 'bootstrap already defined them (the App skeleton does, in `bootstrap.php`).';
        $out[] = '';
        $out[] = '| Constant | Variable | Default |';
        $out[] = '| --- | --- | --- |';
        foreach ($parsed['constants'] as $constant) {
            $out[] = sprintf('| %s | %s | %s |', code($constant['constant']), code($constant['variable']), code($constant['default']));
        }
        $out[] = '';
    }

    foreach ($bySection as $section => $entries) {
        $out[] = '## `' . $section . '`';
        $out[] = '';
        $description = $parsed['sections'][$section] ?? '';
        if ($description !== '') {
            $out[] = rtrim($description, '.') . '.';
            $out[] = '';
        }
        $out[] = '| Variable | Config path | Default | Notes |';
        $out[] = '| --- | --- | --- | --- |';
        foreach ($entries as $entry) {
            $notes = $entry['comment'];
            if ($entry['fallback']) {
                $notes = trim('Fallback when the outer variable is unset. ' . $notes);
            }
            $out[] = sprintf(
                '| %s | %s | %s | %s |',
                code($entry['variable']),
                code(implode('.', $entry['path'])),
                code($entry['default']),
                cell($notes)
            );
        }
        $out[] = '';
    }

    return implode("\n", $out);
}

/**
 * @param array{entries: list<array{variable: string, path: list<string>, default: string, comment: string, fallback: bool, line: int}>, sections: array<string, string>, constants: list<array{constant: string, variable: string, default: string}>} $parsed
 */
function renderServices(array $parsed, string $root): string
{
    $registered = [];
    foreach ($parsed['entries'] as $entry) {
        if (($entry['path'][0] ?? '') === 'providers' && isset($entry['path'][1])) {
            $class = 'PhalconKit\\' . preg_replace('/::class$/', '', $entry['path'][1]);
            $registered[$class] = $entry['variable'];
        }
    }

    $out = [];
    $out[] = '<!-- Generated by bin/generate-reference.php from ' . CONFIG_FILE . '. Do not edit. -->';
    $out[] = '';
    $out[] = '# Services';
    $out[] = '';
    $out[] = 'Core registers one DI service per provider listed in the `providers`';
    $out[] = 'config, in this order. Resolve a service by name with';
    $out[] = '`$this->di->get(\'<name>\')` or `$this-><name>` in an injectable class.';
    $out[] = 'Replace a provider by setting its variable to your own provider class, or';
    $out[] = 'by mapping the provider class to yours in the application `providers`';
    $out[] = 'config; see [Configuration](../configuration.md#provider-overrides).';
    $out[] = '';
    $out[] = '## Registered By Default';
    $out[] = '';
    $out[] = '| Service | Provider | Override variable | Purpose |';
    $out[] = '| --- | --- | --- | --- |';
    foreach ($registered as $class => $variable) {
        [$service, $summary] = describeProvider($class);
        $out[] = sprintf('| %s | %s | %s | %s |', code($service), code($class), code($variable), cell($summary));
    }
    $out[] = '';

    $optional = [];
    foreach (glob($root . '/src/Provider/*/ServiceProvider.php') ?: [] as $file) {
        $class = 'PhalconKit\\Provider\\' . basename(dirname($file)) . '\\ServiceProvider';
        if (!isset($registered[$class])) {
            $optional[] = $class;
        }
    }
    sort($optional);
    if ($optional !== []) {
        $out[] = '## Available, Not Registered By Default';
        $out[] = '';
        $out[] = 'Add these providers to your application `providers` config to register';
        $out[] = 'them; most need an optional Composer package or PHP extension.';
        $out[] = '';
        $out[] = '| Service | Provider | Purpose |';
        $out[] = '| --- | --- | --- |';
        foreach ($optional as $class) {
            [$service, $summary] = describeProvider($class);
            $out[] = sprintf('| %s | %s | %s |', code($service), code($class), cell($summary));
        }
        $out[] = '';
    }

    return implode("\n", $out);
}

/**
 * @return array{string, string} The DI service name and the provider summary.
 */
function describeProvider(string $class): array
{
    if (!class_exists($class)) {
        throw new \RuntimeException('Provider class ' . $class . ' does not exist.');
    }
    $reflection = new \ReflectionClass($class);
    $service = (string) $reflection->getProperty('serviceName')->getDefaultValue();
    $summary = '';
    $doc = $reflection->getDocComment();
    if (is_string($doc)) {
        $summary = explode('. ', cleanDocComment(preg_split('/\n\s*\*\s*\n/', $doc)[0] ?? ''))[0];
        $summary = $summary === '' ? '' : rtrim($summary, '.') . '.';
    }

    return [$service, $summary];
}

/**
 * Render the built-in CLI tasks from their Docopt usage text.
 */
function renderCli(string $root): string
{
    $bootstrap = new \ReflectionClass(\PhalconKit\Bootstrap::class);
    $global = (string) $bootstrap->getProperty('cliDoc')->getDefaultValue();

    $out = [];
    $out[] = '<!-- Generated by bin/generate-reference.php from the CLI usage text. Do not edit. -->';
    $out[] = '';
    $out[] = '# CLI Commands';
    $out[] = '';
    $out[] = 'Core ships these CLI tasks. Run them through your application entrypoint,';
    $out[] = 'for example `./bin/phalcon-kit cli user help`. A task runs only when your';
    $out[] = 'CLI module has a task class with that name and the `cli` role grants the';
    $out[] = 'action: the App skeleton exposes `user`, `scaffold`, `help`, and its own';
    $out[] = '`cron`. To expose another built-in task, add a class that extends it in';
    $out[] = 'your CLI module and grant it; see [CLI Tasks](../cli-tasks.md).';
    $out[] = '';
    $out[] = '```php';
    $out[] = 'namespace App\\Modules\\Cli\\Tasks;';
    $out[] = '';
    $out[] = 'class CacheTask extends \\PhalconKit\\Modules\\Cli\\Tasks\\CacheTask';
    $out[] = '{';
    $out[] = '}';
    $out[] = '```';
    $out[] = '';
    $out[] = '```php';
    $out[] = "'cli' => [";
    $out[] = "    'components' => [";
    $out[] = "        \\App\\Modules\\Cli\\Tasks\\CacheTask::class => ['clear', 'help'],";
    $out[] = '    ],';
    $out[] = '],';
    $out[] = '```';
    $out[] = '';
    $out[] = '## Global Options';
    $out[] = '';
    $out[] = 'Options placed before `cli` apply to every task.';
    $out[] = '';
    $out[] = '```text';
    $out[] = rtrim($global);
    $out[] = '```';
    $out[] = '';

    $tasks = [];
    foreach (glob($root . '/src/Modules/Cli/Tasks/*Task.php') ?: [] as $file) {
        $short = basename($file, '.php');
        if (in_array($short, ['AbstractTask', 'ErrorTask'], true)) {
            continue;
        }
        $tasks[$short] = 'PhalconKit\\Modules\\Cli\\Tasks\\' . $short;
    }
    ksort($tasks);
    foreach ($tasks as $short => $class) {
        $reflection = new \ReflectionClass($class);
        $name = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', substr($short, 0, -4)));
        $out[] = '## `' . $name . '`';
        $out[] = '';
        $out[] = 'Class: `' . $class . '`';
        $out[] = '';
        $out[] = '```text';
        $out[] = rtrim((string) $reflection->getProperty('cliDoc')->getDefaultValue());
        $out[] = '```';
        $out[] = '';
    }

    return implode("\n", $out);
}

/**
 * Render the reference pages.
 *
 * @return array<string, string> Page contents keyed by repository path.
 */
function render(string $root): array
{
    $source = file_get_contents($root . '/' . CONFIG_FILE);
    if ($source === false) {
        throw new \RuntimeException('Unable to read ' . CONFIG_FILE . '.');
    }
    $parsed = parse($source);

    return [
        ENVIRONMENT_PAGE => renderEnvironment($parsed) . "\n",
        SERVICES_PAGE => renderServices($parsed, $root) . "\n",
        CLI_PAGE => renderCli($root) . "\n",
    ];
}

if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $root = dirname(__DIR__);
    require_once $root . '/vendor/autoload.php';
    $check = in_array('--check', $argv, true);
    $stale = [];
    foreach (render($root) as $path => $content) {
        $file = $root . '/' . $path;
        if (!is_file($file) || file_get_contents($file) !== $content) {
            $stale[] = $path;
            if (!$check) {
                if (!is_dir(dirname($file))) {
                    mkdir(dirname($file), 0775, true);
                }
                file_put_contents($file, $content);
            }
        }
    }
    if ($check && $stale !== []) {
        fwrite(STDERR, 'Stale reference: ' . implode(', ', $stale) . ". Run php bin/generate-reference.php.\n");
        exit(1);
    }
    echo ($check ? 'Checked' : 'Generated') . ' reference pages; ' . count($stale) . " changed.\n";
}
