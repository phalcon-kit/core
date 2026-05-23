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

use PhalconKit\Config\ConfigInterface;
use PhalconKit\Di\ServiceResolver;
use PhalconKit\Exception\ServiceException;
use Phalcon\Mvc\Model\Query\BuilderInterface;
use Phalcon\Mvc\Model\QueryInterface;
use Phalcon\Mvc\Model\ManagerInterface;
use Phalcon\Mvc\Model\ResultsetInterface;
use PhalconKit\Mvc\Model\Traits\Abstracts\AbstractModelsManager;

/**
 * Provides static query helpers for data-retention lifecycle tasks.
 *
 * Lifecycle policies are read from `dataLifeCycle.models` and
 * `dataLifeCycle.policies` in the default PhalconKit config service. The trait
 * is static because CLI retention tasks call model classes directly when
 * building delete/obfuscation queries.
 *
 * @todo this should be moved into models manager
 */
trait LifeCycle
{
    use AbstractModelsManager;
    
    /**
     * Apply safety defaults to a lifecycle query builder.
     *
     * A model with no resolved policy query should never accidentally match all
     * records. When parameters are empty, the builder receives a `false`
     * condition and empty bind arrays so the resulting query is intentionally
     * empty.
     *
     * @param BuilderInterface $builder Builder that will be executed by the
     *     lifecycle task.
     * @param array<string, mixed>|null $parameters Policy query parameters
     *     resolved from config or supplied by the caller.
     */
    public static function prepareLifeCycleQuery(BuilderInterface $builder, ?array $parameters = null): void
    {
        // data life cycle policy must be defined
        if (empty($parameters)) {
            $builder->where('false');
            $builder->setBindParams([]);
            $builder->setBindTypes([]);
        }
    }
    /**
     * Return the lifecycle policy configured for the current model class.
     *
     * The config maps model class names to policy names under
     * `dataLifeCycle.models`; the policy payload is then read from
     * `dataLifeCycle.policies`. Missing mappings return an empty policy.
     *
     * @return array<string, mixed> Policy payload for the calling model class.
     * @throws ServiceException When the default DI or config service cannot be
     *     resolved through the PhalconKit DI contract.
     */
    public static function getLifeCyclePolicy(): array
    {
        $config = ServiceResolver::fromDefault(
            'config',
            ConfigInterface::class,
            context: 'model lifecycle helpers'
        );
        $dataLifeCycleConfig = $config->pathToArray('dataLifeCycle') ?? [];
        $models = $dataLifeCycleConfig['models'] ?? [];
        $policies = $dataLifeCycleConfig['policies'] ?? [];
        $policyName = $models[static::class] ?? null;
        return isset($policyName) ? $policies[$policyName] ?? [] : [];
    }
    /**
     * Return only the lifecycle query portion of the configured policy.
     *
     * @return array<string, mixed>|null Query definition accepted by Phalcon's
     *     model query builder, or null when no policy query is configured.
     * @throws ServiceException When the lifecycle policy cannot be resolved.
     */
    public static function getLifeCyclePolicyQuery(): ?array
    {
        return self::getLifeCyclePolicy()['query'] ?? null;
    }
    
    /**
     * Build the executable lifecycle query for the current model class.
     *
     * Callers may pass explicit query parameters or a preconfigured builder for
     * tests and custom lifecycle workflows. When both are omitted, the
     * configured policy query is used.
     *
     * @param array<string, mixed>|null $parameters Query parameters to apply.
     * @param BuilderInterface|null $builder Optional builder override.
     * @return QueryInterface Executable query for lifecycle processing.
     * @throws ServiceException When the default DI or models manager service
     *     cannot be resolved through the PhalconKit DI contract.
     */
    public static function getLifeCycleQuery(?array $parameters = null, ?BuilderInterface $builder = null): QueryInterface
    {
        $parameters ??= self::getLifeCyclePolicyQuery();
        $builder ??= self::getBuilder($parameters);
        
        self::prepareLifeCycleQuery($builder, $parameters);
        
        return $builder->getQuery();
    }
    
    /**
     * Create a lifecycle query builder for the current model class.
     *
     * The builder is initialized from the provided parameters and forced to use
     * the calling model class as its `from` model. A top-level `limit` parameter
     * is applied explicitly because Phalcon's builder parameters do not always
     * preserve that value when lifecycle tasks construct custom arrays.
     *
     * @param array<string, mixed>|null $parameters Query-builder parameters.
     * @return BuilderInterface Builder scoped to the calling model class.
     * @throws ServiceException When the default DI or models manager service
     *     cannot be resolved through the PhalconKit DI contract.
     */
    public static function getBuilder(?array $parameters = null): BuilderInterface
    {
        $modelsManager = ServiceResolver::fromDefault(
            'modelsManager',
            ManagerInterface::class,
            context: 'model lifecycle helpers'
        );
        
        $builder = $modelsManager->createBuilder($parameters);
        $builder->from(get_called_class());
        
        if (isset($parameters['limit'])) {
            $builder->limit($parameters['limit']);
        }
        
        return $builder;
    }
    
    /**
     * Execute the lifecycle query and return matching records.
     *
     * If a resultset is returned and the policy parameters include a
     * `hydration` value, the resultset hydrate mode is updated before returning
     * it to the caller. Non-resultset query outputs are returned untouched to
     * preserve native Phalcon behavior.
     *
     * @param array<string, mixed>|null $parameters Query parameters or null to
     *     use the configured policy query.
     * @return mixed Query execution result, usually a Phalcon model resultset.
     * @throws ServiceException When the lifecycle query cannot be built because
     *     required DI services are unavailable or incompatible.
     */
    public static function findLifeCycle(?array $parameters = null): mixed
    {
        $query = self::getLifeCycleQuery($parameters);
        $resultset = $query->execute();
        
        if ($resultset instanceof ResultsetInterface) {
            if (isset($parameters['hydration'])) {
                $resultset->setHydrateMode($parameters['hydration']);
            }
        }
        
        return $resultset;
    }
}
