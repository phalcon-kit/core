<?php

declare(strict_types=1);

$normalizePath = static function (string $path): string {
    $segments = [];

    foreach (explode('/', str_replace('\\', '/', $path)) as $segment) {
        if ($segment === '' || $segment === '.') {
            continue;
        }

        if ($segment === '..') {
            array_pop($segments);
            continue;
        }

        $segments[] = $segment;
    }

    return '/' . implode('/', $segments);
};

$projectRoot = dirname(__DIR__);
$docsRoot = $projectRoot . '/docs';
$nativeClasses = [
    'ArrayAccess',
    'AssertionError',
    'DateMalformedStringException',
    'Exception',
    'InvalidArgumentException',
    'LogicException',
    'ReflectionException',
    'RuntimeException',
    'Throwable',
];
$nativeTargets = [];

foreach ($nativeClasses as $nativeClass) {
    $nativeTargets[$normalizePath($docsRoot . '/classes/' . $nativeClass . '.md')] =
        'https://www.php.net/manual/en/class.' . strtolower($nativeClass) . '.php';
}

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($docsRoot));

foreach ($iterator as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'md') {
        continue;
    }

    $filePath = $file->getPathname();
    $contents = file_get_contents($filePath);

    if ($contents === false) {
        throw new RuntimeException('Unable to read generated documentation: ' . $filePath);
    }

    $contents = preg_replace_callback(
        '~\]\(([^)]+\.md)\)~',
        static function (array $matches) use ($filePath, $nativeTargets, $normalizePath): string {
            $target = $matches[1];
            $externalUrl = match (true) {
                str_contains($target, '/Phalcon/') => 'https://docs.phalcon.io/latest/api/',
                str_contains($target, '/League/Csv/') => 'https://csv.thephpleague.com/',
                str_contains($target, '/League/OAuth2/Client/') => 'https://oauth2-client.thephpleague.com/',
                str_contains($target, '/League/Fractal/') => 'https://fractal.thephpleague.com/',
                default => $nativeTargets[$normalizePath(dirname($filePath) . '/' . $target)] ?? null,
            };

            if ($externalUrl === null) {
                return $matches[0];
            }

            return '](' . $externalUrl . '){:target="_blank"}';
        },
        $contents
    );

    if ($contents === null) {
        throw new RuntimeException('Unable to rewrite generated documentation: ' . $filePath);
    }

    $contents = preg_replace('/^> Automatically generated on.*\R?/m', '', $contents);
    $contents = html_entity_decode($contents, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    if ($filePath === $docsRoot . '/Home.md') {
        $contents = preg_replace('/^#### .*\R?/m', '', $contents);
    }

    if (str_ends_with($filePath, '/PhalconKit/Support/Helper/Str/SanitizeUTF8.md')) {
        $contents = preg_replace(
            '/^public __invoke\(string \$string, string \$invalidUtf8Regex = .*\R?/m',
            '',
            $contents
        );
    }

    $contents = preg_replace('/[\t ]+$/m', '', $contents);

    if ($contents === null || file_put_contents($filePath, $contents) === false) {
        throw new RuntimeException('Unable to write generated documentation: ' . $filePath);
    }
}
