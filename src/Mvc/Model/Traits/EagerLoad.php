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

namespace PhalconKit\Mvc\Model\Traits;

use JetBrains\PhpStorm\Deprecated;
use Phalcon\Mvc\Model\Resultset;
use Phalcon\Mvc\Model\ResultsetInterface;
use Phalcon\Mvc\ModelInterface;
use PhalconKit\Exception\LogicException;
use PhalconKit\Exception\RuntimeException;
use PhalconKit\Mvc\Model\EagerLoading\Loader;

trait EagerLoad
{
    /**
     * Run Phalcon's native static finder for the model using this trait.
     *
     * The explicit `mixed` parameter mirrors PhalconKit's patched
     * `phalcon/ide-stubs` contract for `Phalcon\Mvc\Model::find()`. Keeping the
     * abstract dependency in sync with the upstream model API prevents static
     * analyzers and downstream projects from seeing this trait as a narrower,
     * incompatible declaration.
     *
     * Eager loading requires an iterable Phalcon resultset because
     * `findWith()` delegates the returned records to the eager-loading loader.
     *
     * @param mixed $parameters Native Phalcon find parameters, usually an
     *     array, string, integer primary key, or null.
     * @return ResultsetInterface Resultset returned by the concrete model
     *     implementation.
     */
    abstract public static function find(mixed $parameters = null): ResultsetInterface;
    
    /**
     * Run Phalcon's native static first-record finder for the model using this trait.
     *
     * Phalcon can return a model instance, a row, false, null, or another value
     * depending on hydration and extension behavior, so this dependency keeps
     * the same broad `mixed` return declared by the patched Phalcon stubs.
     * `findFirstWith()` narrows that value at runtime and only eager-loads when
     * a real model instance is returned.
     *
     * @param mixed $parameters Native Phalcon find-first parameters, usually an
     *     array, string, integer primary key, or null.
     * @return mixed Native result returned by the concrete model implementation.
     */
    abstract public static function findFirst(mixed $parameters = null): mixed;
    
    /**
     * Example:
     *
     * ```php
     * $limit = 100;
     * $offset = max(0, $this->request->getQuery('page', 'int') - 1) * $limit;
     *
     * $manufacturers = Manufacturer::with('Robots.Parts', [
     *     'limit' => [$limit, $offset]
     * ]);
     *
     * foreach ($manufacturers as $manufacturer) {
     *     foreach ($manufacturer->robots as $robot) {
     *         foreach ($robot->parts as $part) { ... }
     *     }
     * }
     * ```
     *
     * @param array ...$arguments
     */
    public static function findWith(array ...$arguments): array
    {
        $parameters = static::getParametersFromArguments($arguments);
        $list = static::find($parameters);
        
        if ($list instanceof Resultset && $list->count()) {
            return Loader::fromResultset($list, ...$arguments);
        }
        
        return [];
    }
    
    /**
     * Same as EagerLoadingTrait::findWith() for a single record
     *
     * @param array ...$arguments
     * @return ?ModelInterface
     */
    public static function findFirstWith(array ...$arguments): ?ModelInterface
    {
        $parameters = static::getParametersFromArguments($arguments);
        $entity = static::findFirst($parameters);
        
        if ($entity instanceof ModelInterface) {
            return Loader::fromModel($entity, ...$arguments);
        }
        
        return null;
    }
    
