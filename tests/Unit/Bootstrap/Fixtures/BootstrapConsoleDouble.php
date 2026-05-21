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

namespace PhalconKit\Tests\Unit\Bootstrap\Fixtures;

use PhalconKit\Cli\Console;

class BootstrapConsoleDouble extends Console
{
    public ?array $handledArguments = null;

    public bool $throw = false;

    public function handle(?array $arguments = [])
    {
        $this->handledArguments = $arguments;

        if ($this->throw) {
            throw new QuietConsoleException('');
        }

        echo 'console-content';
    }
}
