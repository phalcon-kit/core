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

namespace PhalconKit\Mvc\Model\Behavior;

use Phalcon\Db\Column;
use Phalcon\Mvc\Model\MetaData\Exceptions\TableNotInDatabase;
use Phalcon\Mvc\Model\Behavior;
use Phalcon\Mvc\ModelInterface;
use PhalconKit\Models\User;
use PhalconKit\Models\Audit;
use PhalconKit\Models\AuditDetail;
use PhalconKit\Models\Interfaces\AuditDetailInterface;
use PhalconKit\Models\Interfaces\AuditInterface;
use PhalconKit\Exception\LogicException;
use PhalconKit\Mvc\Model;
use PhalconKit\Mvc\Model\Behavior\Traits\SkippableTrait;
use PhalconKit\Support\Helper;

/**
 * Blameable Behavior
 *
 * Provides a complete audit trail system for models.
 *
 * Responsibilities:
 * - Create a single audit entry per create/update operation (audit table)
 * - Optionally create per-column audit detail entries (audit_detail table)
 * - Support snapshot-based diffing for updates
 * - Support runtime and global enable/disable controls
 * - Prevent recursive auditing of audit tables themselves
 *
 * Control layers (in order of precedence):
 * 1. SkippableTrait                → disable the behavior entirely
 * 2. Audit toggle                  → enable/disable parent audit rows
 * 3. Per-record audit opt-out      → skip audit for one model instance
 * 4. Audit detail toggle           → enable/disable per-column audit details
 *
 * Scope: Phalcon registers model behaviors once per model class, so the
 * instance returned by `$model->getBlameableBehavior()` is shared by every
 * record of that class. `enable()`/`disable()`, `enableAudit()`/
 * `disableAudit()`, and the audit-detail toggles therefore apply to the whole
 * model class for the rest of the request/process. Use
 * `disableAuditFor($model)` to skip auditing for one record only.
 *
 * Update audits are written only when at least one audited column changed;
 * an update without a snapshot is always written because changes cannot be
 * ruled out.
 *
 * This design guarantees:
 * - Zero overhead when fully disabled
 * - Deterministic behavior across environments
 * - Safe runtime toggling (importers, migrations, hot paths)
 */
class Blameable extends Behavior
{
    use SkippableTrait;

    /**
     * Parent audit ID used to link cascaded/related model changes.
     * This is intentionally static to propagate across nested saves.
     */
    protected static ?int $parentId = null;

    /**
     * Pre-update state captured in `beforeUpdate`, keyed by model instance.
     *
     * The behavior is shared by every record of a model class, so this state
     * must never live in a scalar property: it would leak one record's
     * snapshot into another record's audit. Entries are consumed by the next
     * audit of the same instance and disappear with the model.
     *
     * @var \WeakMap<ModelInterface, array{snapshot: array<array-key, mixed>, changedFields: array<array-key, mixed>}>|null
     */
    protected ?\WeakMap $pendingUpdates = null;

    /**
     * Records excluded from auditing with `disableAuditFor()`.
     *
     * @var \WeakMap<ModelInterface, true>|null
     */
    protected ?\WeakMap $auditDisabledModels = null;

    /**
     * Fully-qualified audit model class.
     */
    protected string $auditClass = Audit::class;

    /**
     * Fully-qualified audit detail model class.
     */
    protected string $auditDetailClass = AuditDetail::class;

    /**
     * Fully-qualified user model class.
     */
    protected string $userClass = User::class;

    /**
     * Behavior-level audit toggle (parent audit row).
     *
     * The behavior is shared by every record of the model class, so this
     * toggle is class-wide. Subclasses can redeclare this property to change
     * the default.
     */
    protected bool $auditEnabled = false;

    /**
     * Global audit toggle (parent audit row).
     */
    protected static bool $auditStaticEnabled = true;

    /**
     * Behavior-level audit detail toggle (per-column rows), class-wide like
     * `$auditEnabled`. Subclasses can redeclare this property to change the
     * default.
     */
    protected bool $auditDetailEnabled = true;

    /**
     * Global audit detail toggle (per-column rows).
     */
    protected static bool $auditDetailStaticEnabled = true;

