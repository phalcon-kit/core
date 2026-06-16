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

namespace PhalconKit\Tests\Unit\Mvc\Model\Fixtures;

use Phalcon\Mvc\Model\MetaData\Exceptions\TableNotInDatabase;
use Phalcon\Mvc\ModelInterface;
use PhalconKit\Models\Audit;

class FakeAudit extends Audit
{
    public static ?self $last = null;
    public static bool $saveResult = true;
    public static bool $throwMissingTableOnSave = false;
    public static array $messages = [];
    public static mixed $nextId = 77;

    public array $assigned = [];

    #[\Override]
    public function initialize(): void
    {
    }

    #[\Override]
    public function assign(array $data, $whiteList = null, $dataColumnMap = null): ModelInterface
    {
        $this->assigned[] = $data;

        foreach ($data as $field => $value) {
            $this->{$field} = $value;
        }

        return $this;
    }

    #[\Override]
    public function save(): bool
    {
        if (self::$throwMissingTableOnSave) {
            throw new TableNotInDatabase($this->getSource(), self::class);
        }

        self::$last = $this;
        $this->id = self::$nextId;
        return self::$saveResult;
    }

    #[\Override]
    public function getMessages($filter = null): array
    {
        return self::$messages;
    }

    public static function reset(): void
    {
        self::$last = null;
        self::$saveResult = true;
        self::$throwMissingTableOnSave = false;
        self::$messages = [];
        self::$nextId = 77;
    }
}
