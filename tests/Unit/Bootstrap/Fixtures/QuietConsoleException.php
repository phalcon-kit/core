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

class QuietConsoleException extends \RuntimeException
{
    public function __toString(): string
    {
        return $this->getMessage();
    }
}