    /**
     * Constructor
     *
     * Accepts configuration options to control:
     * - Model classes
     * - Initial audit/audit-detail enablement
     *
     * Audit is opt-in with `auditEnabled => true`. Audit detail remains enabled
     * by default once audit itself is enabled and can be disabled with
     * `auditDetailEnabled => false`. Explicit options override subclass
     * property defaults.
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);

        $this->userClass = $options['userClass'] ?? $this->userClass;
        $this->auditClass = $options['auditClass'] ?? $this->auditClass;
        $this->auditDetailClass = $options['auditDetailClass'] ?? $this->auditDetailClass;

        $this->auditEnabled = $options['auditEnabled'] ?? $this->auditEnabled;
        $this->auditDetailEnabled = $options['auditDetailEnabled'] ?? $this->auditDetailEnabled;
    }

    /**
     * Receives model lifecycle events.
     *
     * This method acts as the central gatekeeper and enforces
     * all enable/disable semantics before any work is done.
     *
     * Returning null short-circuits the event handling cleanly.
     *
     * @throws LogicException When the behavior is attached to an incompatible
     *     model instance.
     */
    #[\Override]
    public function notify(string $type, ModelInterface $model): ?bool
    {
        // Hard stop: behavior disabled, audit disabled, or this record opted out
        if (
            !$this->isEnabled() ||
            !$this->isAuditEnabledFor($model)
        ) {
            if ($type === 'afterCreate' || $type === 'afterUpdate') {
                $this->takePendingUpdate($model);
            }
            return null;
        }

        // Prevent recursive auditing of audit tables
        if ($model instanceof $this->auditClass || $model instanceof $this->auditDetailClass) {
            return null;
        }

        $model = $this->requireBlameableModel($model);

        return match ($type) {
            'afterCreate', 'afterUpdate' => $this->createAudit($type, $model),
            'beforeUpdate' => $this->collectData($model),
            default => null,
        };
    }

    /**
     * Creates a parent audit entry and optional audit detail entries.
     *
     * - Creates a single audit row when enabled
     * - Conditionally creates per-column audit details
     * - Uses the snapshot + changed fields captured for this same model
     *   instance in `beforeUpdate`; create audits never carry a "before"
     * - Skips update audits whose snapshot shows no audited column changed,
     *   returning true without writing a row
     *
     * @return bool True when the audit was saved or intentionally skipped.
     *
     * @throws LogicException When configured audit classes do not implement the
     *     required audit contracts.
     */
    public function createAudit(string $type, Model $model): bool
    {
        // Normalize event name (create / update)
        $event = lcfirst(
            Helper::uncamelize(
                str_replace(['before', 'after'], '', $type)
            )
        );

        $metaData = $model->getModelsMetaData();
        $columns = $metaData->getAttributes($model);
        $columnMap = $metaData->getColumnMap($model);
        $columnTypes = $metaData->getDataTypes($model);

        // Consume the state captured for this record; never reuse another's.
        $pending = $this->takePendingUpdate($model);
        $snapshot = $event === 'update' ? ($pending['snapshot'] ?? null) : null;
        $changed = $event === 'update' ? ($pending['changedFields'] ?? null) : null;

        // Per-column diff, shared by the "unchanged update" check and details.
        $diff = [];
        foreach ($columns as $column) {
            $map = $columnMap[$column] ?? $column;
            $columnType = $columnTypes[$column] ?? null;

            $before = $this->normalizeValue($snapshot[$map] ?? null, $columnType);
            $after = $this->normalizeValue($model->readAttribute($map), $columnType);

            // Skip unchanged fields on update
            if (
                $snapshot !== null &&
                $changed !== null &&
                ($before === $after || !in_array($map, $changed, true))
            ) {
                continue;
            }

            $diff[$column] = ['map' => $map, 'before' => $before, 'after' => $after];
        }

        // An update proven unchanged by its snapshot is not worth an audit row.
        if ($event === 'update' && $snapshot !== null && $changed !== null && $diff === []) {
            self::$parentId = null;
            return true;
        }

        $auditClass = $this->auditClass;
        $audit = new $auditClass();

        $audit = $this->requireAuditRecord($audit);

        // Populate core audit metadata
        $audit->setModel($model::class);
        $audit->setTable($model->getSource());
        $audit->setPrimary($model->readAttribute('id'));
        $audit->setEvent($event);

        $audit->setBefore($snapshot ? json_encode($this->normalizeArray($snapshot, $columnMap, $columnTypes)) : null);
        // `toArray()` can include assigned relations; normalize against metadata
        // so audit snapshots stay focused on mapped scalar columns.
        $audit->setAfter(json_encode($this->normalizeArray($model->toArray(), $columnMap, $columnTypes)));

        $audit->setParentId(self::$parentId);

        // Legacy compatibility: store column map on audit row
        $audit->assign([
            'columns' => $columnMap ? json_encode($columnMap) : null,
        ]);

        /**
         * Audit detail generation
         *
         * This block is completely skipped when audit detail is disabled,
         * ensuring no per-column overhead on hot paths.
         */
        if ($this->isAuditDetailEnabled()) {
            $details = [];
            $detailClass = $this->auditDetailClass;

            foreach ($diff as $column => ['map' => $map, 'before' => $before, 'after' => $after]) {
                $detail = $this->requireAuditDetailRecord(new $detailClass());

                $detail->setColumn($column);
                $detail->setBefore($before);
                $detail->setAfter($after);

                // Legacy compatibility fields
                $detail->assign([
                    'model' => $audit->getModel(),
                    'table' => $audit->getTable(),
                    'primary' => $audit->getPrimary(),
                    'event' => $event,
                    'map' => $map,
                ]);

                $details[] = $detail;
            }

            if ($details !== []) {
                $audit->assign(['AuditDetailList' => $details]);
            }
        }

        // Persist audit (and details via relationship)
        $saved = $this->saveAuditRecord($audit);

        // Propagate audit validation errors back to the source model
        foreach ($audit->getMessages() as $message) {
            $message->setField('Audit.' . $message->getField());
            $model->appendMessage($message);
        }

        // Track parent audit for cascading related saves
        self::$parentId = !empty($model->getDirtyRelated())
            ? $audit->getId()
            : null;

        return $saved;
    }

