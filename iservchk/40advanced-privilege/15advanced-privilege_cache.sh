#!/bin/bash
FN_CACHEDIR="/var/lib/iserv/stsbl-iserv-advanced-privilege/app/cachedir"

[ -f "$FN_CACHEDIR" ] || exit 0

CACHE_DIR="$(< "$FN_CACHEDIR")"

[ -n "$CACHE_DIR" ] || exit 0
[[ "$CACHE_DIR" =~ ^/var/cache/iserv/stsbl-iserv-advanced-privilege/app/ ]] || exit 0

cat<<EOT
MkDir 0755 root:root /var/cache/iserv/stsbl-iserv-advanced-privilege
MkDir 2770 stsbl-iserv-advanced-privilege:stsbl-iserv-advanced-privilege /var/cache/iserv/stsbl-iserv-advanced-privilege/app
MkDir 2770 stsbl-iserv-advanced-privilege:stsbl-iserv-advanced-privilege $CACHE_DIR
MkDir 2770 stsbl-iserv-advanced-privilege:stsbl-iserv-advanced-privilege $CACHE_DIR/{pools,templates}

EOT
