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

# Path to the Home.md file
home_md_file="docs/Home.md"

# Final output file for the mkdoc menu
mkdocs_menu_file="docs/mkdocs_menu.yml"

temporary_menu=$(mktemp)
trap 'rm -f "$temporary_menu"' EXIT

# Read the Home.md file line by line
while IFS= read -r line; do
    if [[ $line == '### '* ]]; then
        # Extract the section name (removing '###')
        section_name=${line/#"### "/}

        # Write the section header to the mkdoc menu file
        echo "      - $section_name:" >> "$temporary_menu"
    elif [[ $line =~ \[\`(.*)\`\]\((.*)\.md\) ]]; then
        # Extract the link text and URL from the table
        link_text="${BASH_REMATCH[1]}"
        url="${BASH_REMATCH[2]}"

        # Replace ": ./" with ": api/" in the URL
        url=${url/.\//api/}

        # Write the link to the mkdoc menu file
        echo "          - $link_text: $url.md" >> "$temporary_menu"
    fi
done < "$home_md_file"

mv "$temporary_menu" "$mkdocs_menu_file"
trap - EXIT

echo "Mkdoc menu created: $mkdocs_menu_file"