    /**
     * Require the event model to be a PhalconKit model.
     *
     * The audit behavior depends on PhalconKit model helpers such as metadata,
     * snapshots, attribute reads, and source resolution. This helper keeps the
     * event handler readable while producing a deterministic framework
     * exception if the behavior is attached to an incompatible native model.
     *
     * @param ModelInterface $model Model passed by Phalcon's behavior event.
     *
     * @return Model
     *
     * @throws LogicException When the model does not use PhalconKit's model
     *     base class.
     */
    protected function requireBlameableModel(ModelInterface $model): Model
    {
        if ($model instanceof Model) {
            return $model;
        }

        throw new LogicException(sprintf(
            'Blameable behavior requires an instance of "%s"; got "%s".',
            Model::class,
            get_debug_type($model)
        ));
    }

    /**
     * Require the configured audit class to implement the audit contract.
     *
     * Applications can override the audit model class through behavior options.
     * This guard reports an invalid override at the framework boundary instead
     * of failing later while populating audit metadata.
     *
     * @param mixed $audit Audit record created from the configured class.
     *
     * @return AuditInterface&Model
     *
     * @throws LogicException When the configured audit class is incompatible.
     */
    protected function requireAuditRecord(mixed $audit): AuditInterface&Model
    {
        if ($audit instanceof AuditInterface && $audit instanceof Model) {
            return $audit;
        }

        throw new LogicException(sprintf(
            'Blameable audit class must implement "%s" and extend "%s"; got "%s".',
            AuditInterface::class,
            Model::class,
            get_debug_type($audit)
        ));
    }

    /**
     * Save the audit record unless audit storage is explicitly absent.
     *
     * Missing audit tables are optional in some applications, so a concrete
     * `TableNotInDatabase` skips audit creation. The guard is intentionally at
     * the save boundary so fake/custom audit models that override persistence
     * still run normally.
     */
    protected function saveAuditRecord(AuditInterface&Model $audit): bool
    {
        try {
            return $audit->save();
        } catch (TableNotInDatabase) {
            return true;
        }
    }

    /**
     * Require the configured audit-detail class to implement the detail contract.
     *
     * @param mixed $detail Audit-detail record created from the configured
     *     class.
     *
     * @return AuditDetailInterface&Model
     *
     * @throws LogicException When the configured audit-detail class is
     *     incompatible.
     */
    protected function requireAuditDetailRecord(mixed $detail): AuditDetailInterface&Model
    {
        if ($detail instanceof AuditDetailInterface && $detail instanceof Model) {
            return $detail;
        }

        throw new LogicException(sprintf(
            'Blameable audit detail class must implement "%s" and extend "%s"; got "%s".',
            AuditDetailInterface::class,
            Model::class,
            get_debug_type($detail)
        ));
    }

