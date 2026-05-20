#!/bin/bash
#
# This file is part of the Phalcon Kit.
#
# (c) Phalcon Kit Team
#
# For the full copyright and license information, please view the LICENSE.txt
# file that was distributed with this source code.
#
if [ -z "${XDG_CACHE_HOME:-}" ] || [ ! -w "$XDG_CACHE_HOME" ]; then
  export XDG_CACHE_HOME="${PHALCON_KIT_PHPCS_CACHE_HOME:-/tmp/phalcon-kit-phpcs-cache}"
fi
mkdir -p "$XDG_CACHE_HOME"
: "${PHPCS_CACHE:=$XDG_CACHE_HOME/phpcs.cache}"

phpcs --standard=phpcs.xml --parallel=16 --cache="$PHPCS_CACHE" --colors -p -s -w "$@"
