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

project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$project_root"

# Wipe generated output first so removed symbols cannot survive a regeneration.
rm -rf ./docs
mkdir ./docs

phpdoc -c phpdoc.xml "$@"

php ./bin/post-process-docs.php

# Generate the API menu consumed by the documentation website.
./bin/generate-docs-menu.sh
