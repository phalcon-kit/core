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

namespace PhalconKit\Modules\Cli\Tasks;

use PhalconKit\Modules\Cli\Task;

class CronTask extends Task
{
    public string $cliDoc = <<<DOC
Usage:
  phalcon-kit cli cron <action> [<params> ...]

Actions:
  main, hourly, daily, weekly, monthly  Empty by default; override them in your application task


DOC;

    #[\Override]
    public function mainAction(): ?array
    {
        return null;
    }

    public function hourlyAction(): ?array
    {
        return null;
    }

    public function dailyAction(): ?array
    {
        return null;
    }

    public function weeklyAction(): ?array
    {
        return null;
    }

    public function monthlyAction(): ?array
    {
        return null;
    }
}
