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

namespace PhalconKit\Mvc\Model\Interfaces;

use Phalcon\Contracts\Db\Adapter\Adapter as AdapterContract;

interface ReplicationInterface
{
    public static function getReplicationLag(): ?int;
    
    public static function setReplicationLag(?int $replicationLag = null): void;
    
    public static function getReplicationReadyAt(): ?int;
    
    public static function setReplicationReadyAt(?int $replicationReadyAt = null): void;
    
    public function initializeReplication(?array $options = null): void;
    
    public function selectReadConnection(): AdapterContract;
    
    public function addReadWriteConnectionBehavior(): void;
    
    public function isReplicationReady(): bool;
}
