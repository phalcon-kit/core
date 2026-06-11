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

namespace PhalconKit\Mvc\Controller\Traits\Query;

use Phalcon\Filter\Exception as FilterException;
use Phalcon\Support\Collection;
use PhalconKit\Exception\HttpException;
use PhalconKit\Exception\LogicException;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractParams;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\AbstractJoins;
use PhalconKit\Support\CollectionPolicy;

trait DynamicJoins
{
    use AbstractJoins;
    use AbstractParams;

    protected array $dynamicJoinsMapping = [];
    protected ?array $dynamicJoinsBuild = null;

    protected ?Collection $dynamicJoins = null;


    /**
     * Initializes the dynamic joins.
     *
     * This method is responsible for initializing the dynamic joins.
     *
     * @return void
     */
    public function initializeDynamicJoins(): void
    {
        $this->setDynamicJoins(null);
    }

    /**
     * Sets the dynamic joins for the find criteria.
     *
     * @param array|Collection|null $dynamicJoins The collection of dynamic joins.
     *                                      Pass null to disable dynamic joins.
     */
    public function setDynamicJoins(array|Collection|null $dynamicJoins): void
    {
        $this->dynamicJoins = CollectionPolicy::normalizeNullable($dynamicJoins);
        $dynamicJoins = $this->getDynamicJoinsFromFilters();
        $joins = $this->getJoins();
        if ($joins === null) {
            $joins = new Collection([], false);
            $this->setJoins($joins);
        }

        foreach ($dynamicJoins as $key => $dynamicJoin) {
            $dynamicJoinKey = array_search($key, $this->dynamicJoinsMapping, true);
            if (!is_string($dynamicJoinKey)) {
                continue;
            }

            $joins->set($dynamicJoinKey, $dynamicJoin);
        }
    }

    /**
     * Returns the dynamic joins collection.
     *
     * This method retrieves the dynamic joins for the find criteria.
     * If joins fields have been set, it returns the collection of dynamic joins.
     * If no dynamic joins have been set, it returns null.
     *
     * Note: The dynamic joins are used to add conditions during the find query and are not added to the result.
     *
     * @return Collection|null The collection of dynamic joins or null everything is allowed.
     */
    public function getDynamicJoins(): ?Collection
    {
        return $this->dynamicJoins;
    }

    /**
     * Merges the provided dynamicJoins collection with the current dynamicJoins property.
     *
     * @param array|Collection $dynamicJoins The collection of dynamicJoins to merge with the current property.
     * @return void
     */
    public function mergeDynamicJoins(array|Collection $dynamicJoins): void
    {
        $this->dynamicJoins = CollectionPolicy::mergeNullable(
            $this->dynamicJoins,
            CollectionPolicy::normalize($dynamicJoins)
        );
    }

    /**
     * Extract dynamic join definitions required by the current filter tree.
     *
     * Relationship filters can ask the controller to materialize joins lazily.
     * This method walks nested filters, validates that every requested dynamic
     * alias is configured, builds generated SQL aliases, and returns the join
     * definitions that should be merged into the current find query.
     *
     * @param array|null $filters Optional filter tree. When null, the request
     *     `filters` parameter is read from the controller.
     *
     * @return array Dynamic join definitions keyed by generated join alias.
     *
     * @throws FilterException When request parameter filtering fails.
     * @throws HttpException When a filter references an undefined dynamic join
     *     alias.
     * @throws LogicException When a configured dynamic join definition is
     *     malformed.
     */
    public function getDynamicJoinsFromFilters(?array $filters = null): array
    {
        $filters ??= $this->getParam('filters');
        
        if (!isset($filters) || !is_array($filters)) {
            return [];
        }

        $dynamicJoins = $this->getDynamicJoins();
        if (empty($dynamicJoins)) {
            return [];
        }

        // prepare dynamic joins build
        if (!isset($this->dynamicJoinsBuild)) {
            $this->dynamicJoinsBuild = [];
        }

        foreach ($filters as $filter) {
            // loop through filters subgroups
            if (is_array($filter) && isset($filter[0])) {
                $this->dynamicJoinsBuild = array_merge(
                    $this->dynamicJoinsBuild,
                    $this->getDynamicJoinsFromFilters($filter)
                );
            }
            
            // skip if no field is defined
            if (!isset($filter['field'])) {
                continue;
            }
            
            // skip if not a relationship field
            if (!str_contains($filter['field'], '.')) {
                continue;
            }
            
            // prepare field without array brackets
            $filteredField = preg_replace('/\[[^\]]*\](?=\.)/', '', (string)$filter['field']) ?? '';
            $prospectAlias = preg_replace('/\.[^.]*$/', '', $filteredField) ?? '';

            foreach ($dynamicJoins as $dynamicJoinAlias => $dynamicJoin) {
                // if prospect alias doesn't match any dynamic join alias, skip
                if ($prospectAlias !== $dynamicJoinAlias) {
                    continue;
                }
                
                // prepare replaces for conditions
                $replaces = [];
                
                // prepare the field alias
                $fieldAlias = '';
                
                // explode each parts of the nesting relationship
                $fieldParts = explode('.', $filter['field']);
                
                // only keep relationships from the field parts
                array_pop($fieldParts);
                
                // loop through each steps
                foreach ($fieldParts as $fieldPartAlias) {
                    // build the full field alias with the context
                    $fieldAlias .= (empty($fieldAlias) ? '' : '.') . $fieldPartAlias;
                    
                    // filter field alias to get raw alias by removing brackets
                    $alias = preg_replace('/\[[^\]]*\]/', '', $fieldAlias) ?? '';
                    
                    // the join alias must be defined
                    if (!$dynamicJoins->has($alias)) {
                        throw new HttpException('Dynamic join alias not defined for `' . $alias . '`', 400);
                    }
                    
                    // prepare the dynamic joins alias mapping
                    $joinAlias = $this->dynamicJoinsMapping[$fieldAlias] ??= uniqid('_') . '_';
                    
                    // append this replace definition for this joins and other joins of the same deviation
                    $replaces['[' . $alias . '].'] = '[' . $joinAlias . '].';
                    
                    // if the join part alias is defined and the dynamic join alias never created
                    if (!isset($this->dynamicJoinsBuild[$joinAlias])) {
                        // generate the join filter conditions
                        $joinFilters = $this->getParam('joins');
                        $joinFilters = is_array($joinFilters) ? $joinFilters : [];
                        $conditions = !empty($joinFilters[$fieldAlias]) ? $this->defaultFilterCondition($joinFilters[$fieldAlias], null, $fieldAlias) : [];

                        $dynamicJoinDefinition = $this->normalizeDynamicJoinDefinition(
                            $alias,
                            $dynamicJoins->get($alias)
                        );

                        $joinCondition = $dynamicJoinDefinition[1];
                        if (is_array($joinCondition)) {
                            $joinCondition = implode(' and ', $joinCondition);
                        }

                        if (!is_string($joinCondition)) {
                            throw new LogicException(sprintf('Invalid dynamic join condition for `%s`.', $alias));
                        }

                        $joinType = $dynamicJoinDefinition[3] ?? 'left';
                        if (!is_string($joinType)) {
                            $joinType = 'left';
                        }

                        // Potential optimization: relationship-scoped filters
                        // could be hoisted into the join condition when they do
                        // not reference external fields. Keep the current query
                        // shape until alias and permission semantics are proven.

                        $join = [
                            // model class to use
                            $dynamicJoinDefinition[0],

                            // update the join condition to use the dynamic join alias
                            '(' . str_replace(array_keys($replaces), array_values($replaces), $joinCondition) . ')',

                            // use generated alias
                            $joinAlias,

                            // join type left by default
                            $joinType,

                            // join conditions with sql, bind, bindTypes
                            $conditions
                        ];

                        // add the new dynamic join alias
                        $this->dynamicJoinsBuild[$joinAlias] = $join;
                    }
                }
            }
        }
        
        // ensure the children (longest mapping) gets replaced before their parents
        uksort($this->dynamicJoinsMapping, function ($a, $b) {
            return mb_strlen($b) - mb_strlen($a);
        });

        return $this->dynamicJoinsBuild;
    }

