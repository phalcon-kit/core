#!/usr/bin/env bash

set -euo pipefail

required_version="${PHALCON_VERSION:?PHALCON_VERSION is required}"
pecl_package_url="${PHALCON_PECL_URL:-https://github.com/phalcon/cphalcon/releases/download/v${required_version}/phalcon-pecl.tgz}"
pecl_package_sha256="${PHALCON_PECL_SHA256:?PHALCON_PECL_SHA256 is required}"
installed_version="$(php -r 'echo phpversion("phalcon") ?: "";')"

if [[ "${installed_version}" != "${required_version}" ]]; then
  echo "Installing Phalcon ${required_version} from ${pecl_package_url}"
  echo "Current Phalcon version: ${installed_version:-not installed}"

  if ! pecl_bin="$(command -v pecl)"; then
    echo "::error::pecl is required to install Phalcon ${required_version}"
    exit 1
  fi

  downloaded_package="${RUNNER_TEMP:-/tmp}/phalcon-pecl-${required_version}.tgz"

  curl --fail --location --show-error --silent --retry 3 --retry-delay 2 \
    --output "${downloaded_package}" \
    "${pecl_package_url}"

  actual_sha256="$(sha256sum "${downloaded_package}" | awk '{print $1}')"
  if [[ "${actual_sha256}" != "${pecl_package_sha256}" ]]; then
    echo "::error::Phalcon archive checksum mismatch: expected ${pecl_package_sha256}, got ${actual_sha256}"
    exit 1
  fi

  sudo "${pecl_bin}" install -f "${downloaded_package}"

  scan_dir="$(php -r '$dirs = array_values(array_filter(explode(PATH_SEPARATOR, PHP_CONFIG_FILE_SCAN_DIR))); echo $dirs[0] ?? "";')"
  if [[ -n "${scan_dir}" ]]; then
    echo "extension=phalcon.so" | sudo tee "${scan_dir%/}/35-phalcon.ini" > /dev/null
  fi
fi

installed_version="$(php -r 'echo phpversion("phalcon") ?: "";')"
public_version="$(php -r '$class = "Phalcon\\Support\\Version"; echo class_exists($class) ? (new $class())->get() : "";')"

if [[ "${installed_version}" != "${required_version}" || "${public_version}" != "${required_version}" ]]; then
  echo "::error::Expected Phalcon ${required_version}, got extension ${installed_version:-not installed} and API ${public_version:-not installed}"
  php --ri phalcon || true
  exit 1
fi

php --ri phalcon
