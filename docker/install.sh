#!/usr/bin/env bash
#
# Convoy installer.
#
#   curl -fsSL https://install.convoypanel.com | sudo bash
#   curl -fsSL https://install.convoypanel.com | sudo bash -s -- --domain panel.example.com --email you@example.com
#
# Installs Docker if it is missing, writes /opt/convoy, starts the stack and
# creates the first administrator. The buyer never has to know that any of this
# is Docker underneath -- which is the point.

set -euo pipefail

CONVOY_DIR="${CONVOY_DIR:-/opt/convoy}"
CONVOY_VERSION="${CONVOY_VERSION:-6}"
CONVOY_GITHUB_REPO="${CONVOY_GITHUB_REPO:-ConvoyPanel/panel}"
CONVOY_REPO="${CONVOY_REPO:-https://raw.githubusercontent.com/$CONVOY_GITHUB_REPO}"
DOMAIN=""
ADMIN_EMAIL=""
ASSUME_YES=0

readonly RED=$'\033[0;31m' GREEN=$'\033[0;32m' YELLOW=$'\033[0;33m' BOLD=$'\033[1m' DIM=$'\033[2m' RESET=$'\033[0m'

info() { printf '%s\n' "${GREEN}==>${RESET} $*"; }
warn() { printf '%s\n' "${YELLOW}warning:${RESET} $*" >&2; }
die()  { printf '%s\n' "${RED}error:${RESET} $*" >&2; exit 1; }

usage() {
    cat <<'EOF'
Convoy installer

  --domain <host>     Hostname or IP the panel will be reached on
  --email <address>   Email address for the first administrator
  --version <version> 6 for the newest 6.x release (the default), 6.1 for the
                      newest 6.1.x, an exact release such as 6.0.1, or main
                      for unreleased builds of the main branch
  --dir <path>        Install directory (default: /opt/convoy)
  --yes               Do not prompt; fail instead of asking
  --help              Show this message

EOF
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --domain)  DOMAIN="${2:?--domain needs a value}"; shift 2 ;;
        --email)   ADMIN_EMAIL="${2:?--email needs a value}"; shift 2 ;;
        --version) CONVOY_VERSION="${2:?--version needs a value}"; shift 2 ;;
        --dir)     CONVOY_DIR="${2:?--dir needs a value}"; shift 2 ;;
        --yes|-y)  ASSUME_YES=1; shift ;;
        --help|-h) usage; exit 0 ;;
        *)         usage; die "unknown option: $1" ;;
    esac
done

##########################################################################
# Preflight
##########################################################################
[[ "$(id -u)" -eq 0 ]] || die "run this as root (prefix the command with sudo)."

[[ -e "$CONVOY_DIR/.env" ]] && die "a Convoy install already exists at $CONVOY_DIR. Use 'convoy upgrade' to update it, or pass --dir to install elsewhere."

command -v curl >/dev/null 2>&1 || die "curl is required but not installed."

# Caddy binds the host's 80 and 443. Finding out from a crash loop three minutes
# from now is a worse experience than finding out here.
for port in 80 443; do
    if command -v ss >/dev/null 2>&1 && ss -Hltn "sport = :$port" 2>/dev/null | grep -q .; then
        die "port $port is already in use. Convoy needs both 80 and 443. Stop the service using it (often an existing nginx or apache) and run this again."
    fi
done

prompt_for() {
    local var="$1" flag="$2" message="$3" value=""
    [[ -n "${!var}" ]] && return 0
    (( ASSUME_YES )) && die "$message (pass $flag when using --yes)."
    [[ -t 0 ]] || die "$message (no terminal available; pass $flag instead)."
    read -rp "$message: " value
    [[ -n "$value" ]] || die "a value is required."
    printf -v "$var" '%s' "$value"
}

prompt_for DOMAIN --domain "Hostname or IP customers will reach this panel on"
prompt_for ADMIN_EMAIL --email "Email address for the administrator account"

# Let's Encrypt cannot issue for a bare IP, so an IP silently means self-signed.
# Say so now rather than letting the operator wonder why the browser complains.
AUTO_HTTPS=on
if [[ "$DOMAIN" =~ ^[0-9]{1,3}(\.[0-9]{1,3}){3}$ || "$DOMAIN" == *:*:* ]]; then
    AUTO_HTTPS=off
    warn "$DOMAIN is an IP address. Certificate authorities do not issue for bare IPs, so Convoy will serve a self-signed certificate and browsers will show a warning. Point a domain at this host and re-run with --domain to get a trusted certificate."
fi

##########################################################################
# Docker
##########################################################################
if ! command -v docker >/dev/null 2>&1; then
    info "installing Docker"
    curl -fsSL https://get.docker.com | sh || die "Docker installation failed. Install it manually and re-run."
fi

docker compose version >/dev/null 2>&1 || die "the Docker Compose plugin is missing. Install docker-compose-plugin and re-run."
systemctl enable --now docker >/dev/null 2>&1 || true

##########################################################################
# Files
##########################################################################
info "writing $CONVOY_DIR"
mkdir -p "$CONVOY_DIR"

# The newest published release whose tag starts with PREFIX (v6. or v6.1.),
# skipping drafts and prereleases.
newest_release() {
    local prefix="$1"
    curl -fsSL -H 'Accept: application/vnd.github+json' \
        "https://api.github.com/repos/$CONVOY_GITHUB_REPO/releases?per_page=100" 2>/dev/null \
        | grep -oE '"(tag_name|draft|prerelease)": *("[^"]*"|true|false)' \
        | awk -v prefix="$prefix" '
            /"tag_name"/   { tag = $0; sub(/^"tag_name": *"/, "", tag); sub(/"$/, "", tag); seen = 0; skip = 0; next }
            /"draft"/      { seen++; if ($0 ~ /true/) skip = 1 }
            /"prerelease"/ { seen++; if ($0 ~ /true/) skip = 1 }
            seen == 2 && tag != "" { if (!skip && index(tag, prefix) == 1) print tag; tag = "" }
        ' \
        | sort -V | tail -1
}

