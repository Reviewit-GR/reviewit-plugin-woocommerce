#!/usr/bin/env bash
# The zip for the WordPress.org plugin directory (or an upload): only the files the plugin needs.
set -euo pipefail
cd "$(dirname "$0")/.."
header=$(sed -n 's/^ \* Version: *//p' reviewit-invitations.php)
const=$(sed -n "s/^define( 'REVIEWIT_INVITATIONS_VERSION', '\(.*\)' );/\1/p" reviewit-invitations.php)
stable=$(sed -n 's/^Stable tag: *//p' readme.txt)
[ "$header" = "$const" ] && [ "$header" = "$stable" ] || { echo "versions differ: header $header, constant $const, readme $stable" >&2; exit 1; }
python3 bin/compile-mo.py >/dev/null
rm -rf dist && mkdir -p dist/reviewit-invitations
cp -r reviewit-invitations.php uninstall.php readme.txt LICENSE includes languages dist/reviewit-invitations/
(cd dist && python3 -c "import shutil,sys; shutil.make_archive(sys.argv[1], 'zip', '.', 'reviewit-invitations')" "reviewit-invitations-$header" && rm -rf reviewit-invitations)
echo "dist/reviewit-invitations-$header.zip"
