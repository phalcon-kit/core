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

namespace PhalconKit\Tests\Unit\Modules\Cli\Tasks\Fixtures;

use Phalcon\Mvc\ModelInterface;
use Phalcon\Mvc\Model\ResultsetInterface;
use PhalconKit\Models\User;

final class UserTaskUserDouble extends User
{
    /** @var list<array|mixed> */
    public static array $findParameters = [];

    /** @var list<self> */
    public static array $rows = [];

    /** @var list<self> */
    public static array $saved = [];

    /** @var array<string, mixed> */
    public array $assigned = [];

    public bool $saveResult = true;

    /** @var list<mixed> */
    public array $messages = [];

    /**
     * Build a configured user double without overriding Phalcon's final model
     * constructor.
     *
     * @param list<mixed> $messages Messages returned by getMessages().
     */
    public static function make(?string $email = null, bool $saveResult = true, array $messages = []): self
    {
        $user = new self();
        $user->email = $email;
        $user->saveResult = $saveResult;
        $user->messages = $messages;

        return $user;
    }

    public static function resetState(): void
    {
        self::$findParameters = [];
        self::$rows = [];
        self::$saved = [];
    }

    #[\Override]
    public static function find(mixed $parameters = null): ResultsetInterface
    {
        self::$findParameters[] = $parameters;
        return new UserTaskResultsetDouble(self::$rows);
    }

    #[\Override]
    public function assign(array $data, $whiteList = null, $dataColumnMap = null): ModelInterface
    {
        $this->assigned = $data;
        foreach ($data as $field => $value) {
            $this->{$field} = $value;
        }

        return $this;
    }

    #[\Override]
    public function save(): bool
    {
        self::$saved[] = $this;
        return $this->saveResult;
    }

    #[\Override]
    public function getMessages($filter = null): array
    {
        return $this->messages;
    }
}