    /**
     * @deprecated
     * @link static::findWith()
     * @param array ...$arguments
     * @return array
     */
    #[Deprecated(
        reason: 'since Phalcon Kit 1.0, use findWith() instead',
        replacement: '%class%::findWith(%parametersList%)'
    )]
    public static function with(array ...$arguments): array
    {
        return static::findWith(...$arguments);
    }
    
    /**
     * @deprecated
     * @link static::findFirstWith()
     * @param array ...$arguments
     * @return ?ModelInterface
     */
    #[Deprecated(
        reason: 'since Phalcon Kit 1.0, use findFirstWith() instead',
        replacement: '%class%::findFirstWith(%parametersList%)'
    )]
    public static function firstWith(array ...$arguments): ?ModelInterface
    {
        return static::findFirstWith(...$arguments);
    }
    
    /**
     * Dynamically handles static method calls for the class, forwarding them to
     * appropriate internal methods based on the method name patterns.
     *
     * The method provides a mechanism to resolve calls like "findFirstWithBy..."/"firstWithBy..."
     * and "findWithBy..."/"withBy..." to their corresponding mapped operations.
     *
     * The static magic method keeps the existing PhalconKit `findWithBy*`
     * surface. Moving this to native `missingMethods()` remains a compatibility
     * decision because it would change where dynamic calls are intercepted.
     *
     * @param string $method The name of the static method being called.
     * @param array $arguments An array of arguments passed to the static method.
     * @return array|ModelInterface|null Returns the result of the forwarded operation, which may be
     *                                   an array, an implementation of ModelInterface, or null.
     */
    public static function __callStatic(string $method, array $arguments = []): array|null|ModelInterface
    {
        // Single - FindFirstBy...
        if (str_starts_with($method, 'findFirstWithBy') || str_starts_with($method, 'firstWithBy')) {
            $forwardMethod = str_replace(['findFirstWithBy', 'firstWithBy'], 'findFirstBy', $method);
            return static::findFirstWithBy($forwardMethod, $arguments);
        }
        
        // List - FindWithBy...
        elseif (str_starts_with($method, 'findWithBy') || str_starts_with($method, 'withBy')) {
            $forwardMethod = str_replace(['findWithBy', 'withBy'], 'findBy', $method);
            return static::findWithBy($forwardMethod, $arguments);
        }
    
        return parent::$method(...$arguments);
    }
    
    /**
     * Call native Phalcon FindFirstBy function then eager load relationships from the model
     */
    protected static function findFirstWithBy(string $forwardMethod, array $arguments): ?ModelInterface
    {
        $parameters = static::getParametersFromArguments($arguments);
        $entity = parent::$forwardMethod($parameters);
    
        if ($entity instanceof ModelInterface) {
            return Loader::fromModel($entity, ...$arguments);
        }
    
        return null;
    }
    
    /**
     * Call native Phalcon findBy function then eager load relationships from the resultset
     */
    protected static function findWithBy(string $forwardMethod, array $arguments): ?array
    {
        $parameters = static::getParametersFromArguments($arguments);
        $list = parent::$forwardMethod($parameters);

        if (!$list instanceof ResultsetInterface) {
            throw new RuntimeException(sprintf(
                'Expected "%s::%s()" to return "%s" for eager loading; got "%s".',
                static::class,
                $forwardMethod,
                ResultsetInterface::class,
                get_debug_type($list)
            ));
        }
        
        if (is_countable($list) && $list->count()) {
            return Loader::fromResultset($list, ...$arguments);
        }
    
        return [];
    }
    
    /**
     * Example:
     *
     * ```php
     * $manufacturer = Manufacturer::findFirstById(51);
     *
     * $manufacturer->load('Robots.Parts');
     *
     * foreach ($manufacturer->robots as $robot) {
     *    foreach ($robot->parts as $part) { ... }
     * }
     * ```
     *
     * @param array ...$arguments
     * @return ?ModelInterface
     */
    public function load(array ...$arguments): ?ModelInterface
    {
        if (!$this instanceof ModelInterface) {
            throw new LogicException(sprintf(
                'Eager-loading model helpers require the trait host to implement "%s"; got "%s".',
                ModelInterface::class,
                get_debug_type($this)
            ));
        }

        return Loader::fromModel($this, ...$arguments);
    }
    
    /**
     * Get the query parameters from a list of arguments
     * @param array $arguments
     * @return mixed
     */
    public static function getParametersFromArguments(array &$arguments): mixed
    {
        $parameters = null;
        
        if (!empty($arguments)) {
            $numArgs = count($arguments);
            $lastArg = $numArgs - 1;
            
            if ($numArgs >= 2) {
                $parameters = $arguments[$lastArg];
                unset($arguments[$lastArg]);
                
                if (isset($parameters['columns'])) {
                    // the first columns should be * so we can have the main model and all the necessary fields for eager loading
                    if ($parameters['columns'][0] !== '*') {
                        array_unshift($parameters['columns'], '*');
                    }
                }
            }
        }
        
        return $parameters;
    }
}
