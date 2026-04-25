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

namespace PhalconKit\Mvc\Controller\Traits\Query;

use Phalcon\Filter\Exception;
use Phalcon\Messages\Message;
use Phalcon\Mvc\ModelInterface;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractExpose;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractInjectable;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractModel;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractParams;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractQuery;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\AbstractSave;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\AbstractWith;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\Fields\AbstractMapFields;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\Fields\AbstractSaveFields;
use PhalconKit\Mvc\Model\Interfaces\EagerLoadInterface;

/**
 * REST persistence trait (controller-side).
 *
 * Goals:
 * - Provide a stable `save()` entry point (single + batch)
 * - Optionally expose controller-mappable `create()` and `update()` helpers
 * - Enforce forced mode semantics (create must never update, update must never create)
 * - Keep identity resolution data-driven (row-scoped), not request/global-scoped
 *
 * Contract:
 * - Single save: { saved: bool, messages: [], data?: mixed, mode?: 'create'|'update' }
 * - Batch save:  { saved: bool, messages: [], results: [], stats: {} }
 */
trait Save
{
    use AbstractSave;

    use AbstractExpose;
    use AbstractInjectable;
    use AbstractModel;
    use AbstractParams;
    use AbstractQuery;
    use AbstractWith;
    use AbstractMapFields;
    use AbstractSaveFields;

    /* ==========================================================
     * Controller-mappable entry points (optional)
     * ======================================================== */

    /**
     * Force CREATE semantics.
     * - Never updates
     * - Fails if identity is present in payload
     *
     * @throws Exception
     */
    public function create(): array
    {
        return $this->save(forceMode: 'create');
    }

    /**
     * Force UPDATE semantics.
     * - Never creates
     * - Fails if identity is missing or does not resolve
     *
     * @throws Exception
     */
    public function update(): array
    {
        return $this->save(forceMode: 'update');
    }

    /* ==========================================================
     * Public API
     * ======================================================== */

    /**
     * Generic save entry point.
     *
     * Payload shapes:
     * - Single entity: associative array
     * - Batch: list of associative arrays
     *
     * Mode:
     * - null      => auto (create if no identity, else update if identity resolves)
     * - 'create'  => force create (no identity allowed)
     * - 'update'  => force update (identity must resolve)
     *
     * @throws Exception
     */
    public function save(?string $forceMode = null): array
    {
        $payload = $this->getParams();

        // Batch mode (list payload)
        if (is_array($payload) && array_is_list($payload)) {
            return $this->saveMany($payload, $forceMode);
        }

        // Single entity
        if (!is_array($payload)) {
            return $this->buildRestSaveFailure(
                'Invalid payload.',
                'InvalidPayload',
                400
            );
        }

        return $this->saveOne($payload, $forceMode);
    }

    /* ==========================================================
     * Batch persistence
     * ======================================================== */

    /**
     * Saves many entities (best-effort).
     *
     * Semantics:
     * - Continues on errors
     * - Returns per-row results + stats
     * - Root messages are summary-only (legacy compatibility)
     */
    protected function saveMany(array $rows, ?string $forceMode): array
    {
        $results = [];
        $savedCount = 0;
        $failedCount = 0;

        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                $results[$index] = $this->buildRestSaveFailure(
                    'Invalid payload row.',
                    'InvalidPayloadRow',
                    400,
                    (string) $index
                );
                $failedCount++;
                continue;
            }

            $result = $this->saveOne($row, $forceMode);
            $results[$index] = $result;

