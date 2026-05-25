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

namespace PhalconKit\Tests\Unit\Mvc\Controller\Traits\Fixtures;

final class CountActionViewDouble
{
    /**
     * @var array<string, mixed>
     */
    private array $vars = [];

    /**
     * Set one response view variable.
     */
    public function setVar(string $key, mixed $value): void
    {
        $this->vars[$key] = $value;
    }

    /**
     * Return one response view variable.
     */
    public function getVar(string $key): mixed
    {
        return $this->vars[$key] ?? null;
    }
}