# The compose file and the image have to come from the same release, so the
# version is resolved to a git tag for the files and an image tag for .env.
# `6` follows the newest 6.x release, so `convoy upgrade` picks up fixes but
# never turns into a major upgrade on its own. `latest` means the same here:
# it's never written to .env.
version="${CONVOY_VERSION#v}"
case "$version" in
    latest|6)
        ref="$(newest_release v6.)"
        image_tag=6 ;;
    main)
        # The main branch as it stands: unreleased, rebuilt on every push.
        ref=main
        image_tag=main ;;
    6.[0-9]*.[0-9]*)
        ref="v$version"
        image_tag="$version" ;;
    6.[0-9]*)
        ref="$(newest_release "v$version.")"
        image_tag="$version" ;;
    *)
        die "$CONVOY_VERSION isn't a Convoy 6 version. Use --version 6 for the newest release, or an exact one such as --version 6.0.1." ;;
esac
[[ -n "$ref" ]] || die "couldn't find a published Convoy $version release. Pass --version with an exact release (for example --version 6.0.1)."

curl -fsSL "$CONVOY_REPO/$ref/compose.yml" -o "$CONVOY_DIR/compose.yml" \
    || die "could not download compose.yml for $ref."
curl -fsSL "$CONVOY_REPO/$ref/.env.docker.example" -o "$CONVOY_DIR/.env" \
    || die "could not download the environment template."

# Neither alphabet below contains `$`, so nothing here can be mangled by
# Compose's interpolation of .env. 32 bytes is what Laravel's AES-256 key needs.
app_key="base64:$(openssl rand -base64 32)"
db_password="$(openssl rand -hex 24)"
admin_password="$(openssl rand -base64 18 | tr -d '/+=' | cut -c1-20)"

scheme=https
set_env() {
    local key="$1" value="$2"
    # Values are written with a literal-safe replacement so slashes in a URL or
    # key do not terminate the sed expression.
    python3 - "$CONVOY_DIR/.env" "$key" "$value" <<'PY'
import sys, re
path, key, value = sys.argv[1], sys.argv[2], sys.argv[3]
with open(path) as fh:
    text = fh.read()
pattern = re.compile(rf'^{re.escape(key)}=.*$', re.MULTILINE)
replacement = f'{key}={value}'
text, count = pattern.subn(lambda _: replacement, text, count=1)
if count == 0:
    text = text.rstrip('\n') + f'\n{replacement}\n'
with open(path, 'w') as fh:
    fh.write(text)
PY
}

set_env APP_KEY "$app_key"
set_env APP_DOMAIN "$DOMAIN"
set_env APP_URL "$scheme://$DOMAIN"
set_env CONVOY_AUTO_HTTPS "$AUTO_HTTPS"
set_env CONVOY_VERSION "$image_tag"
set_env DB_PASSWORD "$db_password"
set_env MAIL_FROM_ADDRESS "convoy@$DOMAIN"

chmod 600 "$CONVOY_DIR/.env"

# A released command comes from the release assets, where it carries its
# version; a branch build from the tree.
info "installing the convoy command"
cli_tmp="$(mktemp)"
if [[ "$ref" =~ ^v[0-9] ]] && curl -fsSL "https://github.com/$CONVOY_GITHUB_REPO/releases/download/$ref/convoy" -o "$cli_tmp" 2>/dev/null; then
    :
else
    curl -fsSL "$CONVOY_REPO/$ref/docker/convoy" -o "$cli_tmp" || die "could not download the convoy command for $ref."
fi
install -m 755 "$cli_tmp" /usr/local/bin/convoy
rm -f "$cli_tmp"

##########################################################################
# Start
##########################################################################
cd "$CONVOY_DIR"

info "pulling images (this takes a minute on a fresh host)"
docker compose pull --quiet

info "starting Convoy"
docker compose up -d --remove-orphans

info "waiting for the panel to come up"
waited=0
until [[ "$(docker inspect -f '{{.State.Health.Status}}' "$(docker compose ps -q web)" 2>/dev/null)" == "healthy" ]]; do
    if (( waited >= 300 )); then
        docker compose logs --tail=50 web >&2
        die "the panel did not start within 5 minutes. The last 50 log lines are above."
    fi
    sleep 5
    waited=$(( waited + 5 ))
done

##########################################################################
# First administrator
##########################################################################
info "creating the administrator account"
docker compose exec -T web php artisan users:create \
    --email "$ADMIN_EMAIL" \
    --name "Administrator" \
    --password "$admin_password" \
    --admin true >/dev/null || die "could not create the administrator. The panel is running; create one with: convoy artisan users:create"

cat <<EOF

${GREEN}${BOLD}Convoy is installed.${RESET}

  URL        ${BOLD}$scheme://$DOMAIN${RESET}
  Email      $ADMIN_EMAIL
  Password   ${BOLD}$admin_password${RESET}

  ${DIM}Change this password after signing in. It is not stored anywhere else.${RESET}

Manage the install with ${BOLD}convoy${RESET}:

  convoy ps                   status of every container
  convoy logs web             follow the panel's logs
  convoy upgrade              back up, pull, restart
  convoy artisan <cmd>        run an Artisan command

Configuration lives in ${BOLD}$CONVOY_DIR/.env${RESET}.
EOF

if [[ "$AUTO_HTTPS" == "off" ]]; then
    printf '\n%s\n' "${YELLOW}The certificate is self-signed. Your browser will warn on first visit.${RESET}"
fi
