#!/usr/bin/env bash
#
# Convoy installer.
#
#   curl -fsSL https://install.convoypanel.com | sudo bash
#   curl -fsSL https://install.convoypanel.com | sudo bash -s -- --domain panel.example.com --email you@example.com
#
# Upgrading a v4 install (rehearse with --dry-run first; v4 keeps serving):
#
#   curl -fsSL https://install.convoypanel.com | sudo bash -s -- --import /var/www/convoy --dry-run
#   curl -fsSL https://install.convoypanel.com | sudo bash -s -- --import /var/www/convoy
#
# Installs Docker if it is missing, writes /opt/convoy, installs the `convoy`
# command, starts the stack and creates the first administrator -- or, with
# --import, hands over to `convoy import-v4` to bring a v4 install across.

set -euo pipefail

CONVOY_DIR="${CONVOY_DIR:-/opt/convoy}"
CONVOY_VERSION="${CONVOY_VERSION:-5}"
CONVOY_GITHUB_REPO="${CONVOY_GITHUB_REPO:-ConvoyPanel/panel}"
CONVOY_REPO="${CONVOY_REPO:-https://raw.githubusercontent.com/$CONVOY_GITHUB_REPO}"
# A git ref to take compose.yml and the CLI from, bypassing the version lookup.
# For testing a branch build; CONVOY_VERSION is then used as the image tag as is.
CONVOY_REF="${CONVOY_REF:-}"
DOMAIN=""
ADMIN_EMAIL=""
IMPORT_DIR=""
DRY_RUN=0
ASSUME_YES=0

if [[ -t 1 ]]; then
    RED=$'\033[0;31m' GREEN=$'\033[0;32m' YELLOW=$'\033[0;33m' BOLD=$'\033[1m' DIM=$'\033[2m' RESET=$'\033[0m'
else
    RED='' GREEN='' YELLOW='' BOLD='' DIM='' RESET=''
fi

info() { printf '%s\n' "${GREEN}==>${RESET} $*"; }
warn() { printf '%s\n' "${YELLOW}warning:${RESET} $*" >&2; }
die()  { printf '%s\n' "${RED}error:${RESET} $*" >&2; exit 1; }

usage() {
    cat <<'EOF'
Convoy installer

  --domain <host>     Hostname or IP the panel will be reached on
  --email <address>   Email address for the first administrator
  --version <tag>     5 (the newest 5.x, default), 5.1, or an exact release such as 5.0.1
  --dir <path>        Install directory (default: /opt/convoy)
  --import <dir>      Upgrade the v4 install in <dir> (usually /var/www/convoy)
                      instead of starting a fresh one
  --dry-run           With --import: rehearse on a copy while v4 keeps serving
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
        --import)  IMPORT_DIR="${2:?--import needs the v4 directory}"; shift 2 ;;
        --dry-run) DRY_RUN=1; shift ;;
        --yes|-y)  ASSUME_YES=1; shift ;;
        --help|-h) usage; exit 0 ;;
        *)         usage; die "unknown option: $1" ;;
    esac
done

(( DRY_RUN )) && [[ -z "$IMPORT_DIR" ]] && die "--dry-run only applies to --import."

##########################################################################
# Helpers
##########################################################################

# Replaces the first KEY= line of a dotenv file, or appends one. Pure bash, so
# nothing in the value (slashes, ampersands, quotes) needs escaping.
env_set() {
    local file="$1" key="$2" value="$3" line done=0 tmp
    tmp="$(mktemp "$file.XXXXXX")"
    while IFS= read -r line || [[ -n "$line" ]]; do
        if (( ! done )) && [[ "$line" =~ ^[[:space:]]*${key}[[:space:]]*= ]]; then
            printf '%s=%s\n' "$key" "$value"
            done=1
        else
            printf '%s\n' "$line"
        fi
    done < "$file" > "$tmp"
    (( done )) || printf '%s=%s\n' "$key" "$value" >> "$tmp"
    chmod 600 "$tmp"
    mv "$tmp" "$file"
}

# Asks on the terminal even when the script itself arrived on stdin (curl | bash).
prompt_for() {
    local var="$1" flag="$2" message="$3" value=""
    [[ -n "${!var}" ]] && return 0
    (( ASSUME_YES )) && die "$message (pass $flag when using --yes)."
    { : < /dev/tty; } 2>/dev/null || die "$message (no terminal available; pass $flag instead)."
    read -rp "$message: " value < /dev/tty
    [[ -n "$value" ]] || die "a value is required."
    printf -v "$var" '%s' "$value"
}

