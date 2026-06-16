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

namespace PhalconKit\Tests\Unit\Mvc\Controller\Traits;

use PhalconKit\Http\Request;
use PhalconKit\Mvc\Controller;
use PhalconKit\Mvc\Controller\Traits\Params;
use PhalconKit\Tests\Unit\AbstractUnit;

class ParamsTest extends AbstractUnit
{
    public function testJsonPostParamsUseJsonBodyWithoutMergingPostPatchOrQueryParams(): void
    {
        $jsonPayload = [
            'source' => 'json',
            'id' => 10,
            '_url' => '/ignored',
        ];

        $controller = $this->createController(
            $this->createRequest(
                isPost: true,
                post: [
                    'source' => 'post',
                    'postOnly' => true,
                ],
                patch: [
                    'patchOnly' => true,
                ],
                query: [
                    'source' => 'query',
                    'queryOnly' => true,
                ],
                contentType: 'application/json; charset=utf-8',
                json: $jsonPayload,
            )
        );

        $this->assertSame([
            'source' => 'json',
            'id' => 10,
        ], $controller->getParams());
    }

    public function testVendorJsonPatchParamsUseJsonBodyWithoutMergingFormOrQueryParams(): void
    {
        $jsonPayload = [
            [
                'source' => 'json',
            ],
        ];

        $controller = $this->createController(
            $this->createRequest(
                isPatch: true,
                post: [
                    [
                        'source' => 'post',
                    ],
                ],
                patch: [
                    [
                        'source' => 'patch',
                    ],
                ],
                query: [
                    'queryOnly' => true,
                ],
                contentType: 'application/vnd.api+json',
                json: $jsonPayload,
            )
        );

        $this->assertSame($jsonPayload, $controller->getParams());
    }

    public function testJsonContentTypeDoesNotOverrideQueryParamsForQueryRequest(): void
    {
        $controller = $this->createController(
            $this->createRequest(
                query: [
                    'source' => 'query',
                    '_url' => '/ignored',
                ],
                contentType: 'application/json',
                json: [
                    'source' => 'json',
                ],
            )
        );

        $this->assertSame(['source' => 'query'], $controller->getParams());
    }

    public function testNonArrayJsonPayloadFallsBackToMethodBody(): void
    {
        $postPayload = [
            'source' => 'post',
        ];

        $controller = $this->createController(
            $this->createRequest(
                isPost: true,
                post: $postPayload,
                contentType: 'application/json',
                json: new \stdClass(),
            )
        );

        $this->assertSame($postPayload, $controller->getParams());
    }

    public function testPostParamsUsePostBodyWithoutConcatenatingPatchBody(): void
    {
        $payload = [
            [
                'recordId' => 87443,
                'content' => 'test',
            ],
        ];

        $controller = $this->createController(
            $this->createRequest(
                isPost: true,
                post: $payload,
                patch: $payload,
            )
        );

        $this->assertSame($payload, $controller->getParams());
    }

    public function testPatchParamsUsePatchBodyWithoutMergingPostBody(): void
    {
        $postPayload = [
            [
                'source' => 'post',
            ],
        ];
        $patchPayload = [
            [
                'source' => 'patch',
            ],
        ];

        $controller = $this->createController(
            $this->createRequest(
                isPatch: true,
                post: $postPayload,
                patch: $patchPayload,
            )
        );

        $this->assertSame($patchPayload, $controller->getParams());
    }

    public function testPutParamsUsePutBodyWithoutMergingPostPatchOrQueryParams(): void
    {
        $putPayload = [
            'source' => 'put',
            'id' => 25,
        ];

        $controller = $this->createController(
            $this->createRequest(
                isPut: true,
                post: [
                    'source' => 'post',
                    'postOnly' => true,
                ],
                patch: [
                    'source' => 'patch',
                    'patchOnly' => true,
                ],
                put: $putPayload,
                query: [
                    'source' => 'query',
                    'queryOnly' => true,
                ],
            )
        );

        $this->assertSame($putPayload, $controller->getParams());
    }

    public function testJsonPutParamsUseJsonBodyWithoutMergingFormOrQueryParams(): void
    {
        $jsonPayload = [
            'source' => 'json',
            'id' => 26,
            '_url' => '/ignored',
        ];

        $controller = $this->createController(
            $this->createRequest(
                isPut: true,
                post: [
                    'source' => 'post',
                    'postOnly' => true,
                ],
                put: [
                    'source' => 'put',
                    'putOnly' => true,
                ],
                query: [
                    'source' => 'query',
                    'queryOnly' => true,
                ],
                contentType: 'Application/Merge-Patch+JSON; charset=utf-8',
                json: $jsonPayload,
            )
        );

        $this->assertSame([
            'source' => 'json',
            'id' => 26,
        ], $controller->getParams());
    }

    private function createController(Request $request): object
    {
        $this->di?->set('request', $request);

        $controller = new class extends Controller {
            use Params;
        };
        $controller->setDI($this->di);

        return $controller;
    }

    /**
     * @param array<array-key, mixed> $post
     * @param array<array-key, mixed> $patch
     * @param array<array-key, mixed> $put
     * @param array<array-key, mixed> $query
     */
    private function createRequest(
        bool $isPost = false,
        bool $isPatch = false,
        bool $isPut = false,
        array $post = [],
        array $patch = [],
        array $put = [],
        array $query = [],
        ?string $contentType = null,
        array|bool|\stdClass $json = false,
    ): Request {
        return new class ($isPost, $isPatch, $isPut, $post, $patch, $put, $query, $contentType, $json) extends Request {
            public function __construct(
                private readonly bool $postRequest,
                private readonly bool $patchRequest,
                private readonly bool $putRequest,
                private readonly array $postPayload,
                private readonly array $patchPayload,
                private readonly array $putPayload,
                private readonly array $queryPayload,
                private readonly ?string $contentTypeValue,
                private readonly array|bool|\stdClass $jsonPayload,
            ) {
            }

            #[\Override]
            public function isPost(): bool
            {
                return $this->postRequest;
            }

            #[\Override]
            public function isPatch(): bool
            {
                return $this->patchRequest;
            }

            #[\Override]
            public function isPut(): bool
            {
                return $this->putRequest;
            }

            #[\Override]
            public function getPost(
                ?string $name = null,
                $filters = null,
                $defaultValue = null,
                bool $notAllowEmpty = false,
                bool $noRecursive = false
            ) {
                return $this->postPayload;
            }

            #[\Override]
            public function getPatch(
                ?string $name = null,
                $filters = null,
                $defaultValue = null,
                bool $notAllowEmpty = false,
                bool $noRecursive = false
            ) {
                return $this->patchPayload;
            }

            #[\Override]
            public function getPut(
                ?string $name = null,
                $filters = null,
                $defaultValue = null,
                bool $notAllowEmpty = false,
                bool $noRecursive = false
            ) {
                return $this->putPayload;
            }

            #[\Override]
            public function getQuery(
                ?string $name = null,
                $filters = null,
                $defaultValue = null,
                bool $notAllowEmpty = false,
                bool $noRecursive = false
            ) {
                return $this->queryPayload;
            }

            #[\Override]
            public function getContentType(): ?string
            {
                return $this->contentTypeValue;
            }

            #[\Override]
            public function getJsonRawBody(bool $associative = false): \stdClass|bool|array
            {
                return $this->jsonPayload;
            }
        };
    }
}
