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

namespace PhalconKit\Tests\Unit\Bootstrap;

use PhalconKit\Tests\Unit\AbstractUnit;

/**
 * Keep the generated environment and service references in step with Config.
 */
class ConfigReferenceTest extends AbstractUnit
{
    public function testGeneratedReferencePagesAreCurrent(): void
    {
        $root = dirname(__DIR__, 3);
        require_once $root . '/bin/generate-reference.php';

        foreach (\PhalconKit\Bin\Reference\render($root) as $path => $content) {
            $this->assertFileExists($root . '/' . $path);
            $this->assertSame(
                $content,
                file_get_contents($root . '/' . $path),
                $path . ' is stale; run php bin/generate-reference.php.'
            );
        }
    }

    public function testEveryDefaultConfigVariableIsDocumented(): void
    {
        $root = dirname(__DIR__, 3);
        require_once $root . '/bin/generate-reference.php';
        $source = (string) file_get_contents($root . '/src/Bootstrap/Config.php');
        // Ignore commented-out lines; every other Env::get() call must be parsed.
        $active = (string) preg_replace('#^\s*//.*$#m', '', $source);
        preg_match_all("/Env::get\\('([A-Z0-9_]+)'/", $active, $calls);

        $parsed = \PhalconKit\Bin\Reference\parse($source);

        $this->assertCount(
            count($calls[1]),
            array_merge($parsed['entries'], $parsed['constants'])
        );
    }
}
