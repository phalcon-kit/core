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

namespace PhalconKit\Mvc\Controller\Traits\Abstracts;

use Phalcon\Http\ResponseInterface;

trait AbstractRestResponse
{
    /**
     * Envelope field containing the ISO-8601 response generation time.
     */
    public const string REST_PAYLOAD_TIMESTAMP = 'timestamp';

    /**
     * Envelope field containing the HTTP reason phrase.
     */
    public const string REST_PAYLOAD_STATUS = 'status';

    /**
     * Envelope field containing the HTTP status code.
     */
    public const string REST_PAYLOAD_CODE = 'code';

    /**
     * Envelope field containing the action success/error value.
     */
    public const string REST_PAYLOAD_RESPONSE = 'response';

    /**
     * Envelope field containing public variables collected from the view.
     */
    public const string REST_PAYLOAD_VIEW = 'view';

    /**
     * Envelope field containing debug metadata when debug mode is enabled.
     */
    public const string REST_PAYLOAD_DEBUG = 'debug';

    /**
     * Internal Phalcon view key that must not be serialized in REST responses.
     */
    public const string REST_VIEW_INTERNAL = '_';

    /**
     * Standard view field containing exposed model/list data.
     */
    public const string REST_VIEW_DATA = 'data';

    /**
     * Standard view field containing model or validation messages.
     */
    public const string REST_VIEW_MESSAGES = 'messages';

    /**
     * Standard view field containing count aggregate results.
     */
    public const string REST_VIEW_COUNT = 'count';

    /**
     * Standard view field containing sum aggregate results.
     */
    public const string REST_VIEW_SUM = 'sum';

    /**
     * Standard view field containing average aggregate results.
     */
    public const string REST_VIEW_AVERAGE = 'average';

    /**
     * Standard view field containing minimum aggregate results.
     */
    public const string REST_VIEW_MINIMUM = 'minimum';

    /**
     * Standard view field containing maximum aggregate results.
     */
    public const string REST_VIEW_MAXIMUM = 'maximum';

    /**
     * Standard view field containing save success state.
     */
    public const string REST_VIEW_SAVED = 'saved';

    /**
     * Standard view field containing batch action rows.
     */
    public const string REST_VIEW_RESULTS = 'results';

    /**
     * Standard view field containing batch action statistics.
     */
    public const string REST_VIEW_STATS = 'stats';

    /**
     * Standard view field containing delete success state.
     */
    public const string REST_VIEW_DELETED = 'deleted';

    /**
     * Standard view field containing restore success state.
     */
    public const string REST_VIEW_RESTORED = 'restored';

    /**
     * Standard view field containing reorder success state.
     */
    public const string REST_VIEW_REORDERED = 'reordered';

    abstract public function setRestErrorResponse(int $code = 400, string $status = 'Bad Request', mixed $response = null): ResponseInterface;
    
    abstract public function setRestResponse(mixed $response = null, ?int $code = null, ?string $status = null, int $jsonOptions = 0, int $depth = 512): ResponseInterface;

    /**
     * Set one public REST view field.
     */
    abstract protected function setRestViewVar(string $key, mixed $value): void;

    /**
     * Set several public REST view fields.
     *
     * @param array<string, mixed> $vars
     */
    abstract protected function setRestViewVars(array $vars, bool $merge = true): void;
}
