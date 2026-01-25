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

namespace PhalconKit\Mvc\Controller\Traits;

use Phalcon\Mvc\ModelInterface;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractInjectable;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractModel;

trait Model
{
    use AbstractModel;
    
    use AbstractInjectable;
    
    /**
     * The name of the model.
     * @var ?string
     */
    protected ?string $modelName = null;
    
    /**
     * The namespaces for the model lookup.
     * @var string[]
     */
    protected ?array $modelNamespaces = null;
    
    /**
     * Retrieves the name of the model associated with the controller.
     *
     * @return string|null The name of the model associated with the controller, or null if not found.
     */
    public function getModelName(): ?string
    {
        if (!isset($this->modelName)) {
            $this->modelName = $this->getModelNameFromController();
        }
        
        return $this->modelName;
    }
    
    /**
     * Sets the name of the model to be used.
     *
     * @param string|null $modelName The name of the model to be set.
     *
     * @return void
     */
    public function setModelName(?string $modelName): void
    {
        $this->modelName = $modelName;
    }
    
    /**
     * Gets the namespaces used for the model lookup.
     * If no model namespace is set, the namespaces defined in the loader will be returned.
     *
     * @return array The namespaces used for the model lookup.
     */
    public function getModelNamespaces(): array
    {
        if (!isset($this->modelNamespaces) && $this->di->has('loader')) {
            $loader = $this->di->get('loader');
            assert($loader instanceof \Phalcon\Autoload\Loader);
            $this->modelNamespaces = $loader->getNamespaces();
        }
        
        return $this->modelNamespaces ?? [];
    }
    
    /**
     * Set the namespaces for the models.
     *
     * @param array|null $modelNamespaces The array of namespaces for the models.
     *
     * @return void
     */
    public function setModelNamespaces(?array $modelNamespaces): void
    {
        $this->modelNamespaces = $modelNamespaces;
    }
    
    /**
     * Retrieves the model name from the controller by following certain naming conventions.
     *
     * @param array|null $namespaces Optional. An array of namespaces to search for the model. Default is null and will use $this->getModelNamespaces().
     * @param string $needle Optional. The keyword to search for in the namespace. Default is 'Models'.
     * 
     * @return string|null The model name if found, otherwise null.
     */
    public function getModelNameFromController(?array $namespaces = null, string $needle = 'Models'): ?string
    {
        $model = ucfirst(
            $this->helper->camelize(
                $this->helper->uncamelize(
                    $this->getControllerName()
                )
            )
        );
        
        if (class_exists($model)) {
            return $model;
        }
        
        $namespaces ??= $this->getModelNamespaces();
        foreach ($namespaces as $namespace => $path) {
            if (str_contains($namespace, $needle)) {
                $possibleModel = $namespace . $model;
                if (class_exists($possibleModel) && is_subclass_of($possibleModel, ModelInterface::class)) {
                    return $possibleModel;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Returns the name of the controller.
     *
     * If the controller name is not set in the dispatcher, it extracts the controller name from the class name
     * of the current instance.
     *
     * @return string The name of the controller.
     */
    public function getControllerName(): string
    {
        return $this->dispatcher->getControllerName()
            ?: substr(basename(str_replace('\\', '/', get_class($this))), 0, -10);
    }
    
    /**
     * Loads a model by its name using the modelsManager.
     *
     * @param string|null $modelName The name of the model to load. Default is null and will use $this->getModelName().
     *
     * @return ModelInterface The loaded model.
     */
    public function loadModel(?string $modelName = null): ModelInterface
    {
        $modelName ??= $this->getModelName() ?? '';
        return $this->modelsManager->load($modelName);
    }
    
    /**
     * Appends the model name to the specified field string, if not already present.
     * Example: field -> [Alias].[field]
     *
     * @param string $field The field string to append the model name to.
     * @param string|null $modelName The name of the model to append. If null, the default model name will be used.
     *
     * @return string The modified field string with the model name appended.
     */
    public function appendModelName(string $field, ?string $modelName = null): string
    {
        $modelName ??= $this->getModelName() ?? '';
        
        if (empty($field)) {
            return $field;
        }

        // fields with brackets are ignored
        if (str_starts_with($field, '[') && str_ends_with($field, ']')) {
            return $field;
        }
        
        // Add the current model name by default
        $explode = explode(' ', $field);
        if (!strpos($field, '.') !== false) {
            $field = trim('[' . $modelName . '].[' . array_shift($explode) . '] ' . implode(' ', $explode));
        }
        else if (!str_contains($field, ']') && !str_contains($field, '[')) {
            $field = trim('[' . implode('].[', explode('.', array_shift($explode))) . ']' . implode(' ', $explode));
        }
        
        return $field;
    }
    
    /**
     * Retrieves the primary key attributes for a given model.
     *
     * @param string|null $modelName The name of the model to retrieve primary key attributes for. Default is null and will use $this->getModelName().
     *
     * @return array An array of primary key attributes for the model. Returns an empty array if no model name is specified.
     */
    public function getPrimaryKeyAttributes(?string $modelName = null): array
    {
        $modelName ??= $this->getModelName() ?? '';
        if (empty($modelName)) {
            return [];
        }
        
        return $this->modelsMetadata->getPrimaryKeyAttributes($this->loadModel($modelName));
    }
}