            if (($result['saved'] ?? false) === true) {
                $savedCount++;
            } else {
                $failedCount++;
            }
        }

        // Root messages for batch are summary-only (not validation carriers).
        $messages = [];
        if ($failedCount > 0) {
            $messages[] = [
                'type' => 'summary',
                'message' => sprintf(
                    '%d of %d entities were not saved.',
                    $failedCount,
                    count($rows)
                ),
            ];
        }

        return [
            'saved' => $failedCount === 0,
            'messages' => $messages,
            'results' => $results,
            'stats' => [
                'total'  => count($rows),
                'saved'  => $savedCount,
                'failed' => $failedCount,
            ],
        ];
    }

    /* ==========================================================
     * Single-entity persistence (orchestration)
     * ======================================================== */

    /**
     * Saves a single entity.
     *
     * Implementation is intentionally split into small, testable phases:
     * - resolvePersistenceIntent(): mode + model selection (create/update)
     * - assignModelFromPayload(): assignment + beforeAssign event
     * - persistAssignedModel(): save + events + eager loading + expose
     */
    protected function saveOne(array $data, ?string $forceMode): array
    {
        [$mode, $model, $failure] = $this->resolvePersistenceIntent($data, $forceMode);

        if ($failure !== null) {
            return $failure;
        }

        assert($model instanceof ModelInterface);
        assert($mode === 'create' || $mode === 'update');

        $this->assignModelFromPayload($model, $data);

        return $this->persistAssignedModel($model, $mode);
    }

    /* ==========================================================
     * Phase 1: resolve persistence intent (create vs update)
     * ======================================================== */

    /**
     * Resolves:
     * - the effective mode ('create'|'update')
     * - the target model instance
     *
     * Forced mode invariants:
     * - force 'create': must NOT update; identity is forbidden
     * - force 'update': must NOT create; identity must resolve to an entity
     *
     * Returns a 3-tuple: [$mode, $model|null, $failure|null]
     */
    protected function resolvePersistenceIntent(array $data, ?string $forceMode): array
    {
        // Force CREATE: reject identity to prevent accidental updates.
        if ($forceMode === 'create') {
            if ($this->hasPrimaryKey($data)) {
                return [
                    null,
                    null,
                    $this->buildRestSaveFailure(
                        'Identity fields are not allowed when forcing create.',
                        'InvalidCreate',
                        400,
                        $this->getPrimaryKeyAttributes()
                    ),
                ];
            }

            return ['create', $this->loadModel(), null];
        }

        // Force UPDATE: require identity and require entity to exist.
        if ($forceMode === 'update') {
            if (!$this->hasPrimaryKey($data)) {
                return [
                    null,
                    null,
                    $this->buildRestSaveFailure(
                        'Missing identity fields for update.',
                        'InvalidUpdate',
                        400,
                        $this->getPrimaryKeyAttributes()
                    ),
                ];
            }

            $model = $this->findModelByIdentityPayload($data);

            if (!$model) {
                return [
                    null,
                    null,
                    $this->buildRestSaveFailure(
                        'Entity not found.',
                        'NotFound',
                        404,
                        $this->getPrimaryKeyAttributes()
                    ),
                ];
            }

            return ['update', $model, null];
        }

        // Auto mode: update only if identity resolves; otherwise create.
        if ($this->hasPrimaryKey($data)) {
            $model = $this->findModelByIdentityPayload($data);
            if ($model) {
                return ['update', $model, null];
            }
        }

        return ['create', $this->loadModel(), null];
    }

    /**
     * Finds an entity using identity extracted from the provided payload.
     *
     * This is intentionally payload-driven, so batch operations do not rely on controller/global params.
     *
     * Notes:
     * - Uses buildIdentityConditionFromData($data) (from IdentityConditions refactor)
     * - Temporarily overrides the "default" identity condition used by findFirst()
     * - Restores the previous value afterward to prevent leakage across batch rows
     */
    protected function findModelByIdentityPayload(array $payload): ?ModelInterface
    {
        $identity = $this->buildIdentityConditionFromData($payload);
        if (empty($identity)) {
            return null;
        }

        return $this->findFirst(
            $this->mergeCompiledFind(
                $this->prepareFind(),
                $this->compileFind($identity)
            )
        ) ?: null;
    }

    /* ==========================================================
     * Phase 2: assign payload to model
     * ======================================================== */

    /**
     * Assigns payload to the model using saveFields/mapFields rules.
     *
     * - Strips identity fields before assign to prevent accidental PK changes
     * - Fires rest:beforeAssign with references allowing upstream mutation
     */
    protected function assignModelFromPayload(ModelInterface $model, array &$data): void
    {
        $saveFields = $this->getSaveFields()?->toArray();
        $mapFields  = $this->getMapFields()?->toArray();

        // Allow listeners to mutate model/payload/fields before assign.
        $this->eventsManager->fire(
            'rest:beforeAssign',
            $this,
            [&$model, &$data, &$saveFields, &$mapFields],
            false
        );

        // Identity must not be assigned.
        $this->stripPrimaryKey($data);

        $model->assign($data, $saveFields, $mapFields);
    }

    /* ==========================================================
     * Phase 3: persist + after hooks + expose
     * ======================================================== */

    /**
     * Saves the model and returns a canonical REST payload.
     *
     * - Fires rest:beforeSave (may return false to abort)
     * - Saves model
     * - Fires rest:afterSave
     * - Optionally eager-loads relations
     * - Exposes model
     */
    protected function persistAssignedModel(ModelInterface $model, string $mode): array
    {
        if ($this->eventsManager->fire('rest:beforeSave', $this, [&$model, $mode]) === false) {
            return [
                'saved' => false,
                'messages' => $model->getMessages(),
            ];
        }

        if (!$model->save()) {
            return [
                'saved' => false,
                'messages' => $model->getMessages(),
            ];
        }

        $this->eventsManager->fire('rest:afterSave', $this, [&$model, $mode], false);

        // Optional eager-load after save, so response includes relationships.
        $with = $this->getWith()?->toArray();
        if ($with && $model instanceof EagerLoadInterface) {
            $model = $model->load($with);
        }

        return [
            'saved' => true,
            'mode' => $mode,
            'data' => $this->expose($model),
            'messages' => [],
        ];
    }

    /* ==========================================================
     * REST-specific failure helpers
     * ======================================================== */

    /**
     * Builds a canonical failure payload for save operations.
     *
     * Naming is intentionally REST/save-specific (not generic "error") because:
     * - This trait is used by REST controllers
     * - The returned shape is part of the REST contract
     *
     * @param string               $message Human message
     * @param string               $type    Machine-ish type (Phalcon message type)
     * @param int                  $code    HTTP-ish code (used by action layer)
     * @param string|array|null    $field   Optional field(s) (e.g., PK attributes)
     */
    protected function buildRestSaveFailure(
        string $message,
        string $type,
        int $code,
        string|array|null $field = null
    ): array {
        return [
            'saved' => false,
            'messages' => [
                new Message($message, $field, $type, $code),
            ],
        ];
    }

    /* ==========================================================
     * PK helpers (override-friendly)
     * ======================================================== */

    /**
     * Detects whether payload contains identity.
     *
     * Override when you add:
     * - composite primary keys
     * - dynamic key names per model
     */
    protected function hasPrimaryKey(array $data): bool
    {
        return isset($data['id']) || isset($data['uuid']);
    }

    /**
     * Removes identity fields from the payload so they cannot be mass-assigned.
     *
     * Override if identity keys differ.
     */
    protected function stripPrimaryKey(array &$data): void
    {
        unset($data['id'], $data['uuid']);
    }
}
