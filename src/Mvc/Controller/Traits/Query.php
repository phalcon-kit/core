<?php

/**
 * This file is part of the Zemit Framework.
 *
 * (c) Zemit Team <contact@zemit.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

namespace Zemit\Mvc\Controller\Traits;

use Phalcon\Db\Column;
use Phalcon\Filter\Exception;
use Phalcon\Filter\Filter;
use Phalcon\Messages\Message;
use Phalcon\Mvc\ModelInterface;
use Zemit\Identity\Manager as Identity;
use Zemit\Mvc\Controller\Traits\Abstracts\AbstractModel;
use Zemit\Mvc\Controller\Traits\Abstracts\AbstractParams;
use Zemit\Support\Exposer\Exposer;

/**
 * Trait Model
 */
trait Query
{
    use AbstractParams;
    use AbstractModel;
    
    use DynamicJoins;
    
    protected $_bind = [];
    protected $_bindTypes = [];
    
    /**
     * Get the WhiteList parameters for saving
     * @return null|array
     * @todo add a whitelist object that would be able to support one configuration for the search, assign, filter
     *
     */
    protected function getWhiteList()
    {
        return null;
    }
    
    /**
     * Get the Flattened WhiteList
     *
     * @param array|null $whiteList
     *
     * @return array|null
     */
    public function getFlatWhiteList(?array $whiteList = null)
    {
        $whiteList ??= $this->getWhiteList();
        $ret = Exposer::parseColumnsRecursive($whiteList);
        return $ret ? array_keys($ret) : null;
    }
    
    /**
     * Get the WhiteList parameters for filtering
     *
     * @return null|array
     */
    protected function getFilterWhiteList()
    {
        return $this->getWhiteList();
    }
    
    /**
     * @return array
     */
    public function getFilterAliasList(): array
    {
        return [];
    }
    
    /**
     * Get the list of fields to use during with the search value
     *
     * @return null|array
     */
    protected function getSearchWhiteList()
    {
        return $this->getFilterWhiteList();
    }
    
    /**
     * Get the column mapping for crud
     *
     * @return null|array
     */
    protected function getColumnMap()
    {
        return null;
    }
    
    /**
     * Get the Relationship WhiteList 
     * 
     * @return void
     */
    public function getWithWhiteList()
    {
        return null;
    }
    
    /**
     * Get relationship eager loading definition
     *
     * @return null|array
     */
    protected function getWith()
    {
        $requestedWith = $this->normalizeWith($this->getParam('with'));
        $allowedWith = $this->normalizeWith($this->getWithWhiteList());
        
        // Verify if requestedWith are in the allowedWith
        $with = [];
        foreach ($requestedWith as $value) {
            if (in_array($value, $allowedWith, true)) {
                $with[] = $value;
            }
            else {
                throw new Exception(sprintf('Requested "with" value "%s" is not allowed.', $value));
            }
        }
        
        return $with;
    }
    
    protected function normalizeWith(array|string|null $with)
    {
        if (empty($with)) {
            return [];
        }
        
        if (is_string($with)) {
            $with = explode(',', $with);
        }
        
        $normalizedWith = [];
        foreach ($with as $key => $value) {
            if (is_int($key) && is_string($value)) {
                $normalizedWith[] = $value;
            }
            else if (is_string($key) && is_bool($value) && $value) {
                $normalizedWith[] = $key;
            }
        }
        
        return $normalizedWith;
    }
    
    /**
     * Get relationship eager loading definition for a listing
     *
     * @return null|array
     */
    protected function getListWith()
    {
        return $this->getWith();
    }
    
    /**
     * Get relationship eager loading definition for a listing
     *
     * @return null|array
     */
    protected function getExportWith()
    {
        return $this->getListWith();
    }
    
    /**
     * Get expose definition for a single entity
     *
     * @return null|array
     */
    protected function getExpose()
    {
        return null;
    }
    
    /**
     * Get expose definition for listing many entities
     *
     * @return null|array
     */
    protected function getListExpose()
    {
        return $this->getExpose();
    }
    
    /**
     * Get expose definition for export
     *
     * @return null|array
     */
    protected function getExportExpose()
    {
        return $this->getExpose();
    }
    
    /**
     * Get columns merge definition for export
     *
     * @return null|array
     */
    public function getExportMergeColum()
    {
        return null;
    }
    
    /**
     * Get columns format field text definition for export
     *
     * @param array|null $params
     *
     * @return null|array
     */
    public function getExportFormatFieldText(?array $params = null)
    {
        return null;
    }
    
    /**
     * Get join definition
     *
     * @return null|array
     */
//    protected function getJoins()
//    {
//        return null;
//    }
    
