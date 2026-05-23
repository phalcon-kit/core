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

use Phalcon\Config\ConfigInterface;
use Phalcon\Db\Adapter\AdapterInterface;
use Phalcon\Events\ManagerInterface;
use PhalconKit\Exception\ServiceException;
use PhalconKit\Mvc\Model\Traits\Abstracts\AbstractEventsManager;
use PhalconKit\Mvc\Model\Traits\Abstracts\AbstractInjectable;

/**
 * Coordinates read/write connection selection around replica lag.
 *
 * When MySQL read replicas are enabled in config, the trait records a short
 * cooldown after write events. During that cooldown reads continue using the
 * write connection so application code does not immediately read stale replica
 * state after creating, updating, deleting, restoring, or reordering a model.
 */
trait Replication
{
    use AbstractEventsManager;
    use AbstractInjectable;
    use Options;

    /**
     * Set the default connection service used by Phalcon for this model.
     *
     * Implemented by Phalcon's model base class.
     *
     * @param string $connectionService DI service name for the default
     *     connection.
     */
    abstract public function setConnectionService(string $connectionService): void;

    /**
     * Set the read connection service used by Phalcon for this model.
     *
     * @param string $connectionService DI service name for read operations.
     */
    abstract public function setReadConnectionService(string $connectionService): void;

    /**
     * Set the write connection service used by Phalcon for this model.
     *
     * @param string $connectionService DI service name for write operations.
     */
    abstract public function setWriteConnectionService(string $connectionService): void;

    /**
     * Return the configured write connection service name.
     *
     * @return string DI service name for write operations.
     */
    abstract public function getWriteConnectionService(): string;

    /**
     * Return the configured read connection service name.
     *
     * @return string DI service name for read operations.
     */
    abstract public function getReadConnectionService(): string;
    
    /**
     * Replica lag window in milliseconds.
     *
     * A null value means replication behavior has not been initialized yet.
     */
    protected static ?int $replicationLag = null;
    
    /**
     * Unix timestamp in milliseconds after which replica reads may resume.
     *
     * A null value means the replica is considered ready immediately.
     */
    protected static ?int $replicationReadyAt = null;
    
    /**
     * Return the configured replica lag window in milliseconds.
     *
     * @return int|null Lag window, or null before replication initialization.
     */
    public static function getReplicationLag(): ?int
    {
        return self::$replicationLag;
    }
    
    /**
     * Set the replica lag window in milliseconds.
     *
     * @param int|null $replicationLag Lag window to use after write events, or
     *     null to clear the value.
     */
    public static function setReplicationLag(?int $replicationLag = null): void
    {
        self::$replicationLag = $replicationLag;
    }
    
    /**
     * Return the timestamp after which replica reads may resume.
     *
     * @return int|null Unix timestamp in milliseconds, or null when reads are
     *     not currently pinned to the write connection.
     */
    public static function getReplicationReadyAt(): ?int
    {
        return self::$replicationReadyAt;
    }
    
    /**
     * Set the timestamp after which replica reads may resume.
     *
     * @param int|null $replicationReadyAt Unix timestamp in milliseconds, or
     *     null to mark the replica as ready.
     */
    public static function setReplicationReadyAt(?int $replicationReadyAt = null): void
    {
        self::$replicationReadyAt = $replicationReadyAt;
    }
    
