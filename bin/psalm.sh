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
  export XDG_CACHE_HOME="${PHALCON_KIT_PSALM_CACHE_HOME:-/tmp/phalcon-kit-psalm-cache}"
fi
mkdir -p "$XDG_CACHE_HOME"
: "${PSALM_THREADS:=1}"
: "${PSALM_SCAN_THREADS:=$PSALM_THREADS}"

psalm --config=psalm.xml --threads="$PSALM_THREADS" --scan-threads="$PSALM_SCAN_THREADS" --taint-analysis --dump-taint-graph=taints.dot "$@"