# The newest published (not draft, not pre-release) release whose tag starts
# with PREFIX. GitHub's own "latest" is the newest release of any major, which
# after 6.0 ships is no longer a 5.x one.
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

##########################################################################
# Preflight
##########################################################################
[[ "$(id -u)" -eq 0 ]] || die "run this as root (prefix the command with sudo)."

if [[ -e "$CONVOY_DIR/.env" ]]; then
    if [[ -n "$IMPORT_DIR" ]]; then
        die "$CONVOY_DIR is already set up. Run the import with: convoy import-v4 $IMPORT_DIR$( ((DRY_RUN)) && printf ' --dry-run' || true)"
    fi
    die "a Convoy install already exists at $CONVOY_DIR. Use 'convoy upgrade' to update it, or pass --dir to install elsewhere."
fi

command -v curl >/dev/null 2>&1 || die "curl is required but not installed."
command -v openssl >/dev/null 2>&1 || die "openssl is required but not installed."

if [[ -n "$IMPORT_DIR" ]]; then
    [[ -f "$IMPORT_DIR/docker-compose.yml" && -f "$IMPORT_DIR/.env" ]] \
        || die "$IMPORT_DIR does not look like a v4 install (no docker-compose.yml and .env)."
    case "$(uname -m)" in
        x86_64|amd64) ;;
        *) die "upgrading from v4 needs an amd64 host: pgloader, which converts the database, crashes under arm64 emulation." ;;
    esac
else
    # Caddy binds the host's 80 and 443. Finding out from a crash loop three
    # minutes from now is a worse experience than finding out here. (An import
    # skips this: v4 holds the ports until the import stops it.)
    for port in 80 443; do
        if command -v ss >/dev/null 2>&1 && ss -Hltn "sport = :$port" 2>/dev/null | grep -q .; then
            die "port $port is already in use. Convoy needs both 80 and 443. Stop the service using it (often an existing nginx or apache) and run this again."
        fi
    done

    prompt_for DOMAIN --domain "Hostname or IP customers will reach this panel on"
    prompt_for ADMIN_EMAIL --email "Email address for the administrator account"
fi

# Let's Encrypt cannot issue for a bare IP, so an IP silently means self-signed.
# Say so now rather than letting the operator wonder why the browser complains.
AUTO_HTTPS=on
if [[ "$DOMAIN" =~ ^[0-9]{1,3}(\.[0-9]{1,3}){3}$ || "$DOMAIN" == *:*:* ]]; then
    AUTO_HTTPS=off
    warn "$DOMAIN is an IP address. Certificate authorities do not issue for bare IPs, so Convoy will serve a self-signed certificate and browsers will show a warning. Point a domain at this host and re-run with --domain to get a trusted certificate."
fi

##########################################################################
# Version
##########################################################################
# The compose file and the image have to come from the same release, so the
# version is resolved to a git tag for the files and an image tag for .env.
# `5` (and `latest`, which means the same here) follows the newest 5.x release,
# so `convoy upgrade` picks up fixes without ever crossing into 6.0.
version="${CONVOY_VERSION#v}"
if [[ -n "$CONVOY_REF" ]]; then
    ref="$CONVOY_REF"
    image_tag="$CONVOY_VERSION"
else
    case "$version" in
        latest|5)
            ref="$(newest_release v5.)"
            image_tag=5 ;;
        5.x)
            ref=5.x
            image_tag=5.x ;;
        5.[0-9]*.[0-9]*)
            ref="v$version"
            image_tag="$version" ;;
        5.[0-9]*)
            ref="$(newest_release "v$version.")"
            image_tag="$version" ;;
        *)
            die "$CONVOY_VERSION is not a Convoy 5 version. This installer sets up Convoy 5 (for example --version 5 or --version 5.0.1)." ;;
    esac
    [[ -n "$ref" ]] || die "could not find a published Convoy $version release. Pass --version with an exact release (for example --version 5.0.1)."
fi
info "installing Convoy $ref (image tag $image_tag)"

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

curl -fsSL "$CONVOY_REPO/$ref/compose.yml" -o "$CONVOY_DIR/compose.yml" \
    || die "could not download compose.yml for $ref."
( umask 077; curl -fsSL "$CONVOY_REPO/$ref/.env.docker.example" -o "$CONVOY_DIR/.env" ) \
    || die "could not download the environment template for $ref."
chmod 600 "$CONVOY_DIR/.env"

# Neither alphabet below contains `$`, so nothing here can be mangled by
# Compose's interpolation of .env.
db_password="$(openssl rand -hex 24)"
env_set "$CONVOY_DIR/.env" CONVOY_VERSION "$image_tag"
env_set "$CONVOY_DIR/.env" DB_PASSWORD "$db_password"

