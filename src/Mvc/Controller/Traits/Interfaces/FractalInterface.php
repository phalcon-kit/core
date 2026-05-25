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

use League\Fractal\Serializer\SerializerAbstract;
use League\Fractal\TransformerAbstract;
use Phalcon\Mvc\Model\ResultsetInterface;
use PhalconKit\Fractal\Manager;

/**
 * Contract for Fractal-backed REST response transformation.
 *
 * Controllers can use these methods to transform models, resultsets, and
 * arbitrary values through a configured Fractal manager and transformer.
 */
interface FractalInterface
{
    /**
     * Return the current Fractal manager, creating one when needed.
     */
    public function getFractalManager(): Manager;
    
    /**
     * Replace or reset the current Fractal manager.
     */
    public function setFractalManager(?Manager $manager): void;
    
    /**
     * Return the serializer used by new Fractal managers.
     */
    public function getFractalSerializer(): SerializerAbstract;
    
    /**
     * Set the serializer used by new Fractal managers.
     */
    public function setFractalSerializer(SerializerAbstract $serializer): void;
    
    /**
     * Return the controller's configured transformer.
     */
    public function getTransformer(): TransformerAbstract;
    
    /**
     * Replace or reset the controller's transformer.
     */
    public function setTransformer(?TransformerAbstract $transformer = null): void;
    
    /**
     * Determine whether a transformer is currently configured.
     */
    public function hasTransformer(): bool;
    
    /**
     * Transform one Phalcon model.
     *
     * @return array<array-key, mixed>|null
     */
    public function transformModel(\Phalcon\Mvc\ModelInterface $model, ?TransformerAbstract $transformer = null, ?Manager $fractalManager = null): ?array;
    
    /**
     * Transform a Phalcon model resultset.
     *
     * @return array<array-key, mixed>|null
     */
    public function transformResultset(ResultsetInterface $resultset, ?TransformerAbstract $transformer = null, ?Manager $fractalManager = null): ?array;
    
    /**
     * Transform one arbitrary item.
     *
     * @return array<array-key, mixed>|null
     */
    public function transformItem(mixed $data, ?TransformerAbstract $transformer = null, ?Manager $fractalManager = null): ?array;
    
    /**
     * Transform an arbitrary collection.
     *
     * @return array<array-key, mixed>|null
     */
    public function transformCollection(mixed $data, ?TransformerAbstract $transformer = null, ?Manager $fractalManager = null): ?array;
}
