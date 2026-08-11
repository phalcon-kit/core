#!/usr/bin/env bash

set -euo pipefail

required_version="${PHALCON_VERSION:?PHALCON_VERSION is required}"
pecl_package_url="${PHALCON_PECL_URL:-https://github.com/phalcon/cphalcon/releases/download/v${required_version}/phalcon-pecl.tgz}"
script_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"

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

  package_to_install="${downloaded_package}"
  if [[ "${required_version}" == "5.18.2" ]]; then
    patch_file="${script_dir}/../patches/phalcon-5.18.2-pecl-version.patch"
    patch_directory="$(mktemp -d "${RUNNER_TEMP:-/tmp}/phalcon-pecl-${required_version}.XXXXXX")"
    patched_package="${patch_directory}/phalcon-pecl-${required_version}-patched.tgz"

    tar -xzf "${downloaded_package}" -C "${patch_directory}"
    patch --batch --forward --directory="${patch_directory}" --strip=1 < "${patch_file}"
    mv \
      "${patch_directory}/phalcon-5.18.1" \
      "${patch_directory}/phalcon-${required_version}"
    tar -czf "${patched_package}" \
      -C "${patch_directory}" \
      package.xml \
      "phalcon-${required_version}"

    package_to_install="${patched_package}"
  fi

  sudo "${pecl_bin}" install -f "${package_to_install}"

  scan_dir="$(php -r '$dirs = array_values(array_filter(explode(PATH_SEPARATOR, PHP_CONFIG_FILE_SCAN_DIR))); echo $dirs[0] ?? "";')"
  if [[ -n "${scan_dir}" ]]; then
    echo "extension=phalcon.so" | sudo tee "${scan_dir%/}/35-phalcon.ini" > /dev/null
  fi
fi

installed_version="$(php -r 'echo phpversion("phalcon") ?: "";')"
public_version="$(php -r 'echo class_exists("Phalcon\\Support\\Version") ? (new Phalcon\\Support\\Version())->get() : "";')"

if [[ "${installed_version}" != "${required_version}" || "${public_version}" != "${required_version}" ]]; then
  echo "::error::Expected Phalcon ${required_version}, got extension ${installed_version:-not installed} and API ${public_version:-not installed}"
  php --ri phalcon || true
  exit 1
fi

php --ri phalcon