    /**
     * Get the order definition
     *
     * @return null|array
     */
    protected function getOrder()
    {
        return $this->getParamExplodeArrayMapFilter('order');
    }
    
    /**
     * Get the current limit value
     *
     * @return null|int Default: 1000
     */
    protected function getLimit(): ?int
    {
        $limit = (int)$this->getParam('limit', 'int', 1000);
        return $limit === -1? null : (int)abs($limit);
    }
    
    /**
     * Get the current offset value
     *
     * @return null|int Default: 0
     */
    protected function getOffset(): int
    {
        return (int)$this->getParam('offset', 'int', 0);
    }
    
    /**
     * Get group
     * - Automatically group by ID by default if nothing else is provided
     * - This will fix multiple single records being returned for the same model with joins
     *
     * @return array|string|null
     */
    protected function getGroup()
    {
        $group = $this->getParamExplodeArrayMapFilter('group');
        
        // Fix for joins, automatically append grouping if none provided
        $join = $this->getJoins();
        if (empty($group) && !empty($join)) {
            $group = $this->appendModelName('id');
        }
        
        return $group;
    }
    
    /**
     * Get distinct
     * @TODO see how to implement this, maybe an action itself
     *
     * @return array|null
     */
    protected function getDistinct()
    {
        return $this->getParamExplodeArrayMapFilter('distinct');
    }
    
    /**
     * Get columns
     * @TODO see how to implement this
     *
     * @return array|string|null
     */
    protected function getColumns()
    {
        return $this->getParam('columns', 'string');
    }
    
    /**
     * Return the whitelisted role list for the current model
     *
     * @return string[] By default will return dev and admin role
     */
    protected function getRoleList()
    {
        return ['dev', 'admin'];
    }
    
    /**
     * Get Search condition
     *
     * @return string Default: deleted = 0
     */
    protected function getSearchCondition()
    {
        $conditions = [];
        
        $searchList = array_values(array_filter(array_unique(explode(' ', $this->getParam('search') ?? ''))));
        
        foreach ($searchList as $searchTerm) {
            $orConditions = [];
            $searchWhiteList = $this->getSearchWhiteList();
            if ($searchWhiteList) {
                foreach ($searchWhiteList as $whiteList) {
                    
                    // Multidimensional arrays not supported yet
                    // @todo support this properly
                    if (is_array($whiteList)) {
                        continue;
                    }
                    
                    $filterAliasList = $this->getFilterAliasList();
                    $fieldAlias = $filterAliasList[$whiteList] ?? $whiteList;
                    
                    // @todo do dynamic joins?
                    
                    $searchTermBinding = '_' . uniqid() . '_';
                    $orConditions [] = $this->appendModelName($fieldAlias) . " like :$searchTermBinding:";
                    $this->setBind([$searchTermBinding => '%' . $searchTerm . '%']);
                    $this->setBindTypes([$searchTermBinding => Column::BIND_PARAM_STR]);
                }
            }
            
            if (!empty($orConditions)) {
                $conditions [] = '(' . implode(' or ', $orConditions) . ')';
            }
        }
        
        return empty($conditions) ? null : '(' . implode(' and ', $conditions) . ')';
    }
    
    /**
     * Get Soft delete condition
     *
     * @return string Default: deleted = 0
     */
    protected function getSoftDeleteCondition(): ?string
    {
        return '[' . $this->getModelName() . '].[deleted] = 0';
    }
    
    /**
     * This function will explode the field based using the glue
     * and sanitize the values and append the model name
     */
    public function getParamExplodeArrayMapFilter(string $field, string $sanitizer = 'string', string $glue = ',', int $limit = PHP_INT_MAX) : ?array
    {
        $ret = [];
        $filter = $this->filter;
        $params = $this->getParam($field, $sanitizer);
        foreach (is_array($params)? $params : [$params] as $param) {
            $ret = array_filter(array_merge($ret, array_map(function ($e) use ($filter, $sanitizer) {
                return strrpos(strtoupper($e), 'RAND()') === 0? $e : $this->appendModelName(trim($filter->sanitize($e, $sanitizer)));
            }, explode($glue, $param ?? '', $limit))));
        }
        
        return empty($ret) ? null : $ret;
    }
    
    /**
     * Set the variables to bind
     *
     * @param array $bind Variable bind to merge or replace
     * @param bool $replace Pass true to replace the entire bind set
     */
    public function setBind(array $bind = [], bool $replace = false)
    {
        $this->_bind = $replace ? $bind : array_merge($this->getBind(), $bind);
    }
    
    /**
     * Get the current bind
     * key => value
     *
     * @return array|null
     */
    public function getBind()
    {
        return $this->_bind ?? null;
    }
    
