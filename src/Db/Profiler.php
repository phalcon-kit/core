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

namespace PhalconKit\Db;

/**
 * Database profiler with null-safe profile access and array diagnostics.
 *
 * Phalcon's profiler can throw type errors when no profile data has been
 * collected in some runtime states. This wrapper normalizes those cases to
 * empty profile lists and zero elapsed time so debug endpoints can inspect the
 * profiler without defensive try/catch blocks.
 */
class Profiler extends \Phalcon\Db\Profiler
{
    /**
     * Return collected query profiles or an empty list when none are available.
     *
     * @return array<int, \Phalcon\Db\Profiler\Item>
     */
    #[\Override]
    public function getProfiles(): array
    {
        try {
            return parent::getProfiles();
        } catch (\TypeError) {
            return [];
        }
    }

    /**
     * Return total elapsed profile time in nanoseconds.
     */
    #[\Override]
    public function getTotalElapsedNanoseconds(): float
    {
        try {
            return parent::getTotalElapsedNanoseconds();
        } catch (\TypeError) {
            return 0.0;
        }
    }

    /**
     * Return total elapsed profile time as reported by Phalcon.
     */
    #[\Override]
    public function getTotalElapsedSeconds(): float
    {
        return parent::getTotalElapsedSeconds();
    }

    /**
     * Export profiler data for debug responses.
     *
     * @return array{
     *     profiles: array<int, array<string, mixed>>,
     *     numberTotalStatements: int,
     *     totalElapsedSeconds: float
     * }
     */
    public function toArray(): array
    {
        $profiles = [];
        $profilerProfiles = $this->getProfiles();
        if (!empty($profilerProfiles)) {
            foreach ($profilerProfiles as $profile) {
                $profiles [] = [
                    'sqlStatement' => $profile->getSqlStatement(),
                    'sqlVariables' => $profile->getSqlVariables(),
                    'sqlBindTypes' => $profile->getSqlBindTypes(),
                    'initialTime' => $profile->getInitialTime(),
                    'finalTime' => $profile->getFinalTime(),
                    'elapsedSeconds' => $profile->getTotalElapsedSeconds() / 1000000000,
                ];
            }
        }

        return [
            'profiles' => $profiles,
            'numberTotalStatements' => $this->getNumberTotalStatements(),
            'totalElapsedSeconds' => $this->getTotalElapsedSeconds() / 1000000000.0,
        ];
    }
}
