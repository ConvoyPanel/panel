#!/bin/bash

set -euo pipefail

# === Error Handling ===
error_exit() {
    echo -e "${YELLOW}ERROR:${NC} $1"
    exit 1
}

# Trap unexpected errors
trap 'error_exit "An unexpected error occurred." ' ERR

# === Styling ===
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

print()  { echo -e "${GREEN}==>${NC} $1"; }
warn()   { echo -e "${YELLOW}WARNING:${NC} $1"; }
logv()   { [[ "$VERBOSE" == true ]] && echo -e "$1"; }
runv()   { [[ "$VERBOSE" == true ]] && "$@" || "$@" > /dev/null; }

# === Banner ===
echo -e "${GREEN}"
cat <<'BANNER'
 ██████  ██████  ███    ██ ██    ██  ██████  ██    ██
██      ██    ██ ████   ██ ██    ██ ██    ██  ██  ██
██      ██    ██ ██ ██  ██ ██    ██ ██    ██   ████
██      ██    ██ ██  ██ ██  ██  ██  ██    ██    ██
 ██████  ██████  ██   ████   ████    ██████     ██

           Convoy Panel Update Script
BANNER
echo -e "${NC}"

# === Defaults ===
INSTALL_DIR=""
PANEL_URL="https://github.com/convoypanel/panel/releases/latest/download/panel.tar.gz"
PANEL_FILE=""
NON_INTERACTIVE=false
VERBOSE=false
BACKUP_DIR="${HOME}/convoy-backups"
BACKUP_FILE="convoy-backup-$(date +'%Y%m%d-%H%M%S').tar.gz"

# === Parse Flags ===
while [[ $# -gt 0 ]]; do
    case "$1" in
        --install-dir) INSTALL_DIR="$2"; shift 2 ;;
        --panel-url)   PANEL_URL="$2"; shift 2 ;;
        --panel-file)  PANEL_FILE="$2"; shift 2 ;;
        --non-interactive) NON_INTERACTIVE=true; shift ;;
        --verbose)     VERBOSE=true; shift ;;
        -h|--help)
            echo "Usage: $0 [options]"
            echo "  --install-dir PATH    Path to Convoy install (if auto-detect fails)"
            echo "  --panel-url URL       Use custom Convoy panel .tar.gz URL"
            echo "  --panel-file FILE     Use local Convoy panel .tar.gz file"
            echo "  --non-interactive     Run without prompts"
            echo "  --verbose             Enable detailed output"
            exit 0
            ;;
        *) warn "Unknown argument: $1"; exit 1 ;;
    esac
done