    /**
     * Set the variables types to bind
     *
     * @param array $bindTypes Variable bind types to merge or replace
     * @param bool $replace Pass true to replace the entire bind type set
     */
    public function setBindTypes(array $bindTypes = [], bool $replace = false)
    {
        $this->_bindTypes = $replace ? $bindTypes : array_merge($this->getBindTypes(), $bindTypes);
    }
    
    /**
     * Get the current bind types
     *
     * @return array|null
     */
    public function getBindTypes()
    {
        return $this->_bindTypes ?? null;
    }
    
    /**
     * Get the identity condition for querying the database: Default created by
     *
     * @param array|null $columns The columns to check for identity condition
     * @param Identity|null $identity The identity object
     * @param array|null $roleList The list of roles
     *
     * @return string|null The generated identity condition or null if no condition is generated
     */
    protected function getIdentityCondition(?array $columns = null, ?Identity $identity = null, ?array $roleList = null): ?string
    {
        $identity ??= $this->identity ?? false;
        $roleList ??= $this->getRoleList();
        $modelName = $this->getModelName();
        
        if ($modelName && $identity && !$identity->hasRole($roleList)) {
            $ret = [];
            
            $columns ??= [
                'createdBy',
                'ownedBy',
                'userId',
            ];
            
            foreach ($columns as $column) {
                if (!property_exists($modelName, $column)) {
                    continue;
                }
                
                $field = strpos($column, '.') !== false ? $column : $modelName . '.' . $column;
                $field = '[' . str_replace('.', '].[', $field) . ']';
                
                $this->setBind([$column => (int)$identity->getUserId()]);
                $this->setBindTypes([$column => Column::BIND_PARAM_INT]);
                $ret [] = $field . ' = :' . $column . ':';
            }
            
            return implode(' or ', $ret);
        }
        
        return null;
    }
    
    public function arrayMapRecursive(callable $callback, array $array): array
    {
        $func = function ($item) use (&$func, &$callback) {
            return is_array($item) ? array_map($func, $item) : call_user_func($callback, $item);
        };
        
        return array_map($func, $array);
    }
    
