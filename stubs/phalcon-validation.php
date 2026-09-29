<?php

declare(strict_types=1);

namespace Phalcon\Filter\Validation;

/**
 * Correct the native template property's visibility for Psalm 6.19.x.
 *
 * Psalm's reflection loader records read visibility but leaves set visibility
 * public for extension properties. The native Phalcon 5.22 property and its
 * published IDE stub are protected. Declare only that property here so Psalm
 * keeps the other members from native reflection. Never load this file at runtime.
 */
abstract class AbstractValidator
{
    /**
     * Default validation message, overridden by concrete validators.
     *
     * @var string|null
     */
    protected $template = null;
}
