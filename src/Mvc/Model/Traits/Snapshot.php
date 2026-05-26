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

use Phalcon\Mvc\EntityInterface;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\MetaDataInterface;
use Phalcon\Mvc\ModelInterface;
use PhalconKit\Exception\LogicException;
use PhalconKit\Mvc\Model\Behavior\Snapshot as SnapshotBehavior;
use PhalconKit\Mvc\Model\Traits\Abstracts\AbstractBehavior;
use PhalconKit\Mvc\Model\Traits\Abstracts\AbstractEventsManager;
use PhalconKit\Mvc\Model\Traits\Abstracts\AbstractOptions;

/**
 * Trait that provides snapshot functionality for a model.
 */
trait Snapshot
{
    use AbstractEventsManager;
    use AbstractOptions;
    use AbstractBehavior;
    
    abstract protected function keepSnapshots(bool $keepSnapshot): void;

    abstract public function getModelsMetaData(): MetaDataInterface;

    abstract public function getChangedFields(): array;

    abstract public function getSnapshotData(): array;

    abstract public function hasSnapshotData(): bool;
    
    /**
     * Initialize the snapshot for the model.
     *
     * @param array|null $options An array of options for initializing the snapshot (default: null)
     *
     * @return void
     */
    public function initializeSnapshot(?array $options = null): void
    {
        $options ??= $this->getOptionsManager()->get('snapshot') ?? [];
        
        $this->keepSnapshots($options['keepSnapshots'] ?? true);
        $this->setSnapshotBehavior(new SnapshotBehavior($options));
    }
    
    /**
     * Set the SnapshotBehavior for the model
     *
     * @param SnapshotBehavior $snapshotBehavior The SnapshotBehavior instance to set
     *
     * @return void
     */
    public function setSnapshotBehavior(SnapshotBehavior $snapshotBehavior): void
    {
        $this->setBehavior('snapshot', $snapshotBehavior);
    }
    
    /**
     * Get the SnapshotBehavior instance for the model.
     *
     * @return SnapshotBehavior The SnapshotBehavior instance.
     */
    public function getSnapshotBehavior(): SnapshotBehavior
    {
        return $this->getTypedBehavior('snapshot', SnapshotBehavior::class);
    }

    /**
     * Return model fields whose raw values differ from the stored snapshot.
     *
     * Phalcon's native getChangedFields() reports the extension's current dirty
     * tracking state. This helper complements it for audit, domain comparison,
     * replication, and response-building code that needs a stable
     * snapshot-versus-current diff expressed with application model field names.
     *
     * Snapshot arrays can be keyed by either database column names or mapped
     * model field names. Returned fields are normalized through the model column
     * map whenever metadata is available, unknown snapshot entries are ignored,
     * and current values are read through readAttribute() so model getters do
     * not format values or trigger domain side effects during comparison.
     *
     * The ignore list accepts database column names and mapped model field names.
     * Use it for lifecycle or bookkeeping fields such as updatedAt, updatedBy,
     * updatedAs, or their database-column equivalents. Nullable fields preserve
     * PhalconKit's SQL "NULL" string convention by comparing those values as
     * null when metadata marks the field nullable.
     *
     * When Phalcon has no snapshot for the model, the method falls back to
     * native getChangedFields(), still applying column-map normalization and the
     * ignore list. This method is intentionally not a replacement for native
     * dirty tracking and should not be used as the sole authorization context
     * for sensitive flows such as password reset or privileged account changes.
     *
     * @param array<int, string> $ignoreFields Database column or mapped model
     *     field names to omit from the result.
     * @return list<string> Mapped model field names whose snapshot value differs
     *     from the current raw attribute value.
     *
     * @throws LogicException When the trait host cannot expose Phalcon's raw
     *     entity attribute API.
     */
    public function getSnapshotChangedFields(array $ignoreFields = []): array
    {
        $context = $this->getSnapshotFieldContext();
        $ignoredFields = $this->normalizeSnapshotIgnoredFields($ignoreFields, $context);

        if (!$this->hasSnapshotData()) {
            return $this->normalizeNativeChangedFields($this->getChangedFields(), $ignoredFields, $context);
        }

        $entity = $this->requireSnapshotEntity();
        $changedFields = [];
        $changedFieldSet = [];

        foreach ($this->getSnapshotData() as $snapshotField => $snapshotValue) {
            $field = $this->normalizeSnapshotFieldName((string)$snapshotField, $context);
            if ($field === null || isset($ignoredFields[$field])) {
                continue;
            }

            $snapshotValue = $this->normalizeSnapshotComparisonValue(
                $field,
                $snapshotValue,
                $context['nullableFields']
            );
            $currentValue = $this->normalizeSnapshotComparisonValue(
                $field,
                $entity->readAttribute($field),
                $context['nullableFields']
            );

            if ($snapshotValue !== $currentValue && !isset($changedFieldSet[$field])) {
                $changedFields[] = $field;
                $changedFieldSet[$field] = true;
            }
        }

        return $changedFields;
    }
    