    /**
     * Get Filter Condition
     *
     * @param array|null $filters
     * @param array|null $whiteList
     * @param bool $or
     *
     * @return string|null Return the generated query
     * @throws \Exception Throw an exception if the field property is not valid
     * @todo escape fields properly
     *
     */
    protected function getFilterCondition(?array $filters = null, ?array $whiteList = null, bool $or = false, int $level = 0)
    {
        $filters ??= $this->getParam('filters');
        $whiteList ??= $this->getFilterWhiteList();
        $whiteList = $this->getFlatWhiteList($whiteList);
        $lowercaseWhiteList = !is_null($whiteList) ? $this->arrayMapRecursive('mb_strtolower', $whiteList) : $whiteList;
        
        // No filter, no query
        if (empty($filters)) {
            return null;
        }
        
        $query = [];
        foreach ($filters as $filterIndex => $filter) {
            $field = $this->filter->sanitize($filter['field'] ?? null, ['string', 'trim']);
            
            // get logical operator
            $logic = $this->filter->sanitize($filter['logic'] ?? null, ['string', 'trim', 'lower']);
            $queryLogic = $logic ?: ($or ? ($filterIndex === 0? 'and' : 'or') : ($filterIndex === 0? 'or' : 'and')); // fallback for old way
            if (!in_array($queryLogic, ['or', 'and', 'xor'])) {
                throw new \Exception('Unsupported logical operator: `' . $queryLogic . '`', 400);
            }
            
            if (!empty($field)) {
                $filteredField = mb_strtolower(preg_replace('/\[[^\]]*\](?=\.)/', '', $field));
                
                // whiteList on filter condition
                if (is_null($whiteList) || !in_array($filteredField, $lowercaseWhiteList ?? [], true)) {
                    throw new \Exception('Filter field not allowed: `' . $field . '`', 403);
                }
                
                // replace field with alias
                $filterAliasList = $this->getFilterAliasList();
                $fieldAlias = $filterAliasList[$field] ?? $field;
                
                // replace fields related to dynamic joins
                $fieldAlias = str_replace(array_keys($this->dynamicJoinsMapping), array_values($this->dynamicJoinsMapping), $fieldAlias);
                
                $uniqid = substr(md5(json_encode($filter)), 0, 10);
                $queryValue = '_' . uniqid($uniqid . '_value_') . '_';
                $queryOperator = strtolower($filter['operator']);
                
                // Map alias query operator
                $mapAlias = [
                    'equals' => '=',
                    'not equal' => '!=',
                    'does not equal' => '!=',
                    'different than' => '<>',
                    'greater than' => '>',
                    'greater than or equal' => '>=',
                    'less than' => '<',
                    'less than or equal' => '<=',
                    'null-safe equal' => '<=>',
                ];
                $queryOperator = $mapAlias[$queryOperator] ?? $queryOperator;
                
                switch ($queryOperator) {
                    
                    // mysql native
                    case '=': // Equal operator
                    case '!=': // Not equal operator
                    case '<>': // Not equal operator
                    case '>': // Greater than operator
                    case '>=': // Greater than or equal operator
                    case '<': // Less than or equal operator
                    case '<=': // Less than or equal operator
                    case '<=>': // NULL-safe equal to operator
                    case 'in': // Whether a value is within a set of values
                    case 'not in': // Whether a value is not within a set of values
                    case 'like': // Simple pattern matching
                    case 'not like': // Negation of simple pattern matching
                    case 'between': // Whether a value is within a range of values
                    case 'not between': // Whether a value is not within a range of values
                    case 'is': // Test a value against a boolean
                    case 'is not': // Test a value against a boolean
                    case 'is null': // NULL value test
                    case 'is not null': // NOT NULL value test
                    case 'is false': // Test a value against a boolean
                    case 'is not false': // // Test a value against a boolean
                    case 'is true': // // Test a value against a boolean
                    case 'is not true': // // Test a value against a boolean
                        break;
                    
                    // advanced filters
                    case 'starts with':
                    case 'does not start with':
                    case 'ends with':
                    case 'does not end with':
                    case 'regexp':
                    case 'not regexp':
                    case 'contains':
                    case 'does not contain':
                    case 'contains word':
                    case 'does not contain word':
                    case 'distance sphere greater than':
                    case 'distance sphere greater than or equal':
                    case 'distance sphere less than':
                    case 'distance sphere less than or equal':
                    case 'is empty':
                    case 'is not empty':
                        break;
                    
                    default:
                        throw new \Exception('Not allowed to filter using the following operator: `' . $queryOperator . '`', 403);
                }
                
                $bind = [];
                $bindType = [];
                
                // Add the current model name by default
                $fieldAlias = $this->appendModelName($fieldAlias);
                $queryFieldBinder = $fieldAlias;
                
                // Prepare negative situations
                $isNegative = str_contains($queryOperator, 'not') || in_array($queryOperator, ['!=', '<>']);
                $orNullOrEmpty = $isNegative? " or $queryFieldBinder is null or $queryFieldBinder = ''" : '';
                
                // operator using is and value is defined, is not allowed
                if (!empty($filter['value']) && str_contains($queryOperator, 'is')) {
                    throw new \Exception('Not allowed to pass a value when using the following operator: `' . $queryOperator . '`', 403);
                }
                
                // is or not - empty
                if (in_array($queryOperator, ['is empty', 'is not empty'])) {
                    $queryOperator = ($queryOperator === 'is not empty' ? '!' : '');
                    $subQuery = "$queryLogic $queryOperator(TRIM($queryFieldBinder) = '' or $queryFieldBinder is null)";
                }
                
                // is or not - null
                elseif (in_array($queryOperator, ['is null', 'is not null'])) {
                    $subQuery = "$queryLogic $queryFieldBinder $queryOperator";
                }
                
                // is or not - booleans
                elseif (in_array($queryOperator, ['is true', 'is not true', 'is false', 'is not false'])) {
                    // fix for phql, it does not support "is" and "is not" for booleans and instead require equals or not equals
                    $queryOperator = str_replace(['is not', 'is'], ['!=', '='], $queryOperator);
                    $subQuery = "$queryLogic $queryFieldBinder $queryOperator";
                }
                
                // raw queries without values
                elseif (!isset($filter['value'])) {
                    $subQuery = "$queryLogic $queryFieldBinder $queryOperator";
                }
                
                // queries with values
                else {
                    $queryValueBinder = ':' . $queryValue . ':';
                    
                    // special for between and not between
                    if (in_array($queryOperator, ['between', 'not between'])) {
                        $queryValue0 = '_' . uniqid($uniqid . '_value_') . '_';
                        $queryValue1 = '_' . uniqid($uniqid . '_value_') . '_';
                        
                        $queryValueIndex = $filter['value'][0] <= $filter['value'][1]? 0 : 1;
                        $bind[$queryValue0] = $filter['value'][$queryValueIndex? 1 : 0];
                        $bind[$queryValue1] = $filter['value'][$queryValueIndex? 0 : 1];
                        
                        $bindType[$queryValue0] = Column::BIND_PARAM_STR;
                        $bindType[$queryValue1] = Column::BIND_PARAM_STR;
                        
                        $subQuery = $queryLogic . ' ' . (($queryOperator === 'not between') ? 'not ' : null) . "$queryFieldBinder between :$queryValue0: and :$queryValue1:";
                    }
                    
                    elseif (in_array($queryOperator, [
                            'distance sphere equals',
                            'distance sphere greater than',
                            'distance sphere greater than or equal',
                            'distance sphere less than',
                            'distance sphere less than or equal',
                        ])
                    ) {
                        // Prepare values binding of 2 sphere point to calculate distance
                        $queryBindValue0 = '_' . uniqid($uniqid . '_value_') . '_';
                        $queryBindValue1 = '_' . uniqid($uniqid . '_value_') . '_';
                        $queryBindValue2 = '_' . uniqid($uniqid . '_value_') . '_';
                        $queryBindValue3 = '_' . uniqid($uniqid . '_value_') . '_';
                        $bind[$queryBindValue0] = $filter['value'][0];
                        $bind[$queryBindValue1] = $filter['value'][1];
                        $bind[$queryBindValue2] = $filter['value'][2];
                        $bind[$queryBindValue3] = $filter['value'][3];
                        $bindType[$queryBindValue0] = Column::BIND_PARAM_DECIMAL;
                        $bindType[$queryBindValue1] = Column::BIND_PARAM_DECIMAL;
                        $bindType[$queryBindValue2] = Column::BIND_PARAM_DECIMAL;
                        $bindType[$queryBindValue3] = Column::BIND_PARAM_DECIMAL;
                        $queryPointLatBinder0 = ':' . $queryBindValue0 . ':';
                        $queryPointLonBinder0 = ':' . $queryBindValue1 . ':';
                        $queryPointLatBinder1 = ':' . $queryBindValue2 . ':';
                        $queryPointLonBinder1 = ':' . $queryBindValue3 . ':';
                        $queryLogicalOperator =
                            (str_contains($queryOperator, 'greater') ? '>' : null) .
                            (str_contains($queryOperator, 'less') ? '<' : null) .
                            (str_contains($queryOperator, 'equal') ? '=' : null);
                        
                        $bind[$queryValue] = $filter['value'];
                        $subQuery = "$queryLogic ST_Distance_Sphere(point($queryPointLatBinder0, $queryPointLonBinder0), point($queryPointLatBinder1, $queryPointLonBinder1)) $queryLogicalOperator $queryValueBinder";
                    }
                    
                    elseif (in_array($queryOperator, [
                            'in',
                            'not in',
                        ])
                    ) {
                        $queryValueBinder = '({' . $queryValue . ':array})';
                        $bind[$queryValue] = $filter['value'];
                        $bindType[$queryValue] = Column::BIND_PARAM_STR;
                        $subQuery = "$queryLogic $queryFieldBinder $queryOperator $queryValueBinder";
                    }
                    
                    else {
                        $queryAndOr = [];
                        
                        $valueList = is_array($filter['value']) ? $filter['value'] : [$filter['value']];
                        foreach ($valueList as $value) {
                            
                            $queryValue = '_' . uniqid($uniqid . '_value_') . '_';
                            $queryValueBinder = ':' . $queryValue . ':';
                            
                            if (in_array($queryOperator, ['contains', 'does not contain'])) {
                                $bind[$queryValue] = '%' . $value . '%';
                                $bindType[$queryValue] = Column::BIND_PARAM_STR;
                                $likeOperator = $isNegative? 'not like' : 'like';
                                $queryAndOr [] = "($queryFieldBinder $likeOperator :$queryValue:$orNullOrEmpty)";
                            }
                            
                            elseif (in_array($queryOperator, ['starts with', 'does not start with'])) {
                                $bind[$queryValue] = $value . '%';
                                $bindType[$queryValue] = Column::BIND_PARAM_STR;
                                $isNegative = str_contains($queryOperator, 'does not start with');
                                $likeOperator = $isNegative? 'not like' : 'like';
                                $queryAndOr [] = "($queryFieldBinder $likeOperator :$queryValue:$orNullOrEmpty)";
                            }
                            
                            elseif (in_array($queryOperator, ['ends with', 'does not end with'])) {
                                $bind[$queryValue] = '%' . $value;
                                $bindType[$queryValue] = Column::BIND_PARAM_STR;
                                $likeOperator = str_contains($queryOperator, 'does not end with')? 'not like' : 'like';
                                $queryAndOr [] = "($queryFieldBinder $likeOperator :$queryValue:$orNullOrEmpty)";
                            }
                            
                            elseif (in_array($queryOperator, ['contains word', 'does not contain word'])) {
                                $bind[$queryValue] = '\\b' . $value . '\\b';
                                $regexQueryOperator = str_replace(['contains word', 'does not contain word'], ['regexp', 'not regexp'], $queryOperator);
                                $queryAndOr [] = $regexQueryOperator . "($queryFieldBinder, :$queryValue:)$orNullOrEmpty";
                            }
                            
                            elseif (in_array($queryOperator, ['regexp', 'not regexp'])) {
                                $bind[$queryValue] = $value;
                                $queryAndOr [] = $queryOperator . "($queryFieldBinder, :$queryValue:)";
                            }
                            
                            else {
                                $bind[$queryValue] = $value;
                                
                                if (is_string($value)) {
                                    $bindType[$queryValue] = Column::BIND_PARAM_STR;
                                }
                                
                                elseif (is_int($value)) {
                                    $bindType[$queryValue] = Column::BIND_PARAM_INT;
                                }
                                
                                elseif (is_bool($value)) {
                                    $bindType[$queryValue] = Column::BIND_PARAM_BOOL;
                                }
                                
                                elseif (is_float($value)) {
                                    $bindType[$queryValue] = Column::BIND_PARAM_DECIMAL;
                                }
                                
                                elseif (is_double($value)) {
                                    $bindType[$queryValue] = Column::BIND_PARAM_DECIMAL;
                                }
                                
                                elseif (is_array($value)) {
                                    $queryValueBinder = '({' . $queryValue . ':array})';
                                    $bindType[$queryValue] = Column::BIND_PARAM_STR;
                                }
                                
                                else {
                                    $bindType[$queryValue] = Column::BIND_PARAM_NULL;
                                }
                                
                                $queryAndOr [] = "$queryFieldBinder $queryOperator $queryValueBinder" . $orNullOrEmpty;
                            }
                        }
                        if (!empty($queryAndOr)) {
                            $andOr = str_contains($queryOperator, ' not ')? 'and' : 'or';
                            $subQuery = $queryLogic . ' ((' . implode(') ' . $andOr . ' (', $queryAndOr) . '))';
                        }
                    }
                }
                
                if (!empty($subQuery)) {
                    
                    $isForeignField = str_contains($field, '.');
                    $isSubquery = isset($filter['subquery']) && $filter['subquery'];
                    
                    if ($isNegative && $isForeignField && $isSubquery) {
                        $joins = $this->getJoinsDefinitionFromField($field);
                        
                        if (empty($joins)) {
                            throw new \Exception('Unable to prepare negative subquery for the foreign field `' . $field . '`', 400);
                        }
                        
                        $firstJoin = array_shift($joins);
                        
                        // build joins chain
                        $joinsQuery = [];
                        foreach ($joins as $join) {
                            $joinsQuery []= $join[3] . ' join [' . $join[0] . '] as [' . $join[2] . '] on ' . $join[1];
                        }
                        
                        // swap negative condition to positive
                        $replaces = [' not ' => ' ', ' != ' => ' = ', ' <> ' => ' = '];
                        $subQuery = str_replace(array_keys($replaces), array_values($replaces), $subQuery);
                        
                        // prepare "not exists" sub query
                        $notExistQuery = 'not exists (select 1 from [' . $firstJoin[0] . '] as [' . $firstJoin[2] . '] ' . implode(' ', $joinsQuery) . ' where ' . $firstJoin[1] . ' and ${2})';
                        $query []= preg_replace('/^(xor |and |or )(.*)/', $level || !empty($query)? '${1}' . $notExistQuery : $notExistQuery, $subQuery);
                    } else {
                        $query []= $subQuery;
                    }
                }
                
                $this->setBind($bind);
                $this->setBindTypes($bindType);
            }
            elseif (is_array($filter)) {
                $query [] = $this->getFilterCondition($filter, $whiteList, !$or, $level + 1);
            }
            else {
                throw new \Exception('A valid field property is required.', 400);
            }
        }
        
        return empty($query) ? null : preg_replace('/^(xor |and |or )(.*)/', $level? '${1}(${2})' : '(${2})', implode(' ', $query));
    }
    
