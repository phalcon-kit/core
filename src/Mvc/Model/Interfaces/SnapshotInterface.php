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

use PhalconKit\Mvc\Model\Behavior\Snapshot as SnapshotBehavior;

interface SnapshotInterface
{
    /**
     * Initialize snapshot support for the model.
     *
     * Implementations should configure Phalcon's native snapshot tracking and
     * attach the framework snapshot behavior. When no options are provided, the
     * model options manager is expected to provide the `snapshot` option group.
     *
     * @param array<string, mixed>|null $options Snapshot behavior options.
     */
    public function initializeSnapshot(?array $options = null): void;

    /**
     * Register the snapshot behavior used by this model instance.
     *
     * The behavior is stored in the model behavior registry under the snapshot
     * key so downstream code can replace or inspect it without depending on the
     * internal trait composition.
     *
     * @param SnapshotBehavior $snapshotBehavior Behavior instance to register.
     */
    public function setSnapshotBehavior(SnapshotBehavior $snapshotBehavior): void;

    /**
     * Return the registered snapshot behavior.
     *
     * @return SnapshotBehavior The behavior attached to the model snapshot key.
     */
    public function getSnapshotBehavior(): SnapshotBehavior;

    /**
     * Return model fields whose raw values differ from the stored snapshot.
     *
     * The result is intended for audit, replication, domain comparison, and API
     * response code that needs application model field names instead of mixed
     * database-column/native dirty-field names. Implementations should compare
     * raw attributes, normalize column-map names, and fall back to native
     * getChangedFields() only when no snapshot data exists.
     *
     * @param array<int, string> $ignoreFields Database column or mapped model
     *     field names to omit, commonly lifecycle fields such as updatedAt,
     *     updatedBy, or updatedAs.
     * @return list<string> Mapped model fields that differ from the snapshot.
     */
    public function getSnapshotChangedFields(array $ignoreFields = []): array;

    /**
     * Build a callback that recalculates a value when a model field changed.
     *
     * This helper is used by model behaviors that need to keep an existing raw
     * attribute when snapshots show the relevant value has not changed, while
     * still recalculating for new records or records without snapshot data.
     *
     * @param callable $callback Recalculation callback receiving the model and
     *     field name.
     * @param bool $anyField Whether any changed field should trigger the
     *     callback, or only the requested field.
     * @return \Closure Callback wrapper for behavior option definitions.
     */
    public function hasChangedCallback(callable $callback, bool $anyField = true): \Closure;
}