    /**
     * Creates a closure that can be used as a callback to determine if a model attribute has changed.
     *
     * @param callable $callback The callback function to be executed if the model attribute has changed.
     * @param bool $anyField Determines whether to check for changes in any field (default: true).
     *
     * @return \Closure A closure that takes a Model instance and a field name as arguments, and returns the result of the callback
     *         function if the attribute has changed, or the value of the attribute if it has not changed.
     */
    public function hasChangedCallback(callable $callback, bool $anyField = true): \Closure
    {
        return function (Model $model, string $field) use ($callback, $anyField): mixed {
            return (!$model->hasSnapshotData()
                || $model->hasChanged($anyField ? null : $field)
                || $model->hasUpdated($anyField ? null : $field))
                ? $callback($model, $field)
                : $model->readAttribute($field);
        };
    }

    /**
     * Build the field metadata used to normalize snapshot keys and comparisons.
     *
     * Metadata access is best-effort because callers can use model doubles or
     * partially bootstrapped models in tests. When metadata is unavailable the
     * helper keeps field names as provided, which mirrors Phalcon's native
     * changed-field behavior without inventing mappings.
     *
     * @return array{
     *     columnMap: array<string, string>,
     *     databaseFields: array<string, true>|null,
     *     modelFields: array<string, true>|null,
     *     nullableFields: array<string, true>
     * }
     */
    private function getSnapshotFieldContext(): array
    {
        if (!$this instanceof ModelInterface) {
            return [
                'columnMap' => [],
                'databaseFields' => null,
                'modelFields' => null,
                'nullableFields' => [],
            ];
        }

        try {
            $metadata = $this->getModelsMetaData();
            $attributes = $metadata->getAttributes($this);
            $notNullAttributes = array_flip($metadata->getNotNullAttributes($this));
            $columnMap = $this->normalizeSnapshotColumnMap($metadata->getColumnMap($this) ?? []);
        }
        catch (\Throwable) {
            return [
                'columnMap' => [],
                'databaseFields' => null,
                'modelFields' => null,
                'nullableFields' => [],
            ];
        }

        $databaseFields = [];
        $modelFields = [];
        $nullableFields = [];

        foreach ($attributes as $attribute) {
            if (!is_int($attribute) && !is_string($attribute)) {
                continue;
            }

            $databaseField = (string)$attribute;
            $modelField = $columnMap[$databaseField] ?? $databaseField;

            $databaseFields[$databaseField] = true;
            $modelFields[$modelField] = true;

            if (!isset($notNullAttributes[$databaseField])) {
                $nullableFields[$modelField] = true;
            }
        }

        return [
            'columnMap' => $columnMap,
            'databaseFields' => $databaseFields,
            'modelFields' => $modelFields,
            'nullableFields' => $nullableFields,
        ];
    }