    /**
     * Append the current model name alias to the field
     * So: field -> [Alias].[field]
     */
    public function appendModelName(string $field, ?string $modelName = null): string
    {
        $modelName ??= $this->getModelName();
        
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
        elseif (!str_contains($field, ']') && !str_contains($field, '[')) {
            $field = trim('[' . implode('].[', explode('.', array_shift($explode))) . ']' . implode(' ', $explode));
        }
        
        return $field;
    }
    
    /**
     * Get Permission Condition
     *
     * @return null
     */
    protected function getPermissionCondition($type = null, $identity = null)
    {
        return null;
    }
    
    protected function fireGet($method)
    {
        // @todo
//        $eventRet = $this->eventsManager->fire('rest:before' . ucfirst($method), $this);
//        if ($eventRet !== false) {
//            $ret = $this->{$method}();
//            $eventRet = $this->eventsManager->fire('rest:after' . ucfirst($method), $this, $ret);
//        }
        
        $ret = $this->{$method}();
        $eventRet = $this->eventsManager->fire('rest:' . $method, $this, $ret);
        return $eventRet === false ? null : $eventRet ?? $ret;
    }
    
    /**
     * Get all conditions
     *
     * @return string
     * @throws \Exception
     */
    protected function getConditions()
    {
        $conditions = array_values(array_unique(array_filter([
            $this->fireGet('getSoftDeleteCondition'),
            $this->fireGet('getIdentityCondition'),
            $this->fireGet('getFilterCondition'),
            $this->fireGet('getSearchCondition'),
            $this->fireGet('getPermissionCondition'),
        ])));
        
        if (empty($conditions)) {
            $conditions [] = 1;
        }
        
        return '(' . implode(') and (', $conditions) . ')';
    }
    
