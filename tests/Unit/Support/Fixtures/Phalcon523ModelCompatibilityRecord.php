<?php

/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Support\Fixtures;

use Phalcon\Mvc\ModelInterface;
use PhalconKit\Mvc\Model;

/** Core model with application setters and mapped columns for native ORM regressions. */
final class Phalcon523ModelCompatibilityRecord extends Model
{
    public int $id = 37;
    public ?string $label = 'initial';
    public ?int $ownerId = 12;
    public string $connection_service = '';
    public int $labelSetterCalls = 0;
    public int $ownerSetterCalls = 0;
    public int $dynamicOwnerSetterCalls = 0;
    public bool $lateStateBound = false;
    public bool $rejectSave = false;
    public bool $rejectOwnerClear = false;
    public bool $rejectDynamicOwnerClear = false;
    protected ?self $owner = null;
    protected ?self $requiredOwner = null;

    public function initialize(): void
    {
        $this->setSource('model_compat_records');
        $this->keepSnapshots(true);
        $this->belongsTo('ownerId', self::class, 'id', ['alias' => 'owner']);
        $this->belongsTo('ownerId', self::class, 'id', ['alias' => 'otherOwner']);
        $this->belongsTo('ownerId', self::class, 'id', ['alias' => 'requiredOwner']);
        $this->belongsTo('ownerId', self::class, 'id', ['alias' => 'dynamicOwner']);
    }

    /** @return array<string, string> */
    public function columnMap(): array
    {
        return ['id' => 'id', 'label' => 'label', 'owner_id' => 'ownerId', 'connection_service' => 'connection_service'];
    }

    /** Observe opt-in native hydration without changing the persisted value. */
    public function setLabel(?string $label): void
    {
        $this->labelSetterCalls++;
        $this->label = $label;
    }

    /** An application relationship setter must clear its foreign key on null. */
    public function setOwner(?self $owner): void
    {
        $this->ownerSetterCalls++;
        if ($owner === null && $this->rejectOwnerClear) {
            throw new \DomainException('Owner change rejected.');
        }
        $this->owner = $owner;
        $this->ownerId = $owner?->id;
    }

    /** Reject persistence so the tests can verify rollback of a cleared relation. */
    public function validation(): bool
    {
        return !$this->rejectSave;
    }

    /** Exercise Phalcon's fallback when an older application setter rejects null. */
    public function setRequiredOwner(self $owner): void
    {
        $this->requiredOwner = $owner;
        $this->ownerId = $owner->id;
    }

    /** Reenter the same dynamic alias, then another alias, as an application setter may do. */
    public function setDynamicOwner(?self $owner): void
    {
        $this->dynamicOwnerSetterCalls++;
        if ($this->rejectDynamicOwnerClear) {
            throw new \DomainException('Dynamic owner change rejected.');
        }
        $this->dynamicOwner = $owner;
        $this->__set('owner', $owner);
    }

    /** Mark dispatch through the application model when late-state binding is enabled. */
    public static function cloneResultMap(
        mixed $base,
        array $data,
        mixed $columnMap,
        int $dirtyState = 0,
        ?bool $keepSnapshots = null
    ): ModelInterface {
        $record = parent::cloneResultMap($base, $data, $columnMap, $dirtyState, $keepSnapshots);
        $record->lateStateBound = true;

        return $record;
    }
}
