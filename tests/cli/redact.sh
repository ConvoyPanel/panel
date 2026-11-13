#!/usr/bin/env bash
#
# Fixture tests for the redaction and env.json steps of `convoy support`
# (docker/convoy). Run from anywhere: tests/cli/redact.sh
#
# A failing case prints a diff. To accept a deliberate change in the output,
# re-run with UPDATE=1 and review the fixture diff before committing it.

set -euo pipefail

here="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
fixtures="$here/fixtures"

# shellcheck source=docker/convoy
source "$here/../../docker/convoy"

failures=0
fail() { printf 'FAIL: %s\n' "$*" >&2; failures=$(( failures + 1 )); }
pass() { printf 'ok:   %s\n' "$*"; }

compare() {
    local name="$1" expected="$2" actual="$3"
    if [[ "${UPDATE:-0}" == 1 ]]; then
        cp "$actual" "$expected"
        pass "$name (fixture updated)"
    elif diff -u "$expected" "$actual"; then
        pass "$name"
    else
        fail "$name"
    fi
}

out="$(mktemp -d)"
trap 'rm -rf "$out"; [[ -n "${REDACT_SED:-}" ]] && rm -f "$REDACT_SED"' EXIT

redact_init "$fixtures/redact.env"
redact < "$fixtures/redact.input.log" > "$out/redact.log"
compare "redaction matches fixtures/redact.output.log" "$fixtures/redact.output.log" "$out/redact.log"

# Independently of the expected file: no secret value from the .env survives,
# in plain or JSON-escaped form.
while IFS=$'\t' read -r key value; do
    is_secret_key "$key" || continue
    [[ ${#value} -ge 4 && ! "$value" =~ ^(null|true|false)$ ]] || continue
    for form in "$value" "${value//\//\\/}"; do
        if grep -qF -- "$form" "$out/redact.log"; then
            fail "$key's value survives redaction"
        fi
    done
done < <(env_each "$fixtures/redact.env")
pass "no secret value from the .env survives"

for pattern in '9f0c2e1a-3b4d-4e5f-8a9b-0c1d2e3f4a5b' 'Hunter2' 'abc123def456' 'hex0123456789' 'dXNlcjpwYXNzd29yZA' \
               'abcdefghijklmnopqrstuvwxyz0123456789' 'eyJpdiI6' 'b2xkLWtleS1vbGQta2V5' 'abcdef123456' 'k_9876543210' 'Old\\"Pass' 'c2lnbmF0dXJl'; do
    if grep -qF -- "$pattern" "$out/redact.log"; then
        fail "pattern-matched secret survives: $pattern"
    fi
done
pass "no pattern-matched secret survives"

# What diagnosis needs is still there.
for keep in 'https://panel.example.com' 'pve1.example.com:8006' 'root@pam!convoy=' 'theme=dark' 'GuzzleHttp\\Exception\\ConnectException(code: 0)' \
            'Basic checks passed' 'mail username null' '"remember":true' '"name\":\"x\"' 'CurlFactory.php:275'; do
    grep -qF -- "$keep" "$out/redact.log" || fail "redaction removed something it should keep: $keep"
done
pass "non-secrets survive"

support_env_json "$fixtures/redact.env" > "$out/env.json"
compare "env.json matches fixtures/redact.env.json" "$fixtures/redact.env.json" "$out/env.json"
if command -v python3 >/dev/null 2>&1; then
    python3 -m json.tool "$out/env.json" > /dev/null || fail "env.json is not valid JSON"
fi
if grep -qF 'p4ss.w0rd' "$out/env.json" || grep -qF 'tok_1234567890abcdef' "$out/env.json"; then
    fail "env.json carries a secret value"
fi

# The dotenv reader, which both of the above depend on.
[[ "$(env_get "$fixtures/redact.env" REDIS_PASSWORD)" == "redis-secret-value-1234" ]] || fail "env_get: double quotes"
[[ "$(env_get "$fixtures/redact.env" MAIL_PASSWORD)" == "quoted pass with spaces" ]] || fail "env_get: single quotes"
[[ "$(env_get "$fixtures/redact.env" SOME_API_TOKEN)" == "tok_1234567890abcdef" ]] || fail "env_get: inline comment"
[[ "$(env_get "$fixtures/redact.env" CONVOY_TELEMETRY_ENABLED)" == "false" ]] || fail "env_get: export prefix"
env_get "$fixtures/redact.env" NOT_THERE >/dev/null && fail "env_get: missing key reported as present"
pass "dotenv parsing"

if (( failures )); then
    printf '\n%d failure(s)\n' "$failures" >&2
    exit 1
fi
printf '\nall passed\n'