    /**
     * Get having conditions
     */
    public function getHaving()
    {
        return null;
    }
    
    /**
     * Get find definition
     *
     * @return array
     * @throws \Exception
     */
    protected function getFind()
    {
        $find = [];
        $find['joins'] = $this->fireGet('getJoins');
        $find['conditions'] = $this->fireGet('getConditions');
        $find['bind'] = $this->fireGet('getBind');
        $find['bindTypes'] = $this->fireGet('getBindTypes');
        $find['limit'] = $this->fireGet('getLimit');
        $find['offset'] = $this->fireGet('getOffset');
        $find['order'] = $this->fireGet('getOrder');
        $find['columns'] = $this->fireGet('getColumns');
        $find['distinct'] = $this->fireGet('getDistinct');
        $find['group'] = $this->fireGet('getGroup');
        $find['having'] = $this->fireGet('getHaving');
        $find['cache'] = $this->fireGet('getCache');
        
        // fix for grouping by multiple fields, phalcon only allow string here
        foreach (['distinct', 'group', 'order'] as $findKey) {
            if (isset($find[$findKey]) && is_array($find[$findKey])) {
                $find[$findKey] = implode(', ', $find[$findKey]);
            }
        }
        
        // move condition to having if the condition is aggregated
        if ($this->conditionsShouldBeHaving($find['conditions'])) {
            $find['having'] = (!empty($find['having'])? $find['having'] . ' and ' : '') . $find['conditions'];
            $find['conditions'] = '(1)';
        }
        
//        dd($find);
        return array_filter($find);
    }
    
