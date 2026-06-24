<?php

declare(strict_types=1);

/**
 * Local-only smoke page for visually inspecting PhalconKit debug HTML.
 *
 * Run it from the repository root with:
 *
 *     php -S 127.0.0.1:8976 tests/Fixtures/debug-error-page.php
 *
 * Then open http://127.0.0.1:8976/ in a browser.
 */

use PhalconKit\Support\Debug;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$remoteAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

if (!in_array($remoteAddress, ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'This debug smoke page is only available from localhost.';
    return;
}

$debug = new Debug();

set_exception_handler(static function (\Throwable $exception) use ($debug): void {
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }

    echo $debug->renderHtml($exception);
});

/**
 * @param array{class: class-string, message: string} $payload
 */
$levelTwo = static function (array $payload): void {
    throw new \RuntimeException(sprintf(
        'PhalconKit debug error page smoke test for %s. %s',
        $payload['class'],
        $payload['message']
    ));
};

$levelOne = static function () use ($levelTwo): void {
    $levelTwo([
        'class' => Debug::class,
        'message' => 'This synthetic payload makes the debug argument table visible.',
    ]);
};

$levelOne();
