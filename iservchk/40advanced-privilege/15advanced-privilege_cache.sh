#!/bin/bash
FN_CACHEDIR="/var/lib/iserv/stsbl-advanced-privilege/app/cachedir"

[ -f "$FN_CACHEDIR" ] || exit 0

CACHE_DIR="$(< "$FN_CACHEDIR")"

[ -n "$CACHE_DIR" ] || exit 0
[[ "$CACHE_DIR" =~ ^/var/cache/iserv/stsbl-advanced-privilege/app/ ]] || exit 0

cat<<EOT
MkDir 0755 root:root /var/cache/iserv/stsbl-advanced-privilege
MkDir 2770 iserv-stsbl-advanced-privilege:iserv-stsbl-advanced-privilege /var/cache/iserv/stsbl-advanced-privilege/app
MkDir 2770 iserv-stsbl-advanced-privilege:iserv-stsbl-advanced-privilege $CACHE_DIR
MkDir 2770 iserv-stsbl-advanced-privilege:iserv-stsbl-advanced-privilege $CACHE_DIR/{pools,templates}

EOT
