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

final class AuthActionsViewDouble
{
    /**
     * @var array<string, mixed>
     */
    private array $vars = [];

    /**
     * Merge action variables into the view bag like Phalcon's view service.
     *
     * @param array<string, mixed> $vars
     */
    public function setVars(array $vars): void
    {
        $this->vars = array_merge($this->vars, $vars);
    }

    /**
     * Return one merged view variable.
     */
    public function getVar(string $key): mixed
    {
        return $this->vars[$key] ?? null;
    }
}