    /**
     * Return find lazy loading config for count
     * @return array|string
     */
    protected function getFindCount($find = null)
    {
        $find ??= $this->getFind();
        if (isset($find['limit'])) {
            unset($find['limit']);
        }
        if (isset($find['offset'])) {
            unset($find['offset']);
        }
//        if (isset($find['group'])) {
//            unset($find['group']);
//        }
        
        return array_filter($find);
    }
    
    /**
     * Get a single model instance by ID or UUID
     *
     * @param int|null $id The ID of the model instance
     * @param string|null $modelName The name of the model class to retrieve
     * @param array|null $with An array of relationship names to eager load
     * @param array|null $find An array of additional find options
     *
     * @return ModelInterface|null     The retrieved model instance, or null if no ID or UUID is provided
     * @throws Exception
     * @throws \Exception
     */
    public function getSingle(?int $id = null, ?string $modelName = null, ?array $with = null, ?array $find = null): ?ModelInterface
    {
        $id ??= $this->getParam('id', [Filter::FILTER_INT]);
        $uuid = $this->getParam('uuid', [Filter::FILTER_STRING]);
        
        // no ID or UUID provided, avoid doing the request
        if (empty($id) && empty($uuid)) {
            return null;
        }
        
        $modelName ??= $this->getModelName();
        $with ??= $this->getWith();
        $find ??= $this->getFind();
        
        $conditions = [];
        
        // Using ID
        if (!empty($id)) {
            
            $conditions []= $this->appendModelName('id', $modelName) . ' = :id:';
            $find['bind']['id'] = (int)$id;
            $find['bindTypes']['id'] = Column::BIND_PARAM_INT;
        }
        
        // Using UUID
        if (!empty($uuid)) {
            $conditions []= $this->appendModelName('uuid', $modelName) . ' = :uuid:';
            $find['bind']['uuid'] = $uuid;
            $find['bindTypes']['uuid'] = Column::BIND_PARAM_STR;
        }
        
        // Append conditions
        $find['conditions'] .= (empty($find['conditions']) ? null : ' AND (') . implode(') AND (', $conditions) . ')';
        
        return $modelName::findFirstWith($with ?? [], $find ?? []);
    }
    
