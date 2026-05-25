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
 * Mutable state container used by {@see Exposer} during exposure traversal.
 *
 * This class is deliberately simple and strict. It enforces a **single invariant**
 * that the entire exposure system relies on:
 *
 * **All keys are strings. The root path is represented by the empty string (`''`).**
 *
 * `null` is never used to represent keys or paths once inside the Builder.
 *
 * Responsibilities:
 * - Hold the current traversal state (value, parent, key, context).
 * - Hold global exposure configuration (columns, protected flag).
 * - Be reused across recursion to avoid object churn.
 *
 * Non-responsibilities:
 * - No exposure logic.
 * - No rule resolution.
 * - No traversal decisions.
 *
 * All business logic lives in {@see Exposer}.
 */
class Builder implements BuilderInterface
{
    /**
     * Current value being evaluated.
     */
    private mixed $value = null;
    
    /**
     * Parent value in the traversal graph.
     */
    private mixed $parent = null;
    
    /**
     * Flattened column rules (dot-path => rule).
     *
     * @var array<array-key, mixed>|null
     */
    private ?array $columns = null;
    
    /**
     * Optional logical field name (legacy / informational).
     * Not used by the Exposer core logic.
     */
    private ?string $field = null;
    
    /**
     * Current local key (single segment).
     *
     * Normalized and guaranteed to be a string.
     * Root is represented as ''.
     */
    private string $key = '';
    
    /**
     * Current context key (dot-path prefix).
     *
     * Normalized and guaranteed to be a string.
     * Root context is represented as ''.
     */
    private string $contextKey = '';
    
    /**
     * Whether the current node is exposed.
     */
    private bool $expose = true;
    
    /**
     * Whether underscore-prefixed keys are allowed.
     */
    private bool $protected = false;
    
    /* -------------------------------------------------------------------------
     * Value & parent
     * ---------------------------------------------------------------------- */
    
    #[\Override]
    public function getValue(): mixed
    {
        return $this->value;
    }
    
    #[\Override]
    public function setValue(mixed $value = null): void
    {
        $this->value = $value;
    }
    
    #[\Override]
    public function getParent(): mixed
    {
        return $this->parent;
    }
    
    #[\Override]
    public function setParent(mixed $parent = null): void
    {
        $this->parent = $parent;
    }
    
    /* -------------------------------------------------------------------------
     * Key & context
     * ---------------------------------------------------------------------- */
    
    /**
     * Return the current local key.
     *
     * Guaranteed to be a string.
     * Root is represented as ''.
     */
    #[\Override]
    public function getKey(): string
    {
        return $this->key;
    }
    
    /**
     * Set the current local key.
     *
     * Any input is normalized via {@see processKey()}.
     */
    #[\Override]
    public function setKey(?string $key = null): void
    {
        $this->key = self::processKey($key);
    }
    
    /**
     * Return the current context key (dot-path prefix).
     *
     * Guaranteed to be a string.
     * Root context is represented as ''.
     */
    #[\Override]
    public function getContextKey(): string
    {
        return $this->contextKey;
    }
    
    /**
     * Set the current context key.
     *
     * Any input is normalized via {@see processKey()}.
     */
    #[\Override]
    public function setContextKey(?string $contextKey = null): void
    {
        $this->contextKey = self::processKey($contextKey);
    }
    
    /* -------------------------------------------------------------------------
     * Field (legacy / informational)
     * ---------------------------------------------------------------------- */
    
    #[\Override]
    public function getField(): ?string
    {
        return $this->field;
    }
    
    #[\Override]
    public function setField(?string $field = null): void
    {
        $this->field = $field;
    }
    
    /* -------------------------------------------------------------------------
     * Columns (rules)
     * ---------------------------------------------------------------------- */
    
    /**
     * Return the flattened exposure rule map.
     *
     * @return array<array-key, mixed>|null
     */
    #[\Override]
    public function getColumns(): ?array
    {
        return $this->columns;
    }
    
    /**
     * Replace the flattened exposure rule map.
     *
     * @param array<array-key, mixed>|null $columns
     */
    #[\Override]
    public function setColumns(?array $columns = null): void
    {
        $this->columns = $columns;
    }
    
    /* -------------------------------------------------------------------------
     * Exposure flags
     * ---------------------------------------------------------------------- */
    
    #[\Override]
    public function getExpose(): bool
    {
        return $this->expose;
    }
    
    #[\Override]
    public function setExpose(bool $expose): void
    {
        $this->expose = $expose;
    }
    
    #[\Override]
    public function getProtected(): bool
    {
        return $this->protected;
    }
    
    #[\Override]
    public function setProtected(bool $protected): void
    {
        $this->protected = $protected;
    }
    
    /* -------------------------------------------------------------------------
     * Derived keys
     * ---------------------------------------------------------------------- */
    
    /**
     * Return the fully-qualified dot-path key for the current node.
     *
     * Invariants:
     * - Always returns a string.
     * - Root path is ''.
     *
     * Semantics:
     * - key='' and context=''      → ''
     * - key='' and context!=''     → context
     * - key!='' and context=''     → key
     * - key!='' and context!=''    → context.key
     */
    #[\Override]
    public function getFullKey(): string
    {
        if ($this->key === '' && $this->contextKey === '') {
            return '';
        }
        
        if ($this->key === '') {
            return $this->contextKey;
        }
        
        if ($this->contextKey === '') {
            return $this->key;
        }
        
        return $this->contextKey . '.' . $this->key;
    }
    
    /* -------------------------------------------------------------------------
     * Utilities
     * ---------------------------------------------------------------------- */
    
    /**
     * Normalize a key or context segment.
     *
     * Rules:
     * - null, empty string, or integer-like strings collapse to '' (root).
     * - Whitespace becomes dots.
     * - Multiple dots collapse into one.
     * - Lowercased and trimmed of leading/trailing dots.
     *
     * This guarantees:
     * - Stable dot-path generation.
     * - No accidental numeric keys.
     * - A single, canonical representation for root.
     *
     * @param string|null $key Local key or context key.
     *
     * @return string Canonical dot-path segment, or empty string for root.
     */
    public static function processKey(?string $key = null): string
    {
        if (
            $key === null
            || $key === ''
            || filter_var($key, FILTER_VALIDATE_INT) !== false
        ) {
            return '';
        }
        
        $key = preg_replace('/\s+/', '.', $key) ?? '';
        $key = preg_replace('/\.+/', '.', $key) ?? '';
        
        return trim(mb_strtolower($key), '.');
    }
}
