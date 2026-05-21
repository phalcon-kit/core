#!/bin/bash
#
# This file is part of the Phalcon Kit.
#
# (c) Phalcon Kit Team
#
# For the full copyright and license information, please view the LICENSE.txt
# file that was distributed with this source code.
#

if command -v phpunit-coverage >/dev/null 2>&1; then
    phpunit-coverage phpunit "$@"
else
    XDEBUG_MODE="${XDEBUG_MODE:-coverage}" phpunit "$@"
fi
