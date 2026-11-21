#!/bin/sh
#
# Runs this server's provisioner scripts once, in the order its image lists
# them, then reports to the panel how each one exited.
#
# Convoy writes this file through cloud-init vendor data, which runs it once
# per instance. Output goes to /var/log/convoy-provisioners.log.

# Overridable so the panel's test suite can run this file as it ships.
dir=${CONVOY_PROVISIONER_DIR:-/var/lib/convoy/provisioners}
log=${CONVOY_PROVISIONER_LOG:-/var/log/convoy-provisioners.log}

[ -e "$dir/done" ] && exit 0

umask 077
exec >>"$log" 2>&1

# One NAME=value per line. `export "$line"` assigns the value as it is:
# nothing in it is expanded or run, whatever characters it holds.
while IFS= read -r line || [ -n "$line" ]; do
    [ -n "$line" ] && export "$line"
done <"$dir/environment"

results=''
failed=''
while IFS= read -r id || [ -n "$id" ]; do
    [ -n "$id" ] || continue
    [ -z "$failed" ] || break

    echo "==> $id $(date -u '+%Y-%m-%dT%H:%M:%SZ')"
    "$dir/scripts/$id" </dev/null
    code=$?
    echo "<== $id exited $code"

    results="$results${results:+,}{\"provisioner\":\"$id\",\"exit_code\":$code}"
    [ "$code" -eq 0 ] || failed=1
done <"$dir/order"

: >"$dir/done"
rm -f "$dir/environment"

{
    IFS= read -r url
    IFS= read -r token
} <"$dir/callback"
rm -f "$dir/callback"

body="{\"results\":[$results]}"

# The panel answers 2xx once it has the result and 4xx when it will never
# take it (the server was rebuilt since). Anything else is worth retrying.
attempt=0
while [ "$attempt" -lt 30 ]; do
    attempt=$((attempt + 1))

    if command -v curl >/dev/null 2>&1; then
        status=$(curl -sS -o /dev/null -w '%{http_code}' -m 30 -X POST \
            -H "Authorization: Bearer $token" \
            -H 'Content-Type: application/json' \
            -H 'Accept: application/json' \
            --data "$body" "$url")
        case "$status" in
            2??|4??) echo "Reported to the panel (HTTP $status)"; exit 0 ;;
        esac
    elif command -v wget >/dev/null 2>&1; then
        if wget -q -T 30 -O /dev/null \
            --header="Authorization: Bearer $token" \
            --header='Content-Type: application/json' \
            --header='Accept: application/json' \
            --post-data="$body" "$url"; then
            echo 'Reported to the panel'
            exit 0
        fi
    else
        echo 'Could not report to the panel: neither curl nor wget is installed'
        exit 0
    fi

    sleep 10
done

echo 'Could not report to the panel after 30 attempts'
