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
use Phalcon\Mvc\Model\Behavior;
use Phalcon\Mvc\ModelInterface;
use PhalconKit\Models\User;
use PhalconKit\Models\Audit;
use PhalconKit\Models\AuditDetail;
use PhalconKit\Models\Interfaces\AuditDetailInterface;
use PhalconKit\Models\Interfaces\AuditInterface;
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
 * 3. Audit detail toggle           → enable/disable per-column audit details
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
     * Snapshot of model data before update.
     * Populated during beforeUpdate.
     */
    protected ?array $snapshot = null;

    /**
     * List of changed fields detected by Phalcon.
     * Used to filter unchanged columns on update.
     */
    protected ?array $changedFields = null;

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
     * Instance-level audit toggle (parent audit row).
     */
    protected bool $auditEnabled = true;

    /**
     * Global audit toggle (parent audit row).
     */
    protected static bool $auditStaticEnabled = true;

    /**
     * Instance-level audit detail toggle (per-column rows).
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
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);

        $this->userClass = $options['userClass'] ?? $this->userClass;
        $this->auditClass = $options['auditClass'] ?? $this->auditClass;
        $this->auditDetailClass = $options['auditDetailClass'] ?? $this->auditDetailClass;

        $this->auditEnabled = $options['auditEnabled'] ?? true;
        $this->auditDetailEnabled = $options['auditDetailEnabled'] ?? true;
    }

    /**
     * Receives model lifecycle events.
     *
     * This method acts as the central gatekeeper and enforces
     * all enable/disable semantics before any work is done.
     *
     * Returning null short-circuits the event handling cleanly.
     *
     * @throws \Exception
     */
    #[\Override]
    public function notify(string $type, ModelInterface $model): ?bool
    {
        // Hard stop: behavior disabled or audit disabled
        if (
            !$this->isEnabled() ||
            !$this->isAuditEnabled()
        ) {
            return null;
        }

        // Prevent recursive auditing of audit tables
        if ($model instanceof $this->auditClass || $model instanceof $this->auditDetailClass) {
            return null;
        }

        assert($model instanceof Model);

        return match ($type) {
            'afterCreate', 'afterUpdate' => $this->createAudit($type, $model),
            'beforeUpdate' => $this->collectData($model),
            default => null,
        };
    }

    /**
     * Creates a parent audit entry and optional audit detail entries.
     *
     * - Always creates a single audit row when enabled
     * - Conditionally creates per-column audit details
     * - Uses snapshot + changed fields to minimize noise
     *
     * @throws \Exception
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

        $changed = $this->changedFields;
        $snapshot = $this->snapshot;

        $auditClass = $this->auditClass;
        $audit = new $auditClass();

        assert($audit instanceof AuditInterface);

        // Populate core audit metadata
        $audit->setModel($model::class);
        $audit->setTable($model->getSource());
        $audit->setPrimary($model->readAttribute('id'));
        $audit->setEvent($event);

        $audit->setBefore($snapshot ? json_encode($this->normalizeArray($snapshot, $columnMap, $columnTypes)) : null);
        $audit->setAfter(json_encode($this->normalizeArray($model->toArray(), $columnMap, $columnTypes))); // @todo fix because toArray returns relations

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

            foreach ($columns as $column) {
                $map = $columnMap[$column] ?? $column;
                $type = $columnTypes[$column] ?? null;

                $before = $this->normalizeValue($snapshot[$map] ?? null, $type);
                $after  = $this->normalizeValue($model->readAttribute($map), $type);

                // Skip unchanged fields on update
                if (
                    $event === 'update' &&
                    $changed !== null &&
                    $snapshot !== null &&
                    ($before === $after || !in_array($map, $changed, true))
                ) {
                    continue;
                }

                $detail = new $detailClass();
                assert($detail instanceof AuditDetailInterface);

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
        $saved = $audit->save();

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
     * Collects snapshot and changed field data prior to update.
     *
     * This method is intentionally lightweight and only runs
     * when snapshots are available.
     */
    protected function collectData(Model $model): bool
    {
        if ($model->hasSnapshotData()) {
            $this->snapshot = $model->getSnapshotData();
            $this->changedFields = $model->getChangedFields();
            return true;
        }

        $this->snapshot = null;
        $this->changedFields = null;
        return false;
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
            return json_encode($decoded);
        }

        $this->ksortRecursive($decoded);

        return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
     * Normalize a full row using column metadata.
     */
    protected function normalizeArray(
        array $data,
        ?array $columnMap,
        array $columnTypes
    ): array {
        foreach ($data as $key => $value) {
            $column = $columnMap
                ? array_search($key, $columnMap, true) ?: $key
                : $key;

            $data[$key] = $this->normalizeValue(
                $value,
                $columnTypes[$column] ?? null
            );
        }

        return $data;
    }

    /* ============================================================
     * Audit (parent row) toggles
     * ============================================================
     */

    /**
     * Returns true if audit rows are enabled for this instance and globally.
     */
    public function isAuditEnabled(): bool
    {
        return $this->auditEnabled && self::$auditStaticEnabled;
    }

    /**
     * Enable audit rows for this instance.
     */
    public function enableAudit(): void
    {
        $this->auditEnabled = true;
    }

    /**
     * Disable audit rows for this instance.
     */
    public function disableAudit(): void
    {
        $this->auditEnabled = false;
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
     * Returns true if audit detail rows are enabled for this instance and globally.
     */
    public function isAuditDetailEnabled(): bool
    {
        return $this->auditDetailEnabled && self::$auditDetailStaticEnabled;
    }

    /**
     * Enable audit detail rows for this instance.
     */
    public function enableAuditDetail(): void
    {
        $this->auditDetailEnabled = true;
    }

    /**
     * Disable audit detail rows for this instance.
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
