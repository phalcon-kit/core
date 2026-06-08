#!/usr/bin/env bash

set -euo pipefail

required_version="${PHALCON_VERSION:?PHALCON_VERSION is required}"
pecl_package_url="${PHALCON_PECL_URL:-https://github.com/phalcon/cphalcon/releases/download/v${required_version}/phalcon-pecl.tgz}"

installed_version="$(php -r 'echo phpversion("phalcon") ?: "";')"

if [[ "${installed_version}" != "${required_version}" ]]; then
  echo "Installing Phalcon ${required_version} from ${pecl_package_url}"
  echo "Current Phalcon version: ${installed_version:-not installed}"

  printf "\n" | sudo pecl install -f "${pecl_package_url}"

  scan_dir="$(php -r '$dirs = explode(PATH_SEPARATOR, PHP_CONFIG_FILE_SCAN_DIR); echo $dirs[0] ?? "";')"
  if [[ -n "${scan_dir}" ]]; then
    echo "extension=phalcon.so" | sudo tee "${scan_dir%/}/35-phalcon.ini" > /dev/null
  fi
fi

installed_version="$(php -r 'echo phpversion("phalcon") ?: "";')"

if [[ "${installed_version}" != "${required_version}" ]]; then
  echo "::error::Expected Phalcon ${required_version}, got ${installed_version:-not installed}"
  php --ri phalcon || true
  exit 1
fi

php --ri phalcon
