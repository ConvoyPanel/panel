#!/bin/bash

set -euo pipefail

# === Styling ===
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

print()  { echo -e "${GREEN}==>${NC} $1"; }
warn()   { echo -e "${YELLOW}WARNING:${NC} $1"; }
logv()   { [[ "$VERBOSE" == true ]] && echo -e "$1"; }
run_verbose() {
    if [[ "$VERBOSE" == true ]]; then
        "$@"
    else
        "$@" > /dev/null
    fi
}

# === Banner ===
echo -e "${GREEN}"
cat <<'BANNER'
 ██████  ██████  ███    ██ ██    ██  ██████  ██    ██
██      ██    ██ ████   ██ ██    ██ ██    ██  ██  ██
██      ██    ██ ██ ██  ██ ██    ██ ██    ██   ████
██      ██    ██ ██  ██ ██  ██  ██  ██    ██    ██
 ██████  ██████  ██   ████   ████    ██████     ██
BANNER
echo -e "${NC}"

echo -e "${YELLOW}Convoy Panel Installer${NC} — Production-Ready Setup"
echo -e "Documentation: ${GREEN}https://convoypanel.com/docs/project/introduction.html${NC}"
echo -e "Buy License:   ${GREEN}https://console.convoypanel.com/${NC}"
echo -e "Source Code:   ${GREEN}https://github.com/ConvoyPanel/panel${NC}"
echo ""

# === Defaults ===
INSTALL_DIR="/var/www/convoy"
PANEL_URL="https://github.com/convoypanel/panel/releases/latest/download/panel.tar.gz"
APP_URL=""
DB_DATABASE=""
DB_USERNAME=""
NON_INTERACTIVE=false
FORCE=false
VERBOSE=false

# === Parse Flags ===
while [[ $# -gt 0 ]]; do
    case "$1" in
        --panel-url)       PANEL_URL="$2"; shift 2 ;;
        --app-url)         APP_URL="$2"; shift 2 ;;
        --db-name)         DB_DATABASE="$2"; shift 2 ;;
        --db-user)         DB_USERNAME="$2"; shift 2 ;;
        --install-dir)     INSTALL_DIR="$2"; shift 2 ;;
        --non-interactive) NON_INTERACTIVE=true; shift ;;
        --force)           FORCE=true; shift ;;
        --verbose)         VERBOSE=true; shift ;;
        -h|--help)
            echo "Usage: $0 [options]"
            echo "Options:"
            echo "  --panel-url URL       Custom panel tarball URL"
            echo "  --app-url URL         Application base URL"
            echo "  --db-name NAME        Database name"
            echo "  --db-user USER        Database user"
            echo "  --install-dir PATH    Installation directory"
            echo "  --non-interactive     Skip prompts"
            echo "  --force               Force reinstall (stop, wipe, rebuild)"
            echo "  --verbose             Enable detailed output"
            exit 0
            ;;
        *) warn "Unknown argument: $1"; exit 1 ;;
    esac
done

COMPOSE_CMD="docker compose -f $INSTALL_DIR/docker-compose.yml"

# === Require Root ===
if [[ "$EUID" -ne 0 ]]; then
    warn "Please run as root (use sudo)."
    exit 1
fi

# === Handle Existing Install in This Directory ===
if [[ -f "$INSTALL_DIR/.env" ]]; then
    warn "Convoy already installed at: $INSTALL_DIR"

    if [[ "$FORCE" == false ]]; then
        if [[ "$NON_INTERACTIVE" == true ]]; then
            warn "Aborting. Use --force to reinstall."
            exit 1
        fi

        read -p "Overwrite this install? This deletes containers and DB. [y/N]: " confirm
        [[ "$confirm" =~ ^[Yy]$ ]] || { print "Aborted."; exit 0; }
    fi

    print "Stopping and removing containers..."
    cd "$INSTALL_DIR"
    run_verbose $COMPOSE_CMD down -v || true

    print "Wiping $INSTALL_DIR..."
    if [[ -z "$INSTALL_DIR" || "$INSTALL_DIR" == "/" || "$INSTALL_DIR" == "/root" || "$INSTALL_DIR" == "/home" ]]; then
        warn "Refusing to wipe suspicious directory: $INSTALL_DIR"
        exit 1
    fi

    [[ "$(pwd)" == "$INSTALL_DIR"* ]] && cd /
    rm -rf "$INSTALL_DIR"
