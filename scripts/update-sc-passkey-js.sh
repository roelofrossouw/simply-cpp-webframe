#!/bin/bash
# Copies sc-passkey's browser script (modules/sc-passkey/js/sc-passkey.js in the suite) into public/js, which serves it at
# /framework/js/sc-passkey.js for Auth\Passkey. Run it when sc-passkey's script changes, and commit the copy.
#
#   scripts/update-sc-passkey-js.sh [path/to/sc-passkey.js]
set -euo pipefail
here="$(cd "$(dirname "$0")/.." && pwd)"
source="${1:-$here/../sc-passkey/js/sc-passkey.js}"
if [ ! -f "$source" ]; then
    echo "No sc-passkey.js at $source" >&2
    exit 1
fi
cp "$source" "$here/public/js/sc-passkey.js"
echo "public/js/sc-passkey.js updated from $source"