    /**
     * Initialize read/write connection services for replica-aware models.
     *
     * The trait reads `database.drivers.mysql.readonly.enable` from the config
     * service. When enabled, it configures connection service names and attaches
     * write-event listeners that temporarily pin reads to the write connection.
     *
     * @param array<array-key, mixed>|null $options Optional replication
     *     options. Supported keys are `lag`, `connectionService`,
     *     `readConnectionService`, and `writeConnectionService`.
     *
     * @throws ServiceException When the config service cannot be resolved
     *     through the PhalconKit DI contract.
     */
    public function initializeReplication(?array $options = null): void
    {
        $options ??= $this->getOptionsManager()->get('replication') ?? [];
        
        $config = $this->getTypedService('config', ConfigInterface::class, 'model replication helpers');
        
        $enabled = $config->path('database.drivers.mysql.readonly.enable', false);
        if ($enabled) {
            self::setReplicationLag($options['lag'] ?? 1000);
            $this->setConnectionService($options['connectionService'] ?? 'db');
            $this->setReadConnectionService($options['readConnectionService'] ?? 'dbr');
            $this->setWriteConnectionService($options['writeConnectionService'] ?? 'db');
            $this->addReadWriteConnectionBehavior();
        }
    }
    
    /**
     * Select the connection used for model reads.
     *
     * When the replica delay has elapsed, the method validates that the read
     * connection service can be resolved. The returned connection remains the
     * write connection to preserve the existing consistency-first behavior.
     *
     * @return AdapterInterface Write database connection service.
     * @throws ServiceException When the read or write connection service cannot
     *     be resolved through the PhalconKit DI contract.
     */
    public function selectReadConnection(): AdapterInterface
    {
        // Check if the replication is ready
        if ($this->isReplicationReady()) {
            // Use the read connection service
            $this->getTypedService(
                $this->getReadConnectionService(),
                AdapterInterface::class,
                'model replication helpers'
            );
        }
        
        // Use write connection service
        return $this->getTypedService(
            $this->getWriteConnectionService(),
            AdapterInterface::class,
            'model replication helpers'
        );
    }
    
    /**
     * Attach lifecycle listeners that pin reads to the write connection.
     *
     * Each write-like event updates `replicationReadyAt` to `now + lag`. Native
     * Phalcon requires a compatible events manager to attach these callbacks.
     *
     * @throws ServiceException When the model events manager is missing or does
     *     not implement Phalcon's events manager contract.
     */
    public function addReadWriteConnectionBehavior(): void
    {
        $forceMasterConnectionService = function (): void {
            $lag = self::getReplicationLag() ?? 0;
            self::setReplicationReadyAt(self::nowMs() + $lag);
        };
        
        // Direct listener attachment preserves existing behavior. A reusable
        // behavior object or idempotency guard would need a public lifecycle
        // contract for models that call this more than once.
        $eventsManager = $this->getEventsManager();
        if (!$eventsManager instanceof ManagerInterface) {
            throw new ServiceException(sprintf(
                'Expected model events manager for model replication helpers to be an instance of "%s"; got "%s".',
                ManagerInterface::class,
                get_debug_type($eventsManager)
            ));
        }
        
        $eventsManager->attach('model:afterSave', $forceMasterConnectionService);
        $eventsManager->attach('model:afterCreate', $forceMasterConnectionService);
        $eventsManager->attach('model:afterUpdate', $forceMasterConnectionService);
        $eventsManager->attach('model:afterDelete', $forceMasterConnectionService);
        $eventsManager->attach('model:afterRestore', $forceMasterConnectionService);
    }
    
    /**
     * Determine whether reads may use a replica again.
     *
     * When the cooldown has expired, the ready timestamp is cleared so future
     * calls remain ready until another write event updates it.
     *
     * @return bool True when the replica cooldown is absent or expired.
     */
    public function isReplicationReady(): bool
    {
        $replicationReadyAt = self::getReplicationReadyAt();
        
        if (empty($replicationReadyAt) || $replicationReadyAt < self::nowMs()) {
            self::setReplicationReadyAt(null);
            return true;
        }
        
        return false;
    }
    /**
     * Return the current process time in milliseconds.
     *
     * This helper keeps replication timestamps integer-based and easy to
     * compare without leaking floating-point microtime values into public
     * replication state.
     *
     * @return int Unix timestamp in milliseconds.
     */
    protected static function nowMs(): int
    {
        // floor() avoids rounding up when converting to int
        return (int) round(microtime(true) * 1000.0);
    }
}