    /**
     * Get a count result from the find query
     * @param string|null $model
     * @param array|null $find
     * @return int
     * @throws \Exception
     */
    protected function count(?string $model = null, ?array $find = null): int
    {
        $model ??= $this->getModelName();
        $find ??= $this->getFind();
        $find = $this->getFindCount($find);
        
        // use subCount instead of the query is aggregated
        $useSubCount = !empty($find['having']) || !empty($find['group']);
        $countResult = $useSubCount
            ? $model::subCount($find)
            : $model::count($find);
        
        // in case we didn't use subCount and received false, try using subCount anyway
        if (!$useSubCount && $countResult === false) {
            $countResult = $model::subCount($find);
        }
        
        return is_countable($countResult) ? count($countResult) : (int)$countResult;
    }
    
    /**
     * Saving model automagically
     *
     * Note:
     * If a newly created entity can't be retrieved using the ->getSingle
     * method after it's creation, the entity will be returned directly
     *
     * @TODO Support Composite Primary Key*
     */
    protected function save(?int $id = null, ?ModelInterface $entity = null, ?array $post = null, ?string $modelName = null, ?array $whiteList = null, ?array $columnMap = null, ?array $with = null): array
    {
        $single = false;
        $retList = [];
        
        // Get the model name to play with
        $modelName ??= $this->getModelName();
        $post ??= $this->getParams();
        $whiteList ??= $this->getWhiteList();
        $columnMap ??= $this->getColumnMap();
        $with ??= $this->getWith();
        $id = (int)$id;
        
        // Check if multi-d post
        if (!empty($id) || !isset($post[0]) || !is_array($post[0])) {
            $single = true;
            $post = [$post];
        }
        
        // Save each posts
        foreach ($post as $key => $singlePost) {
            $singlePostId = (!$single || empty($id)) ? $this->getParam('id', 'int', $this->getParam('int', 'int', $singlePost['id'] ?? null)) : $id;
            if (isset($singlePost['id'])) {
                unset($singlePost['id']);
            }
            
            /** @var \Zemit\Mvc\Model $singlePostEntity */
            $singlePostEntity = (!$single || !isset($entity)) ? $this->getSingle($singlePostId, $modelName, []) : $entity;
            
            // Create entity if not exists
            if (!$singlePostEntity && empty($singlePostId)) {
                $singlePostEntity = new $modelName();
            }
            
            if (!$singlePostEntity) {
                $ret = [
                    'saved' => false,
                    'messages' => [new Message('Entity id `' . $singlePostId . '` not found.', $modelName, 'NotFound', 404)],
                    'model' => $modelName,
                    'source' => (new $modelName())->getSource(),
                ];
            }
            else {
                // allow custom manipulations
                // @todo move this using events
                $this->beforeAssign($singlePostEntity, $singlePost, $whiteList, $columnMap);
                
                // assign & save
                $singlePostEntity->assign($singlePost, $whiteList, $columnMap);
                $ret = $this->saveEntity($singlePostEntity);
                
                // refetch & expose
//                $fetchWith = $this->getSingle($singlePostEntity->getId(), $modelName, $with);
//                $ret['single'] = $this->expose($fetchWith);
                $fetchWith = $singlePostEntity->load($with ?? []);
                $ret['single'] = $this->expose($fetchWith);
            }
            
            if ($single) {
                return $ret;
            }
            else {
                $retList [] = $ret;
            }
        }
        
        return $retList;
    }
    
    /**
     * Allow overrides to add alter variables before entity assign & save
     */
    public function beforeAssign(ModelInterface &$entity, array &$post, ?array &$whiteList, ?array &$columnMap): void
    {
    }
    
    /**
     * Save an entity and return an array of the result
     *
     */
    public function saveEntity(ModelInterface $entity): array
    {
        $ret = [];
        
        $ret['saved'] = $entity->save();
        $ret['messages'] = $entity->getMessages();
        $ret['model'] = get_class($entity);
        $ret['source'] = $entity->getSource();
//        $ret['entity'] = $entity; // @todo this is to fix a phalcon internal bug (503 segfault during eagerload)
        $ret['single'] = $this->expose($entity, $this->getExpose());
        
        return $ret;
    }
    
    public function conditionsShouldBeHaving(?string $conditions)
    {
        return false;
        return preg_match('/GROUP_CONCAT\(.+?\)|COUNT\(.+?\)|SUM\(.+?\)|AVG\(.+?\)|MIN\(.+?\)|MAX\(.+?\)/i', $conditions);
    }
}