    /**
     * Normalizes dynamic join definitions to [model, condition, alias?, type?].
     *
     * Dynamic joins generate their concrete SQL alias at runtime, so only the model and
     * join condition are required. The legacy [Model::class => condition] form is kept
     * for older application controllers.
     */
    protected function normalizeDynamicJoinDefinition(string $alias, mixed $definition): array
    {
        if (!is_array($definition)) {
            throw new LogicException(sprintf('Invalid dynamic join definition for `%s`.', $alias));
        }

        if (!isset($definition[0], $definition[1]) && count($definition) === 1) {
            $model = array_key_first($definition);

            if (is_string($model)) {
                $definition = [
                    $model,
                    $definition[$model],
                ];
            }
        }

        if (!isset($definition[0], $definition[1])) {
            throw new LogicException(sprintf('Invalid dynamic join definition for `%s`.', $alias));
        }

        return $definition;
    }
    
    /**
     * Retrieves the join definitions for a given field by analyzing its relationship parts.
     *
     * @param string $field The field for which to retrieve the join definitions, including its relationship hierarchy.
     * @return array An array containing the join definitions for the specified field, ordered in a manner suitable for processing.
     */
    public function getJoinsDefinitionFromField(string $field): array
    {
        // prepare the field relationship parts
        $fieldParts = explode('.', $field);

        // remove the field part
        array_pop($fieldParts);

        // no relationship parts so we don't have joins
        if (empty($fieldParts)) {
            return [];
        }

        // prepare return value
        $ret = [];

        // pre-fetch the joins first
        $joins = $this->getJoins();
        if ($joins === null) {
            return [];
        }

        // prepare alias with context
        $alias = '';

        foreach ($fieldParts as $fieldPart) {
            // build the full alias with context
            $alias .= (empty($alias) ? '' : '.') . $fieldPart;

            // check if we have dynamic alias for this alias
            $dynamicAlias = $this->dynamicJoinsMapping[$alias] ??= $alias;

            // dynamic alias specifically defined, use this
            if ($joins->has($dynamicAlias)) {
                $join = $joins->get($dynamicAlias);
                if (is_array($join)) {
                    $ret [] = $join;
                }
            }

            // alias specifically defined, use this
            elseif ($joins->has($alias)) {
                $join = $joins->get($alias);
                if (is_array($join)) {
                    $ret [] = $join;
                }
            }

            // joins not specifically defined, fallback using native phalcon way
            else {
                // loop through each defined joins
                foreach ($joins as $join) {
                    if (!is_array($join) || !isset($join[2]) || !is_string($join[2])) {
                        continue;
                    }

                    // join found using dynamic alias
                    if ($join[2] === $dynamicAlias) {
                        $ret [] = $join;
                    }

                    // join found using alias
                    elseif ($join[2] === $alias) {
                        $ret [] = $join;
                    }
                }
            }
        }

        // Return deep -> shallow so the longest alias is processed first.
        return array_reverse($ret);
    }
}