    /**
     * Collects snapshot and changed field data prior to update.
     *
     * The data is stored for this model instance only and consumed by its
     * next audit. Returns true when a snapshot was captured and null
     * otherwise; it never returns false because a false result from a
     * `beforeUpdate` behavior cancels the save in Phalcon.
     */
    protected function collectData(Model $model): ?bool
    {
        $this->takePendingUpdate($model);

        if ($model->hasSnapshotData()) {
            $this->pendingUpdates()[$model] = [
                'snapshot' => $model->getSnapshotData(),
                'changedFields' => $model->getChangedFields(),
            ];
            return true;
        }

        return null;
    }

    /**
     * Remove and return the pre-update state captured for one model instance.
     *
     * @return array{snapshot: array<array-key, mixed>, changedFields: array<array-key, mixed>}|null
     */
    protected function takePendingUpdate(ModelInterface $model): ?array
    {
        $pendingUpdates = $this->pendingUpdates();
        $pending = $pendingUpdates[$model] ?? null;
        unset($pendingUpdates[$model]);

        return $pending;
    }

    /**
     * Lazily create the per-instance pre-update state map.
     *
     * @return \WeakMap<ModelInterface, array{snapshot: array<array-key, mixed>, changedFields: array<array-key, mixed>}>
     */
    protected function pendingUpdates(): \WeakMap
    {
        if ($this->pendingUpdates === null) {
            /** @var \WeakMap<ModelInterface, array{snapshot: array<array-key, mixed>, changedFields: array<array-key, mixed>}> $map */
            $map = new \WeakMap();
            $this->pendingUpdates = $map;
        }

        return $this->pendingUpdates;
    }

    /**
     * Lazily create the per-record audit opt-out map.
     *
     * @return \WeakMap<ModelInterface, true>
     */
    protected function auditDisabledModels(): \WeakMap
    {
        if ($this->auditDisabledModels === null) {
            /** @var \WeakMap<ModelInterface, true> $map */
            $map = new \WeakMap();
            $this->auditDisabledModels = $map;
        }

        return $this->auditDisabledModels;
    }