# === Auto-detect install dir ===
if [[ -z "$INSTALL_DIR" ]]; then
    print "Auto-detecting Convoy install directory..."
    found_dirs=()
    for cid in $(docker ps -q); do
        mounts=$(docker inspect -f '{{range .Mounts}}{{.Source}} {{end}}' "$cid" 2>/dev/null || true)
        for m in $mounts; do
            [[ "$m" =~ convoy ]] && [[ -f "$m/docker-compose.yml" ]] && found_dirs+=("$m")
        done
    done
    found_dirs=($(printf "%s\n" "${found_dirs[@]}" | sort -u))

    if [[ ${#found_dirs[@]} -eq 1 ]]; then
        INSTALL_DIR="${found_dirs[0]}"
        print "Detected Convoy install: $INSTALL_DIR"
    elif [[ ${#found_dirs[@]} -gt 1 && "$NON_INTERACTIVE" == false ]]; then
        echo "Multiple installs found:"
        select dir in "${found_dirs[@]}"; do
            INSTALL_DIR="$dir"
            break
        done
    else
        warn "Could not detect install location."
        echo "Please rerun with: --install-dir /path/to/convoy"
        exit 1
    fi
fi

cd "$INSTALL_DIR"
COMPOSE="docker compose -f $INSTALL_DIR/docker-compose.yml"

# === Get current version (from config/app.php) ===
OLD_VERSION=$(grep "'version' =>" config/app.php | cut -d"'" -f4 || echo "unknown")
# Prepend 'v' to the current version if not already there
OLD_VERSION="v$OLD_VERSION"
print "Current version: $OLD_VERSION"

# === Fetch latest release tag from GitHub ===
LATEST_VERSION=$(curl -s https://api.github.com/repos/ConvoyPanel/panel/releases/latest | jq -r .tag_name)

# === Compare versions ===
if [[ "$OLD_VERSION" == "$LATEST_VERSION" ]]; then
    print "Convoy is already up to date (version: $LATEST_VERSION). No update needed."
    exit 0
else
    print "New release found: $LATEST_VERSION. Proceeding with the update..."
fi

# === Extract APP_URL from .env ===
APP_URL=$(grep -oP '^APP_URL=\K.+' .env || error_exit "APP_URL not found in .env")

# === Maintenance Mode ===
MAINTENANCE_SECRET=$(openssl rand -hex 8)
print "Entering maintenance mode..."
runv $COMPOSE up -d workspace || error_exit "Failed to start Convoy containers in maintenance mode"
runv $COMPOSE exec workspace php artisan down --secret="$MAINTENANCE_SECRET" || error_exit "Failed to set Convoy into maintenance mode"

echo -e "${YELLOW}Bypass URL during maintenance:${NC}"
echo "  $APP_URL/$MAINTENANCE_SECRET"

# === Horizon Job Check ===
print "Please check the Horizon job queue manually before proceeding."
echo -e "${YELLOW}Navigate to the following URL to check job statuses:${NC}"
echo "  $APP_URL/horizon"
if [[ "$NON_INTERACTIVE" == false ]]; then
    read -p "Have you verified that all jobs are completed? [y/N]: " ready
    [[ "$ready" =~ ^[Yy]$ ]] || { echo "Aborted."; exit 1; }
fi

# === Stop containers ===
print "Stopping containers..."
runv $COMPOSE down || error_exit "Failed to stop Convoy containers"

# === Backup Creation ===
print "Backing up Convoy installation to: $BACKUP_DIR/$BACKUP_FILE"
mkdir -p "$BACKUP_DIR"
runv tar -czf "$BACKUP_DIR/$BACKUP_FILE" . || error_exit "Failed to create backup"

# === Download and unpack ===
if [[ -n "$PANEL_FILE" ]]; then
    print "Using local panel archive: $PANEL_FILE"
    runv tar -xzf "$PANEL_FILE" || error_exit "Failed to unpack the local panel archive"
else
    print "Downloading panel from: $PANEL_URL"
    runv curl -L "$PANEL_URL" -o panel.tar.gz || error_exit "Failed to download panel"
    runv tar -xzf panel.tar.gz || error_exit "Failed to unpack downloaded panel"
fi

print "Fixing permissions..."
chmod -R o+w storage/* bootstrap/cache/ || error_exit "Failed to set correct file permissions"

# === Rebuild containers ===
print "Rebuilding containers..."
runv $COMPOSE up -d --build || error_exit "Failed to rebuild Convoy containers"

# === Dependencies & DB ===
print "Installing Composer dependencies..."
runv $COMPOSE exec workspace composer install --no-dev --optimize-autoloader || error_exit "Failed to install Composer dependencies"

print "Running database migrations..."
runv $COMPOSE exec workspace php artisan migrate --force || error_exit "Failed to run database migrations"

print "Refreshing application cache..."
runv $COMPOSE exec workspace php artisan optimize || error_exit "Failed to optimize application"

# === Restart & exit maintenance ===
print "Restarting stack..."
runv $COMPOSE restart || error_exit "Failed to restart Convoy containers"

print "Exiting maintenance mode..."
runv $COMPOSE exec workspace php artisan up || error_exit "Failed to exit maintenance mode"

# === Get new version ===
NEW_VERSION=$(grep "'version' =>" config/app.php | cut -d"'" -f4 || echo "unknown")

# === Done ===
echo ""
echo -e "${GREEN}=== UPDATE COMPLETE ===${NC}"
echo "Updated: $OLD_VERSION  =>  $NEW_VERSION"
echo "Backup stored at: $BACKUP_DIR/$BACKUP_FILE"
