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

use Phalcon\Mvc\Model\Resultset\Simple;
use Phalcon\Mvc\Model\ResultsetInterface;
use Phalcon\Mvc\Model\Row;
use Phalcon\Mvc\ModelInterface;
use PhalconKit\Exception\LogicException;
use PhalconKit\Mvc\Model\Traits\Abstracts\AbstractInstance;
use PhalconKit\Support\Helper;

trait Events
{
    use AbstractInstance;
    
    abstract public function fireEventCancel(string $eventName): bool;

    private static function ensureTraversableResultset(ResultsetInterface $resultset): ResultsetInterface&\Traversable
    {
        if (!$resultset instanceof \Traversable) {
            throw new LogicException('Phalcon model find() returned a non-traversable resultset.');
        }

        return $resultset;
    }
    
    /**
     * Retrieves records from the database that match the specified conditions.
     *
     * @see \Phalcon\Mvc\Model::find()
     * @param mixed $parameters Optional native Phalcon find parameters. The
     *     public signature stays broad to match PhalconKit's patched Phalcon
     *     model stubs, while callers normally pass an array, string, integer
     *     primary key, or null.
     * @return ResultsetInterface&\Traversable Returns the result set, or an empty result set if the operation is canceled.
     */
    #[\Override]
    public static function find(mixed $parameters = null): ResultsetInterface
    {
        $instance = self::loadInstance();
        $event = ucfirst(Helper::camelize(__FUNCTION__));

        if ($instance->fireEventCancel('before' . $event) === false) {
            return self::ensureTraversableResultset(new Simple(null, $instance, false));
        }

        $ret = parent::find($parameters);

        $instance->fireEvent('after' . $event);

        return self::ensureTraversableResultset($ret);
    }
    
    /**
     * Finds the first record that matches the given parameters.
     *
     * @see \Phalcon\Mvc\Model::findFirst()
     * @param mixed $parameters Optional native Phalcon find-first parameters.
     *     The public signature stays broad to match PhalconKit's patched
     *     Phalcon model stubs, while callers normally pass an array, string,
     *     integer primary key, or null.
     * @return ModelInterface|Row|false|null The first matching record, or null if no record is found or false if the operation is canceled.
     */
    #[\Override]
    public static function findFirst(mixed $parameters = null): ModelInterface|Row|false|null
    {
        return self::fireEventCancelCall(__FUNCTION__, fn(): mixed => parent::findFirst($parameters));
    }
    
    /**
     * Counts the number of records that match the given parameters.
     *
     * This method wraps the core static `count` model call with beforeCount/afterCount cancellable events.
     * The "beforeCount" event can cancel the operation. Since Phalcon 5.14's
     * native contract cannot return false for count(), cancellation returns 0.
     *
     * @see \Phalcon\Mvc\Model::count()
     * @param mixed $parameters Optional native Phalcon count parameters.
     * @return ResultsetInterface|int The count result or a ResultsetInterface, depending on the implementation.
     */
    #[\Override]
    public static function count(mixed $parameters = null): ResultsetInterface|int
    {
        $count = self::fireEventCancelCall(__FUNCTION__, fn(): mixed => parent::count($parameters));

        if ($count === false) {
            return 0;
        }
        
        if (is_string($count)) {
            return (int)$count;
        }
        
        return $count;
    }
    
    /**
     * Executes a sum operation on the underlying data with optional parameters.
     * This method supports cancellable events triggered before and after execution.
     * If the "beforeSum" event cancels the operation, this method returns 0.0
     * to satisfy Phalcon 5.14's native return contract.
     *
     * @see \Phalcon\Mvc\Model::sum()
     * @param mixed $parameters Optional native Phalcon sum parameters.
     * @return ResultsetInterface|float Returns the sum result as a float or a result set interface.
     */
    #[\Override]
    public static function sum(mixed $parameters = null): ResultsetInterface|float
    {
        $sum = self::fireEventCancelCall(__FUNCTION__, fn(): mixed => parent::sum($parameters));

        if ($sum === false) {
            return 0.0;
        }
        
        if (is_string($sum)) {
            return floatval($sum);
        }
        
        return $sum;
    }
    
    /**
     * Calculates the average of results based on the provided parameters. It wraps the method execution
     * with before/after cancellable events.
     *
     * Example events triggered:
     * - beforeAverage()
     * - afterAverage()
     *
     * If the "beforeAverage" event cancels the operation, 0.0 is returned to
     * satisfy Phalcon 5.14's native return contract.
     * @see \Phalcon\Mvc\Model::average()
     * @param array $parameters Parameters to define the criteria for calculating the average.
     * @return ResultsetInterface|float The calculated average or a ResultsetInterface, depending on the implementation.
     */
    #[\Override]
    public static function average(array $parameters = []): ResultsetInterface|float
    {
        $average = self::fireEventCancelCall(__FUNCTION__, fn(): mixed => parent::average($parameters));

        if ($average === false) {
            return 0.0;
        }
        
        if (is_string($average)) {
            return (float)$average;
        }
        
        return $average;
    }
    
    /**
     * Calculates the minimum value of a specified column in the database according to the given conditions.
     *
     * @param mixed $parameters Native Phalcon parameters to customize the query,
     *     such as conditions, column selection, or groupings.
     * @return ResultsetInterface|float|false Returns the minimum value as a float, a ResultsetInterface object, or false if no matching records are found or the operation fails.
     */
    #[\Override]
    public static function minimum(mixed $parameters = null): ResultsetInterface|float|false
    {
        $minimum = self::fireEventCancelCall(__FUNCTION__, fn(): mixed => parent::minimum($parameters));
        
        if (is_string($minimum)) {
            return (float)$minimum;
        }
        
        return $minimum;
    }
    
    /**
     * Calculates the maximum value of a specified column in the database based on the given conditions.
     *
     * @param mixed $parameters Native Phalcon parameters to customize the query,
     *     such as conditions, column selection, or groupings.
     * @return ResultsetInterface|float|false Returns the computed maximum value as a float, a ResultsetInterface object for detailed results, or false on failure.
     */
    #[\Override]
    public static function maximum(mixed $parameters = null): ResultsetInterface|float|false
    {
        $maximum = self::fireEventCancelCall(__FUNCTION__, fn(): mixed => parent::maximum($parameters));
        
        if (is_string($maximum)) {
            return (float)$maximum;
        }
        
        return $maximum;
    }
    
    /**
     *  Wraps core static model calls (find, findFirst, count, sum, average, minimum, maximum)
     *  with beforeX/afterX cancellable events.
     *
     *  Example (beforeX/afterX events):
     *  - beforeAverage()
     *  - beforeSum()
     *  - beforeCount()
     *  - beforeFind()
     *  - beforeFindFirst()
     *  - afterAverage()
     *  - afterSum()
     *  - afterCount()
     *  - afterFind()
     *  - afterFindFirst()
     *
     *  Returns false if the "beforeX" event cancels the operation. Callers
     *  whose native Phalcon contracts cannot return false must normalize this
     *  sentinel before returning.
     *
     * @param string $method
     * @param callable $callable
     * @return mixed
     */
    public static function fireEventCancelCall(string $method, callable $callable): mixed
    {
        $instance = self::loadInstance();
        $event = ucfirst(Helper::camelize($method));
        
        if ($instance->fireEventCancel('before' . $event) === false) {
            return false;
        }
        
        $ret = $callable();
        
        $instance->fireEvent('after' . $event);
        
        return $ret;
    }
}
