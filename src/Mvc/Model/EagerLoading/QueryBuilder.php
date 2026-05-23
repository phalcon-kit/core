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

namespace PhalconKit\Mvc\Model\EagerLoading;

use Phalcon\Mvc\Model\Query\Builder;
use Phalcon\Mvc\Model\Query\BuilderInterface;
use PhalconKit\Exception\LogicException;

final class QueryBuilder extends Builder
{
    public const string E_NOT_ALLOWED_METHOD_CALL = 'When eager loading relations queries must return full entities';
    
    /**
     * @param mixed $distinct
     * @return BuilderInterface
     * @throws LogicException
     */
    #[\Override]
    public function distinct($distinct): BuilderInterface
    {
        throw new LogicException(self::E_NOT_ALLOWED_METHOD_CALL);
    }
    
    /**
     * @param mixed $columns
     * @return BuilderInterface
     * @throws LogicException
     */
    #[\Override]
    public function columns($columns): BuilderInterface
    {
        throw new LogicException(self::E_NOT_ALLOWED_METHOD_CALL);
    }
    
    /**
     * Replacing where to andWhere in order to avoid loosing relationship conditions
     */
    #[\Override]
    public function where(string $conditions, array $bindParams = [], array $bindTypes = []): BuilderInterface
    {
        if (!empty($this->conditions)) {
            $appendCondition = is_array($this->conditions) ? implode(') AND (', $this->conditions) : $this->conditions;
            $conditions = '(' . $appendCondition . ') AND (' . $conditions . ')';
        }
        return parent::where($conditions, $bindParams, $bindTypes);
    }
}
