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

namespace PhalconKit\Mvc\Controller\Traits\Interfaces;

/**
 * Contract for resolving the model managed by a REST controller.
 */
interface ModelInterface
{
    /**
     * Return the explicit model class name, when one is configured.
     *
     * @return class-string<\Phalcon\Mvc\ModelInterface>|null
     */
    public function getModelName(): ?string;
    
    /**
     * Set the explicit model class name for this controller.
     *
     * @param class-string<\Phalcon\Mvc\ModelInterface>|null $modelName
     */
    public function setModelName(?string $modelName): void;
    
    /**
     * Return namespace prefixes searched when deriving a model from a controller.
     *
     * @return list<string>
     */
    public function getModelNamespaces(): array;
    
    /**
     * Replace namespace prefixes searched when deriving a model from a controller.
     *
     * @param list<string>|null $modelNamespaces
     */
    public function setModelNamespaces(?array $modelNamespaces): void;
    
    /**
     * Infer a model class name from the controller namespace/class.
     *
     * @param list<string>|null $namespaces Optional namespace prefixes.
     * @param string $needle Namespace segment to replace with `Models`.
     *
     * @return class-string<\Phalcon\Mvc\ModelInterface>|null
     */
    public function getModelNameFromController(?array $namespaces = null, string $needle = 'Models'): ?string;
    
    /**
     * Return the current controller route name.
     */
    public function getControllerName(): string;
    
    /**
     * Resolve and instantiate the configured model class.
     *
     * @param class-string<\Phalcon\Mvc\ModelInterface>|null $modelName Optional
     *     override.
     */
    public function loadModel(?string $modelName = null): \Phalcon\Mvc\ModelInterface;

    /**
     * Determine whether the configured model exposes a raw database column or a
     * mapped model attribute name.
     *
     * Implementations should use generated `columnMap()` definitions or Phalcon
     * model metadata rather than issuing application-level data queries. Models
     * without generated maps may depend on the application's configured metadata
     * strategy and cache.
     *
     * @param string $column Database column or mapped model attribute.
     * @param class-string<\Phalcon\Mvc\ModelInterface>|null $modelName Optional
     *     model override; defaults to the current controller model.
     */
    public function modelHasColumn(string $column, ?string $modelName = null): bool;
}
