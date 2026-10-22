#!/bin/sh
set -eu

dependencies=$(mktemp)
trap 'rm -f "$dependencies" "$dependencies.packages" "$dependencies.paths"' EXIT HUP INT TERM

if [ "$#" -eq 0 ]; then
    set -- /usr/local/bin /usr/local/sbin /usr/local/lib
fi

find "$@" -type f \
    \( -perm /111 -o -name '*.so' -o -name '*.so.*' \) \
    -exec ldd '{}' \; > "$dependencies" 2>/dev/null

if grep -q 'not found' "$dependencies"; then
    cat "$dependencies" >&2
    exit 1
fi

# Wildcard the /usr prefix to support Debian's merged-/usr package paths.
awk '/=> \// {
    path = $(NF-1)
    if (index(path, "/usr/local/") == 1) next
    sub("^/(usr/)?", "", path)
    print "*" path
}' "$dependencies" | sort -u > "$dependencies.paths"

while IFS= read -r library; do
    dpkg-query --search "$library"
done < "$dependencies.paths" > "$dependencies.packages"

awk 'sub(":$", "", $1) { print $1 }' "$dependencies.packages" | sort -u
