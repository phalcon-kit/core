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

use Phalcon\Autoload\Loader;
use Phalcon\Mvc\ModelInterface;
use PhalconKit\Di\ServiceResolver;
use PhalconKit\Exception\InvalidArgumentException;
use PhalconKit\Exception\ServiceException;
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
     * Cached model column and mapped-attribute lookup tables.
     * @var array<class-string<ModelInterface>, array<string, bool>>
     */
    protected static array $modelColumnCache = [];
    
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
     * Get namespaces used when deriving a model class from the controller name.
     *
     * Explicit namespaces set through {@see setModelNamespaces()} win. When no
     * explicit map exists and the DI contains a `loader` service, the method
     * reads namespaces from Phalcon's autoloader. A registered but incompatible
     * loader is treated as a configuration error because otherwise model
     * inference would fail later with a less useful method-call error when PHP
     * assertions are disabled.
     *
     * @return array<string, string> Namespace-to-directory map used for model
     *     lookup.
     *
     * @throws ServiceException When the optional `loader` service is present
     *     but is not a Phalcon autoload loader.
     */
    public function getModelNamespaces(): array
    {
        if (!isset($this->modelNamespaces) && $this->di->has('loader')) {
            $loader = ServiceResolver::fromContainer(
                $this->di,
                'loader',
                Loader::class,
                context: 'controller model lookup'
            );
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
        
        if (class_exists($model) && is_subclass_of($model, ModelInterface::class)) {
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
     *
     * @throws ServiceException When no model can be resolved or the resolved
     *     class does not implement Phalcon's model contract.
     */
    public function loadModel(?string $modelName = null): ModelInterface
    {
        $modelName ??= $this->getModelName();
        if (!$modelName || !is_a($modelName, ModelInterface::class, true)) {
            throw new ServiceException(sprintf(
                'Unable to load controller model "%s": expected a class implementing "%s".',
                $modelName ?: '(none)',
                ModelInterface::class
            ));
        }

        return $this->modelsManager->load($modelName);
    }

    /**
     * Determine whether the configured model exposes a database column or mapped
     * model attribute.
     *
     * The helper prefers generated model `columnMap()` definitions, then falls
     * back to Phalcon's model metadata for models that do not declare a column
     * map. Metadata availability depends on the application's configured
     * metadata strategy and cache; if metadata cannot be read safely, the helper
     * returns false instead of turning an optional controller condition into a
     * runtime failure.
     *
     * @param string $column Database column name or mapped model attribute name.
     * @param string|null $modelName Optional model class; defaults to the
     *     current controller model. Non-model strings return false.
     *
     * @return bool True when the model column map contains the raw column or
     *     mapped attribute name.
     */
    public function modelHasColumn(string $column, ?string $modelName = null): bool
    {
        if ($column === '') {
            return false;
        }

        $modelName ??= $this->getModelName();
        if (!$modelName || !is_a($modelName, ModelInterface::class, true)) {
            return false;
        }
        /** @var class-string<ModelInterface> $modelName */

        if (!isset(self::$modelColumnCache[$modelName])) {
            $this->cacheModelColumns($modelName);
        }

        return self::$modelColumnCache[$modelName][$column] ?? false;
    }

    /**
     * Normalize and qualify a field reference with the model (alias) name.
     *
     * Responsibilities
     * ----------------
     * • Provides **syntactic normalization only** (no metadata validation).
     * • Safely formats identifiers into PHQL bracket notation: [Alias].[column].
     * • Preserves SQL/PHQL function or expression calls (e.g. RAND(), COUNT(id)).
     * • Supports optional ORDER BY direction (ASC | DESC).
     * • Rejects obvious injection vectors.
     *
     * Assumptions
     * -----------
     * • Column / alias allow-listing and validation occur upstream.
     * • This method must be deterministic and side-effect free.
     *
     * Supported inputs
     * ----------------
     * id                     → [Model].[id]
     * id desc                → [Model].[id] desc
     * alias.id               → [alias].[id]
     * COUNT(id)              → COUNT(id)
     * COUNT(id) DESC         → COUNT(id) desc
     * RAND()                 → RAND()
     * [alias].[id]           → unchanged
     *
     * Rejected inputs
     * ---------------
     * foo.bar.baz            → Invalid identifier
     * id; DROP TABLE         → Unsafe expression
     *
     * @param string      $field     Raw field string.
     * @param string|null $modelName Default alias if none provided.
     *
     * @return string Normalized field string.
     *
     * @throws InvalidArgumentException When identifier or expression is unsafe.
     */
    public function appendModelName(string $field, ?string $modelName = null): string
    {
        $modelName ??= $this->getModelName() ?? '';
        $field = trim($field);

        if ($field === '') {
            return $field;
        }

        /**
         * ---------------------------------------------------------------------
         * Security: reject obvious SQL injection patterns
         * ---------------------------------------------------------------------
         */
        if (preg_match('/;|--|\/\*/', $field)) {
            throw new InvalidArgumentException('Unsafe field expression.');
        }

        /**
         * ---------------------------------------------------------------------
         * Extract optional ORDER BY direction
         * ---------------------------------------------------------------------
         * Supports:
         *   "column DESC"
         *   "COUNT(id) asc"
         */
        $direction = '';
        $tokens = preg_split('/\s+/', $field);
        if ($tokens === false) {
            $tokens = [$field];
        }

        if (count($tokens) > 1) {
            $last = strtolower(end($tokens));
            if ($last === 'asc' || $last === 'desc') {
                $direction = ' ' . $last;
                array_pop($tokens);
                $field = implode(' ', $tokens);
            }
        }

        /**
         * ---------------------------------------------------------------------
         * If expression / function → passthrough
         * ---------------------------------------------------------------------
         * Covers:
         *   RAND()
         *   COUNT(id)
         *   LOWER(name)
         *   CASE WHEN ...
         */
        if (preg_match('/^[A-Z_][A-Z0-9_]*\s*\(/i', $field)) {
            return $field . $direction;
        }

        /**
         * Already fully qualified with brackets → passthrough
         */
        if (str_starts_with($field, '[') && str_ends_with($field, ']')) {
            return $field . $direction;
        }

        /**
         * ---------------------------------------------------------------------
         * Identifier normalization
         * ---------------------------------------------------------------------
         * Accepts:
         *   column
         *   alias.column
         */
        $segments = explode('.', $field);

        if (count($segments) > 2) {
            throw new InvalidArgumentException('Invalid identifier.');
        }

        /**
         * Validate identifier grammar (defensive — allow-list is upstream)
         */
        foreach ($segments as $segment) {
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $segment)) {
                throw new InvalidArgumentException('Invalid identifier segment.');
            }
        }

        if (count($segments) === 1) {
            return sprintf('[%s].[%s]%s', $modelName, $segments[0], $direction);
        }

        return sprintf('[%s].[%s]%s', $segments[0], $segments[1] ?? '', $direction);
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

    /**
     * @param class-string<ModelInterface> $modelName
     */
    protected function cacheModelColumns(string $modelName): void
    {
        $model = $this->loadModel($modelName);
        $lookup = [];

        $this->collectModelColumnMap($lookup, $this->getGeneratedModelColumnMap($model));

        try {
            $modelsMetadata = $model->getModelsMetaData();
            $this->collectModelColumnMap($lookup, $modelsMetadata->getColumnMap($model));
            $this->collectModelAttributes($lookup, $modelsMetadata->getAttributes($model));
        } catch (\Throwable) {
            // Metadata can be unavailable when a model has no initialized DI or
            // the configured adapter cannot read metadata for the model.
        }

        self::$modelColumnCache[$modelName] = $lookup;
    }

    /**
     * @return array<array-key, mixed>|null
     */
    protected function getGeneratedModelColumnMap(ModelInterface $model): ?array
    {
        if (!method_exists($model, 'columnMap')) {
            return null;
        }

        try {
            $columnMap = call_user_func([$model, 'columnMap']);
        } catch (\Throwable) {
            return null;
        }

        return is_array($columnMap) ? $columnMap : null;
    }

    /**
     * @param array<string, bool> $lookup
     * @param array<array-key, mixed>|null $columnMap
     */
    protected function collectModelColumnMap(array &$lookup, ?array $columnMap): void
    {
        if ($columnMap === null) {
            return;
        }

        foreach ($columnMap as $column => $attribute) {
            if (is_string($column)) {
                $lookup[$column] = true;
            }

            if (is_string($attribute)) {
                $lookup[$attribute] = true;
            }
        }
    }

    /**
     * @param array<string, bool> $lookup
     * @param array<array-key, mixed> $attributes
     */
    protected function collectModelAttributes(array &$lookup, array $attributes): void
    {
        foreach ($attributes as $attribute) {
            if (is_string($attribute)) {
                $lookup[$attribute] = true;
            }
        }
    }

    protected function isExpression(string $field): bool
    {
        // contains parentheses OR SQL keywords that imply expression
        return (bool) preg_match(
            '/\(|\)|\bCASE\b|\bWHEN\b|\bTHEN\b|\bEND\b|\bOVER\b/i',
            $field
        );
    }
}