    /**
     * Normalize native changed-field output through the same mapped-name rules.
     *
     * Native Phalcon changed fields are used only when no snapshot is available.
     * If metadata cannot identify a field, the original native name is kept so
     * the fallback remains faithful to Phalcon's own result.
     *
     * @param array<int, mixed> $changedFields Native fields from getChangedFields().
     * @param array<string, true> $ignoredFields Normalized fields to omit.
     * @param array{
     *     columnMap: array<string, string>,
     *     databaseFields: array<string, true>|null,
     *     modelFields: array<string, true>|null,
     *     nullableFields: array<string, true>
     * } $context Snapshot field metadata.
     * @return list<string>
     */
    private function normalizeNativeChangedFields(array $changedFields, array $ignoredFields, array $context): array
    {
        $fields = [];
        $fieldSet = [];

        foreach ($changedFields as $changedField) {
            if (!is_int($changedField) && !is_string($changedField)) {
                continue;
            }

            $nativeField = (string)$changedField;
            $field = $this->normalizeSnapshotFieldName($nativeField, $context) ?? $nativeField;
            if (isset($ignoredFields[$field]) || isset($fieldSet[$field])) {
                continue;
            }

            $fields[] = $field;
            $fieldSet[$field] = true;
        }

        return $fields;
    }

    /**
     * Convert database-column snapshot keys and ignore entries to model fields.
     *
     * When metadata knows the model fields, unknown snapshot keys return null so
     * relation payloads or transient data stored alongside snapshots do not
     * create false changed-field results.
     *
     * @param array{
     *     columnMap: array<string, string>,
     *     databaseFields: array<string, true>|null,
     *     modelFields: array<string, true>|null,
     *     nullableFields: array<string, true>
     * } $context Snapshot field metadata.
     */
    private function normalizeSnapshotFieldName(string $field, array $context): ?string
    {
        if (isset($context['columnMap'][$field])) {
            return $context['columnMap'][$field];
        }

        if ($context['modelFields'] === null && $context['databaseFields'] === null) {
            return $field;
        }

        if (isset($context['modelFields'][$field])) {
            return $field;
        }

        if (isset($context['databaseFields'][$field])) {
            return $field;
        }

        return null;
    }

    /**
     * Normalize ignore-list entries once so comparisons stay simple.
     *
     * @param array<int, string> $ignoreFields Database column or mapped model
     *     field names to ignore.
     * @param array{
     *     columnMap: array<string, string>,
     *     databaseFields: array<string, true>|null,
     *     modelFields: array<string, true>|null,
     *     nullableFields: array<string, true>
     * } $context Snapshot field metadata.
     * @return array<string, true>
     */
    private function normalizeSnapshotIgnoredFields(array $ignoreFields, array $context): array
    {
        $ignoredFields = [];

        foreach ($ignoreFields as $ignoreField) {
            $field = $this->normalizeSnapshotFieldName($ignoreField, $context) ?? $ignoreField;
            $ignoredFields[$field] = true;
        }

        return $ignoredFields;
    }

    /**
     * Normalize a Phalcon metadata column map into string keys and values.
     *
     * @param array<array-key, int|string> $columnMap Raw metadata column map.
     * @return array<string, string> Database column name to mapped model field.
     */
    private function normalizeSnapshotColumnMap(array $columnMap): array
    {
        $normalized = [];

        foreach ($columnMap as $column => $field) {
            $normalized[(string)$column] = (string)$field;
        }

        return $normalized;
    }

    /**
     * Normalize comparison values for nullable SQL NULL-string conventions.
     *
     * PhalconKit already converts "NULL" strings to null before persistence for
     * nullable attributes. The snapshot diff mirrors that rule during
     * comparison, without mutating the model or its snapshot arrays.
     *
     * @param array<string, true> $nullableFields Mapped model fields that allow null.
     */
    private function normalizeSnapshotComparisonValue(string $field, mixed $value, array $nullableFields): mixed
    {
        if (isset($nullableFields[$field]) && is_string($value) && strcasecmp(trim($value), 'NULL') === 0) {
            return null;
        }

        return $value;
    }

    /**
     * Require the trait host to expose Phalcon's raw entity attribute API.
     *
     * Snapshot comparison intentionally avoids magic property access and domain
     * getters. If a downstream class composes this trait outside a Phalcon
     * entity, fail with a framework-scoped exception instead of a late method
     * error from readAttribute().
     *
     * @throws LogicException When the trait host is not a Phalcon entity.
     */
    private function requireSnapshotEntity(): EntityInterface
    {
        if ($this instanceof EntityInterface) {
            return $this;
        }

        throw new LogicException(sprintf(
            'Model snapshot helpers require the trait host to implement "%s"; got "%s".',
            EntityInterface::class,
            get_debug_type($this)
        ));
    }
}
