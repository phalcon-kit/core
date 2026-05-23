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

namespace PhalconKit\Mvc\Model\Interfaces;

// `__isset()` and `__unset()` are intentionally not part of the locale model
// contract until translated property presence semantics are defined.
interface LocaleInterface
{
    public function _(string $translateKey, array $placeholders = []): string;

    public function __call(string $method, array $arguments): mixed;
    
    public function __set(string $property, mixed $value): void;
    
    public function __get(string $property): mixed;
}
