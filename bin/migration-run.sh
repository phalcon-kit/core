#!/bin/bash
#
# This file is part of the Phalcon Kit.
#
# (c) Phalcon Kit Team
#
# For the full copyright and license information, please view the LICENSE.txt
# file that was distributed with this source code.
#

set -euo pipefail

project_root="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$project_root"

exec ./vendor/bin/phalcon-migrations run \
    --config=./devtools.php \
    --directory=./ \
    --migrations=./resources/migrations/ \
    --no-auto-increment \
    --verbose \
    --log-in-db \
    "$@"
