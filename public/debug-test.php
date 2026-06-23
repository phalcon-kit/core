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

use PhalconKit\Support\Debug;

require dirname(__DIR__) . '/vendor/autoload.php';

$remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

if (!in_array($remoteAddr, ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "This debug test page is available from localhost only.\n";

    return;
}

function phalconKitDebugTestLeaf(): never
{
    throw new InvalidArgumentException('Synthetic previous exception for the PhalconKit debug page.');
}

function phalconKitDebugTestEntry(): never
{
    try {
        phalconKitDebugTestLeaf();
    }
    catch (Throwable $throwable) {
        throw new RuntimeException('Synthetic PhalconKit debug output test exception.', 0, $throwable);
    }
}

$debug = new Debug();
$debug
    ->setUri('./')
    ->setShowFileFragment(true)
    ->debugVar([
        'purpose' => 'Manual PhalconKit debug renderer smoke test',
        'phalcon' => phpversion('phalcon') ?: 'not installed',
        'php' => PHP_VERSION,
        'request' => [
            'uri' => $_SERVER['REQUEST_URI'] ?? null,
            'remoteAddr' => $remoteAddr,
        ],
    ]);

try {
    phalconKitDebugTestEntry();
}
catch (Throwable $throwable) {
    http_response_code(500);
    echo $debug->renderHtml($throwable);
}