    /**
     * Normalize a scalar value according to its column type.
     *
     * This method is intentionally conservative:
     * - null is preserved
     * - empty string is preserved
     * - only values belonging to the same semantic domain are collapsed
     */
    protected function normalizeValue(mixed $value, ?int $columnType): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($columnType) {
            /* =========================
             * Boolean / Integer domain
             * ========================= */
            Column::TYPE_BOOLEAN,
            Column::TYPE_TINYINTEGER,
            Column::TYPE_INTEGER =>
            (int) $value,

            Column::TYPE_BIGINTEGER =>
            is_int($value) ? $value : (string) $value,

            /* =========================
             * Floating / decimal domain
             * ========================= */
            Column::TYPE_FLOAT,
            Column::TYPE_DOUBLE =>
            (float) $value,

            Column::TYPE_DECIMAL =>
            (string) $value, // NEVER float

            /* =========================
             * Textual domain
             * ========================= */
            Column::TYPE_CHAR,
            Column::TYPE_VARCHAR,
            Column::TYPE_TEXT,
            Column::TYPE_LONGTEXT =>
            (string) $value,

            Column::TYPE_ENUM =>
            (string) $value,

            /* =========================
             * Date / time domain
             * ========================= */
            Column::TYPE_DATE =>
            is_string($value) ? substr($value, 0, 10) : (string) $value,

            Column::TYPE_DATETIME,
            Column::TYPE_TIMESTAMP =>
            is_string($value) ? substr($value, 0, 19) : (string) $value,

            Column::TYPE_TIME =>
            is_string($value) ? substr($value, 0, 8) : (string) $value,

            /* =========================
             * JSON domain
             * ========================= */
            Column::TYPE_JSON =>
            $this->normalizeJson($value),

            /* =========================
             * Binary domain
             * ========================= */
            Column::TYPE_BLOB,
            Column::TYPE_BINARY =>
            base64_encode((string) $value),

            /* =========================
             * Fallback
             * ========================= */
            default =>
            $value,
        };
    }

    protected function normalizeJson(mixed $value): string
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
        } else {
            $decoded = $value;
        }

        if (!is_array($decoded)) {
            $encoded = json_encode($decoded);
            return is_string($encoded) ? $encoded : 'null';
        }

        $this->ksortRecursive($decoded);

        $encoded = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return is_string($encoded) ? $encoded : 'null';
    }

    protected function ksortRecursive(array &$array): void
    {
        ksort($array);
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->ksortRecursive($value);
            }
        }
    }

    /**
     * Normalize a full row using model column metadata.
     *
     * Phalcon models can expose assigned relationships through `toArray()` and
     * snapshot data. Audit payloads are column snapshots, not response payloads,
     * so this method keeps only keys that map to real model columns before
     * normalizing scalar values by database type.
     */
    protected function normalizeArray(
        array $data,
        ?array $columnMap,
        array $columnTypes
    ): array {
        $normalized = [];

        foreach ($data as $key => $value) {
            $column = $this->resolveAuditColumnName($key, $columnMap, $columnTypes);

            if ($column === null) {
                continue;
            }

            $normalized[$key] = $this->normalizeValue(
                $value,
                $columnTypes[$column] ?? null
            );
        }

        return $normalized;
    }

    /**
     * Resolve one public/snapshot key to its database column name.
     *
     * With a non-empty column map, audit snapshots use mapped attribute names
     * while data types remain keyed by database column names. Without a map,
     * the key must already be a metadata column. Unknown keys are treated as
     * relation or transient payloads and are excluded from audit JSON.
     *
     * @param array<string, string>|null $columnMap Database column to model
     *     attribute map, or null when column renaming is disabled.
     * @param array<string, int> $columnTypes Database column types from
     *     Phalcon metadata.
     */
    protected function resolveAuditColumnName(
        string|int $key,
        ?array $columnMap,
        array $columnTypes
    ): ?string {
        $key = (string)$key;

        if (!empty($columnMap)) {
            $column = array_search($key, $columnMap, true);
            return is_string($column) ? $column : null;
        }

        return array_key_exists($key, $columnTypes) ? $key : null;
    }

    /* ============================================================
     * Audit (parent row) toggles
     * ============================================================
     */

    /**
     * Returns true if audit rows are enabled for this behavior and globally.
     *
     * The behavior is shared by every record of the model class; use
     * `isAuditEnabledFor()` to also honor a per-record opt-out.
     */
    public function isAuditEnabled(): bool
    {
        return $this->auditEnabled && self::$auditStaticEnabled;
    }

    /**
     * Enable audit rows for every record of the model class using this behavior.
     */
    public function enableAudit(): void
    {
        $this->auditEnabled = true;
    }

    /**
     * Disable audit rows for every record of the model class using this behavior.
     *
     * This is class-wide, not per record. Use `disableAuditFor($model)` to
     * skip auditing for a single record.
     */
    public function disableAudit(): void
    {
        $this->auditEnabled = false;
    }

    /**
     * Returns true if audit rows are enabled for this specific record.
     *
     * Requires the class-wide and global toggles to be enabled and the record
     * not to be opted out through `disableAuditFor()`.
     */
    public function isAuditEnabledFor(ModelInterface $model): bool
    {
        return $this->isAuditEnabled() && !isset($this->auditDisabledModels()[$model]);
    }

    /**
     * Skip audit rows for one record only, until `enableAuditFor()` is called.
     *
     * Other records of the same model class keep their audit trail. The
     * opt-out is held weakly and ends when the model instance is released.
     */
    public function disableAuditFor(ModelInterface $model): void
    {
        $this->auditDisabledModels()[$model] = true;
    }

    /**
     * Remove a per-record opt-out set by `disableAuditFor()`.
     *
     * This does not override the class-wide or global toggles.
     */
    public function enableAuditFor(ModelInterface $model): void
    {
        unset($this->auditDisabledModels()[$model]);
    }

    /**
     * Enable audit rows globally.
     */
    public static function staticEnableAudit(): void
    {
        self::$auditStaticEnabled = true;
    }

    /**
     * Disable audit rows globally.
     */
    public static function staticDisableAudit(): void
    {
        self::$auditStaticEnabled = false;
    }

    /* ============================================================
     * Audit detail (per-column) toggles
     * ============================================================
     */

    /**
     * Returns true if audit detail rows are enabled for this behavior (class-wide) and globally.
     */
    public function isAuditDetailEnabled(): bool
    {
        return $this->auditDetailEnabled && self::$auditDetailStaticEnabled;
    }

    /**
     * Enable audit detail rows for every record of the model class.
     */
    public function enableAuditDetail(): void
    {
        $this->auditDetailEnabled = true;
    }

    /**
     * Disable audit detail rows for every record of the model class.
     */
    public function disableAuditDetail(): void
    {
        $this->auditDetailEnabled = false;
    }

    /**
     * Enable audit detail rows globally.
     */
    public static function staticEnableAuditDetail(): void
    {
        self::$auditDetailStaticEnabled = true;
    }

    /**
     * Disable audit detail rows globally.
     */
    public static function staticDisableAuditDetail(): void
    {
        self::$auditDetailStaticEnabled = false;
    }
}