if [[ -z "$IMPORT_DIR" ]]; then
    # 32 bytes is what Laravel's AES-256 key needs. An import leaves this empty:
    # `convoy import-v4` copies v4's key, which must not change.
    env_set "$CONVOY_DIR/.env" APP_KEY "base64:$(openssl rand -base64 32)"
    env_set "$CONVOY_DIR/.env" APP_DOMAIN "$DOMAIN"
    env_set "$CONVOY_DIR/.env" APP_URL "https://$DOMAIN"
    env_set "$CONVOY_DIR/.env" CONVOY_AUTO_HTTPS "$AUTO_HTTPS"
    env_set "$CONVOY_DIR/.env" MAIL_FROM_ADDRESS "convoy@$DOMAIN"
    # Browsers send no server name when they connect to an IP address, and
    # without one Caddy cannot pick the certificate: the handshake fails.
    if [[ "$DOMAIN" =~ ^[0-9]{1,3}(\.[0-9]{1,3}){3}$ ]]; then
        env_set "$CONVOY_DIR/.env" CADDY_GLOBAL_OPTIONS "\"default_sni $DOMAIN\""
    fi
fi

# A released CLI comes from the release assets, where it carries its version;
# a branch build from the tree.
info "installing the convoy command"
cli_tmp="$(mktemp)"
if [[ "$ref" =~ ^v5\. ]] && curl -fsSL "https://github.com/$CONVOY_GITHUB_REPO/releases/download/$ref/convoy" -o "$cli_tmp" 2>/dev/null; then
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

if [[ -n "$IMPORT_DIR" ]]; then
    import_args=("$IMPORT_DIR")
    (( DRY_RUN )) && import_args+=(--dry-run)
    (( ASSUME_YES )) && import_args+=(--yes)
    info "handing over to: convoy import-v4 ${import_args[*]}"
    # The CLI asks for confirmation on a terminal, which stdin is not under
    # curl | bash.
    if { : < /dev/tty; } 2>/dev/null; then
        CONVOY_DIR="$CONVOY_DIR" exec /usr/local/bin/convoy import-v4 "${import_args[@]}" < /dev/tty
    fi
    CONVOY_DIR="$CONVOY_DIR" exec /usr/local/bin/convoy import-v4 "${import_args[@]}"
fi

info "starting Convoy"
docker compose up -d --remove-orphans

info "waiting for the panel to come up"
waited=0
until [[ "$(docker inspect -f '{{.State.Health.Status}}' "$(docker compose ps -q web)" 2>/dev/null)" == "healthy" ]]; do
    if (( waited >= 300 )); then
        docker compose logs --tail=50 web >&2
        die "the panel did not start within 5 minutes. The last 50 log lines are above. 'convoy support' collects everything the Convoy team needs to help."
    fi
    sleep 5
    waited=$(( waited + 5 ))
done

##########################################################################
# First administrator
##########################################################################
# The panel requires a lowercase and an uppercase letter, a digit and one of
# !@#$%^&* in every password. `$` is left out so the password survives being
# pasted into a shell.
admin_password=""
until [[ "$admin_password" =~ [a-z] && "$admin_password" =~ [A-Z] && "$admin_password" =~ [0-9] ]]; do
    admin_password="$(openssl rand -base64 24 | tr -d '/+=' | cut -c1-20)"
done
specials='!@#%^&*'
admin_password="${admin_password:0:10}${specials:RANDOM % ${#specials}:1}${admin_password:10}"

info "creating the administrator account"
docker compose exec -T web php artisan users:create \
    --email "$ADMIN_EMAIL" \
    --name "Administrator" \
    --password "$admin_password" \
    --admin true >/dev/null || die "could not create the administrator. The panel is running; create one with: convoy artisan users:create"

cat <<EOF

${GREEN}${BOLD}Convoy is installed.${RESET}

  URL        ${BOLD}https://$DOMAIN${RESET}
  Email      $ADMIN_EMAIL
  Password   ${BOLD}$admin_password${RESET}

  ${DIM}Change this password after signing in. It is not stored anywhere else.${RESET}

Manage the install with ${BOLD}convoy${RESET}:

  convoy ps                status of every container
  convoy logs web          follow the panel's logs
  convoy upgrade           back up, pull, restart
  convoy artisan <cmd>     run an Artisan command
  convoy support           send logs to the Convoy team when something is wrong

Configuration lives in ${BOLD}$CONVOY_DIR/.env${RESET}.
EOF

if [[ "$AUTO_HTTPS" == "off" ]]; then
    printf '\n%s\n' "${YELLOW}The certificate is self-signed. Your browser will warn on first visit.${RESET}"
fi