fi

# === Prompt Configuration ===
if [[ "$NON_INTERACTIVE" == false ]]; then
    echo ""
    read -p "Convoy Panel URL [default: http://localhost]: " input
    APP_URL="${APP_URL:-${input:-http://localhost}}"

    read -p "Database name [default: convoy]: " input
    DB_DATABASE="${DB_DATABASE:-${input:-convoy}}"

    read -p "Database user [default: convoy_user]: " input
    DB_USERNAME="${DB_USERNAME:-${input:-convoy_user}}"
else
    APP_URL="${APP_URL:-http://localhost}"
    DB_DATABASE="${DB_DATABASE:-convoy}"
    DB_USERNAME="${DB_USERNAME:-convoy_user}"
fi

# === Generate Secrets ===
DB_PASSWORD=$(openssl rand -base64 16)
REDIS_PASSWORD=$(openssl rand -base64 16)

# === Docker Check ===
print "Checking Docker..."
if ! command -v docker &> /dev/null; then
    curl -fsSL https://get.docker.com/ | sh
else
    print "Docker is installed."
fi

# === Setup ===
print "Setting up directory..."
mkdir -p "$INSTALL_DIR"
cd "$INSTALL_DIR"

print "Downloading panel..."
run_verbose curl -Lo panel.tar.gz "$PANEL_URL"
run_verbose tar -xzf panel.tar.gz
chmod -R o+w storage/* bootstrap/cache/

# === Configure .env ===
print "Configuring environment..."
cp .env.example .env
sed -i "s|^APP_ENV=.*|APP_ENV=production|" .env
sed -i "s|^APP_DEBUG=.*|APP_DEBUG=false|" .env
sed -i "s|^APP_URL=.*|APP_URL=$APP_URL|" .env
sed -i "s|^DB_DATABASE=.*|DB_DATABASE=$DB_DATABASE|" .env
sed -i "s|^DB_USERNAME=.*|DB_USERNAME=$DB_USERNAME|" .env
sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD='$DB_PASSWORD'|" .env
sed -i "s|^REDIS_PASSWORD=.*|REDIS_PASSWORD='$REDIS_PASSWORD'|" .env

# === Build and Start ===
print "Building and starting containers..."
run_verbose $COMPOSE_CMD up -d --build

# === Laravel Setup ===
print "Installing dependencies..."
run_verbose $COMPOSE_CMD exec workspace bash -c "composer install --no-dev --optimize-autoloader"

print "Setting app key..."
run_verbose $COMPOSE_CMD exec workspace bash -c "php artisan key:generate --force && php artisan optimize"

print "Migrating database..."
run_verbose $COMPOSE_CMD exec workspace php artisan migrate --force

# === Create Admin ===
if [[ "$NON_INTERACTIVE" == false || "$VERBOSE" == true ]]; then
    print "Creating admin user..."
    $COMPOSE_CMD exec workspace php artisan c:user:make --admin=true
else
    warn "Skipping admin user creation (interactive only)."
    echo "You can run this later:"
    echo "  $COMPOSE_CMD exec workspace php artisan c:user:make --admin=true"
fi

# === Done ===
echo ""
echo -e "${GREEN}=== INSTALL COMPLETE ===${NC}"
echo "Panel URL:      $APP_URL"
echo "Install Dir:    $INSTALL_DIR"
echo "Database Name:  $DB_DATABASE"
echo "Database User:  $DB_USERNAME"
echo "Database Pass:  $DB_PASSWORD"
echo "Redis Password: $REDIS_PASSWORD"
echo ""
echo -e "${YELLOW}IMPORTANT: Save these credentials securely!${NC}"
echo ""
echo -e "${GREEN}To get started:${NC}"
echo -e "  - Buy a license at: ${YELLOW}https://console.convoypanel.com/${NC}"
echo -e "  - View docs at:     ${YELLOW}https://convoypanel.com/docs/project/introduction.html${NC}"
echo -e "  - License info:     ${YELLOW}https://github.com/ConvoyPanel/panel/blob/develop/LICENSE.md${NC}"
