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

use Phalcon\Filter\Exception;
use Phalcon\Support\Collection;
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
     * @param Collection|null $dynamicJoins The collection of dynamic joins.
     *                                      Pass null to disable dynamic joins.
     */
    public function setDynamicJoins(?Collection $dynamicJoins): void
    {
        $this->dynamicJoins = $dynamicJoins;
        $dynamicJoins = $this->getDynamicJoinsFromFilters();
        foreach ($dynamicJoins as $key => $dynamicJoin) {
            $dynamicJoinKey = array_search($key, $this->dynamicJoinsMapping);
            $this->getJoins()->set($dynamicJoinKey, $dynamicJoin);
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
     * @param Collection $dynamicJoins The collection of dynamicJoins to merge with the current property.
     * @return void
     */
    public function mergeDynamicJoins(Collection $dynamicJoins): void
    {
        $this->dynamicJoins = CollectionPolicy::mergeNullable(
            $this->dynamicJoins,
            $dynamicJoins
        );
    }

    /**
     * @throws Exception
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
            $filteredField = preg_replace('/\[[^\]]*\](?=\.)/', '', $filter['field']);
            $prospectAlias = preg_replace('/\.[^.]*$/', '', $filteredField);

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
                    $alias = preg_replace('/\[[^\]]*\]/', '', $fieldAlias);
                    
                    // the join alias must be defined
                    if (!$dynamicJoins->has($alias)) {
                        throw new \Exception('Dynamic join alias not defined for `' . $alias . '`');
                    }
                    
                    // prepare the dynamic joins alias mapping
                    $joinAlias = $this->dynamicJoinsMapping[$fieldAlias] ??= uniqid('_') . '_';
                    
                    // append this replace definition for this joins and other joins of the same deviation
                    $replaces['[' . $alias . '].'] = '[' . $joinAlias . '].';
                    
                    // if the join part alias is defined and the dynamic join alias never created
                    if (!isset($this->dynamicJoinsBuild[$joinAlias])) {
                        // force join condition to be an array
//                        if (!is_array($dynamicJoins[$alias][1])) {
//                            $dynamicJoins[$alias][1] = [$dynamicJoins[$alias][1]];
//                        }

                        // generate the join filter conditions
                        $joinFilters = $this->getParam('joins');
                        $conditions = !empty($joinFilters[$fieldAlias]) ? $this->defaultFilterCondition($joinFilters[$fieldAlias], null, $fieldAlias) : [];

                        // @todo we could potentially extract some "filters" conditions that are used and scoped for this relationship with no external fields
                        // @todo so we could append them to the dynamic join conditions and gain performance

                        $join = [
                            // model class to use
                            $dynamicJoins[$alias][0],

                            // update the join condition to use the dynamic join alias
                            '(' . str_replace(array_keys($replaces), array_values($replaces), $dynamicJoins[$alias][1]) . ')',

                            // use generated alias
                            $joinAlias,

                            // join type left by default
                            $joins[$alias][3] ?? 'left',

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

        // prepare alias with context
        $alias = '';

        foreach ($fieldParts as $fieldPart) {
            // build the full alias with context
            $alias .= (empty($alias) ? '' : '.') . $fieldPart;

            // check if we have dynamic alias for this alias
            $dynamicAlias = $this->dynamicJoinsMapping[$alias] ??= $alias;

            // dynamic alias specifically defined, use this
            if (isset($joins[$dynamicAlias])) {
                $ret [] = $joins[$dynamicAlias];
            }

            // alias specifically defined, use this
            elseif (isset($joins[$alias])) {
                $ret [] = $joins[$alias];
            }

            // joins not specifically defined, fallback using native phalcon way
            else {
                // loop through each defined joins
                foreach ($joins as $join) {
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

        // Return shallow -> deep (anchor first)
//        return $ret;

//        // return in reverse order as the first should be the longest alias
        return array_reverse($ret);
    }
}
