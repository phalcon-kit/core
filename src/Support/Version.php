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

namespace PhalconKit\Support;

/**
 * Exposes the installed PhalconKit core version through Phalcon's version API.
 *
 * Keeping this wrapper aligned with `\Phalcon\Support\Version` lets consumers
 * use the same version-format helpers they already know from Phalcon while
 * reporting the framework package version.
 */
class Version extends \Phalcon\Support\Version
{
    /**
     * Return the internal version tuple consumed by Phalcon's formatter.
     *
     * The tuple format is:
     * ABBCCDE
     *
     * A - Major version
     * B - Med version (two digits)
     * C - Min version (two digits)
     * D - Special release: 1 = Alpha, 2 = Beta, 3 = RC, 4 = Stable
     * E - Special release version i.e. RC1, Beta2 etc.
     *
     * @return array{int, int, int, int, int} Major, medium, minor, stability,
     *     and stability-version tuple.
     */
    #[\Override]
    protected function getVersion(): array
    {
        return [1, 1, 0, 4, 0];
    }
}
