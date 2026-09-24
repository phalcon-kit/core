<?php

declare(strict_types=1);

/**
 * Supplement the Markdown template's missing model-enum pages after phpDocumentor.
 *
 * Model enums are generated case-only contracts. Read names and backing values
 * through PHP reflection, then add their pages to the same Home/menu pipeline.
 * Fail on custom methods instead of silently publishing an incomplete reference.
 */

$projectRoot = dirname(__DIR__);
require $projectRoot . '/vendor/autoload.php';

$docsRoot = $projectRoot . '/docs';
$namespace = 'PhalconKit\\Models\\Enums';
$relativeDirectory = 'classes/' . str_replace('\\', '/', $namespace);
$directory = $docsRoot . '/' . $relativeDirectory;
if (!is_dir($directory) && !mkdir($directory, 0775, true)) {
    throw new RuntimeException('Unable to create enum documentation directory.');
}

$home = file_get_contents($docsRoot . '/Home.md');
if ($home === false) {
    throw new RuntimeException('Run phpDocumentor before generating enum pages.');
}
$heading = '### \\' . $namespace;
if (str_contains($home, $heading . "\n")) {
    throw new RuntimeException('Enum navigation already exists; review the Markdown template or regenerate docs first.');
}
$index = $heading . "\n\n| Enum | Description |\n| --- | --- |\n";

foreach (glob($projectRoot . '/src/Models/Enums/*.php') ?: [] as $file) {
    $name = basename($file, '.php');
    $enum = new ReflectionEnum($namespace . '\\' . $name);
    foreach ($enum->getMethods() as $method) {
        if (!$method->isInternal()) {
            throw new RuntimeException('Extend enum documentation for custom methods on ' . $enum->getName());
        }
    }
    $type = $enum->getBackingType()?->getName();
    $declaration = 'enum ' . $name . ($type === null ? '' : ': ' . $type);
    $page = '# ' . $name . "\n\n```php\nnamespace " . $namespace . ";\n\n" . $declaration . "\n```\n\n";
    $page .= "## Cases\n\n| Case | Value |\n| --- | --- |\n";
    foreach ($enum->getCases() as $case) {
        $value = $case instanceof ReflectionEnumBackedCase
            ? htmlspecialchars(json_encode($case->getBackingValue(), JSON_THROW_ON_ERROR), ENT_QUOTES, 'UTF-8')
            : '—';
        $page .= '| `' . $case->getName() . '` | <code>' . str_replace('|', '&#124;', $value) . "</code> |\n";
    }
    $page .= "\nUse [`cases()`](https://www.php.net/manual/en/unitenum.cases.php) to list cases.\n";
    if ($enum->isBacked()) {
        $page .= "Use [`from()`](https://www.php.net/manual/en/backedenum.from.php) or\n";
        $page .= "[`tryFrom()`](https://www.php.net/manual/en/backedenum.tryfrom.php) to resolve a backing value.\n";
    }
    if (file_put_contents($directory . '/' . $name . '.md', $page) === false) {
        throw new RuntimeException('Unable to write enum documentation: ' . $name);
    }
    $index .= '| [`' . $name . '`](./' . $relativeDirectory . '/' . $name . '.md) | '
        . ($type === null ? 'Unit enum.' : '`' . $type . '` backed enum.') . " |\n";
}

// Match the template's namespace ordering before generating the shared menu.
$offset = strlen($home);
preg_match_all('/^### (.+)$/m', $home, $sections, PREG_OFFSET_CAPTURE);
foreach ($sections[1] as [$section, $position]) {
    if (strcmp($section, '\\' . $namespace) > 0) {
        $offset = $position - strlen('### ');
        break;
    }
}
$home = substr($home, 0, $offset) . $index . "\n" . substr($home, $offset);
if (file_put_contents($docsRoot . '/Home.md', $home) === false) {
    throw new RuntimeException('Unable to update the enum documentation index.');
}
