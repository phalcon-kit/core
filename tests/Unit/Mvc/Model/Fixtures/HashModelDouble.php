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

namespace PhalconKit\Tests\Unit\Mvc\Model\Fixtures;

use Phalcon\Di\DiInterface;
use PhalconKit\Mvc\Model\Traits\Hash;

class HashModelDouble
{
    use Hash;

    public function __construct(private DiInterface $di)
    {
    }

    public function setDI(DiInterface $di): void
    {
        $this->di = $di;
    }

    public function getDI(): DiInterface
    {
        return $this->di;
    }
}
