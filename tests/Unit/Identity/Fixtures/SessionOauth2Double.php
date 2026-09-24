<?php

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Identity\Fixtures;

use Phalcon\Mvc\ModelInterface;
use PhalconKit\Models\Abstracts\Oauth2Abstract;
use PhalconKit\Models\Interfaces\Oauth2Interface;

/** App-owned OAuth model contract with synthetic persistence and real field accessors. */
final class SessionOauth2Double extends Oauth2Abstract implements Oauth2Interface
{
    public static self|false|null $found = null;
    public static array $queries = [];
    public static array $saved = [];
    public array $legacyFields = [];
    public array $errors = [];
    public bool $saveResult = true;

    public function initialize(): void
    {
    }

    public static function findFirst(mixed $parameters = null): self|false|null
    {
        self::$queries[] = $parameters;
        return self::$found;
    }

    public function assign(array $data, $whiteList = null, $dataColumnMap = null): ModelInterface
    {
        $this->legacyFields = $data;
        return $this;
    }

    public function save(): bool
    {
        self::$saved[] = $this;
        return $this->saveResult;
    }

    public function getMessages($filter = null): array
    {
        return $this->errors;
    }
}
