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

namespace PhalconKit\Mvc\Model\Traits\Abstracts;

trait AbstractEntity
{
    /**
     * Read a raw model attribute through Phalcon's native entity API.
     *
     * Model traits use this dependency when they need the stored value without
     * going through magic property access. The native extension currently keeps
     * this method untyped at runtime, so the abstract dependency remains
     * signature-compatible while documenting the same mixed-value contract as
     * the patched Phalcon model stub.
     *
     * @param string $attribute Model attribute name.
     * @return mixed Current raw attribute value.
     */
    abstract public function readAttribute(string $attribute);
    
    /**
     * Write a raw model attribute through Phalcon's native entity API.
     *
     * Phalcon's runtime extension currently exposes this method without a
     * native return type, so the abstract dependency intentionally stays
     * untyped for compatibility with both the extension and patched IDE stubs.
     *
     * @param string $attribute Model attribute name.
     * @param mixed $value Value to assign to the raw attribute.
     * @return void
     */
    abstract public function writeAttribute(string $attribute, $value);
}
