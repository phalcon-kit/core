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

namespace PhalconKit\Support\Exposer;

/**
 * Contract for the mutable state carrier used by the Exposer engine.
 *
 * This interface deliberately encodes **strong invariants** required by the
 * exposure system. Implementations MUST respect them.
 *
 * Core invariants:
 * - Keys and context keys are **always strings**.
 * - The root path is represented by the empty string (`''`).
 * - `null` is never used to represent a key or path once inside the Builder.
 *
 * Rationale:
 * - Exposure rules are resolved via string-based dot-path matching.
 * - Parent traversal, child activation, and root deny rules depend on
 *   consistent string semantics.
 */
interface BuilderInterface
{
    /* -------------------------------------------------------------------------
     * Value & parent
     * ---------------------------------------------------------------------- */
    
    /**
     * Get the current value being evaluated.
     */
    public function getValue(): mixed;
    
    /**
     * Set the current value being evaluated.
     */
    public function setValue(mixed $value = null): void;
    
    /**
     * Get the parent value in the traversal graph.
     */
    public function getParent(): mixed;
    
    /**
     * Set the parent value in the traversal graph.
     */
    public function setParent(mixed $parent = null): void;
    
    /* -------------------------------------------------------------------------
     * Columns (rules)
     * ---------------------------------------------------------------------- */
    
    /**
     * Get the flattened column rules map (dot-path => rule).
     *
     * @return array<array-key, mixed>|null
     */
    public function getColumns(): ?array;
    
    /**
     * Set the flattened column rules map.
     *
     * @param array<array-key, mixed>|null $columns
     */
    public function setColumns(?array $columns = null): void;
    
    /* -------------------------------------------------------------------------
     * Field (legacy / informational)
     * ---------------------------------------------------------------------- */
    
    /**
     * Get the logical field name (legacy / informational).
     */
    public function getField(): ?string;
    
    /**
     * Set the logical field name.
     */
    public function setField(?string $field = null): void;
    
    /* -------------------------------------------------------------------------
     * Key & context
     * ---------------------------------------------------------------------- */
    
    /**
     * Get the current local key.
     *
     * MUST always return a string.
     * Root is represented as the empty string (`''`).
     */
    public function getKey(): string;
    
    /**
     * Set the current local key.
     *
     * Implementations MUST normalize the key and collapse invalid values to `''`.
     */
    public function setKey(?string $key = null): void;
    
    /**
     * Get the current context key (dot-path prefix).
     *
     * MUST always return a string.
     * Root context is represented as the empty string (`''`).
     */
    public function getContextKey(): string;
    
    /**
     * Set the current context key.
     *
     * Implementations MUST normalize the key and collapse invalid values to `''`.
     */
    public function setContextKey(?string $contextKey = null): void;
    
    /* -------------------------------------------------------------------------
     * Exposure flags
     * ---------------------------------------------------------------------- */
    
    /**
     * Whether the current node is exposed.
     */
    public function getExpose(): bool;
    
    /**
     * Set whether the current node is exposed.
     */
    public function setExpose(bool $expose): void;
    
    /**
     * Whether underscore-prefixed keys are allowed.
     */
    public function getProtected(): bool;
    
    /**
     * Set whether underscore-prefixed keys are allowed.
     */
    public function setProtected(bool $protected): void;
    
    /* -------------------------------------------------------------------------
     * Derived keys
     * ---------------------------------------------------------------------- */
    
    /**
     * Get the fully-qualified dot-path key for the current node.
     *
     * MUST always return a string.
     * Root path is represented as the empty string (`''`).
     */
    public function getFullKey(): string;
}
