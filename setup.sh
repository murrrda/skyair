#!/usr/bin/env bash
#
# Setup script for the Skyair Laravel + React project.
# - Checks for required tooling and installs anything missing.
# - Bootstraps the Laravel app (.env, vendor deps, app key, migrations).
#
# Supports: Fedora/RHEL (dnf), Debian/Ubuntu (apt), Arch (pacman),
# and macOS (brew). Other systems will be reported as unsupported
# for automatic install; you'll be asked to install manually.

set -euo pipefail

PHP_MIN_MAJOR=8
PHP_MIN_MINOR=4
NODE_MIN_MAJOR=20

C_RESET=$'\033[0m'
C_BOLD=$'\033[1m'
C_GREEN=$'\033[32m'
C_YELLOW=$'\033[33m'
C_RED=$'\033[31m'
C_BLUE=$'\033[34m'

log()   { printf '%s==>%s %s\n' "$C_BLUE$C_BOLD" "$C_RESET" "$*"; }
ok()    { printf '%s✓%s %s\n' "$C_GREEN" "$C_RESET" "$*"; }
warn()  { printf '%s!%s %s\n' "$C_YELLOW" "$C_RESET" "$*"; }
err()   { printf '%s✗%s %s\n' "$C_RED" "$C_RESET" "$*" >&2; }

have() { command -v "$1" >/dev/null 2>&1; }

PKG_MGR=""
SUDO=""

detect_pkg_mgr() {
    case "$(uname -s)" in
        Linux)
            if have dnf;     then PKG_MGR="dnf"
            elif have apt;   then PKG_MGR="apt"
            elif have pacman; then PKG_MGR="pacman"
            else PKG_MGR="unknown"
            fi
            if [ "$(id -u)" -ne 0 ] && have sudo; then
                SUDO="sudo"
            fi
            ;;
        Darwin)
            if have brew; then
                PKG_MGR="brew"
            else
                PKG_MGR="unknown"
            fi
            ;;
        *)
            PKG_MGR="unknown"
            ;;
    esac
}

pkg_install() {
    local pkgs=("$@")
    case "$PKG_MGR" in
        dnf)    $SUDO dnf install -y "${pkgs[@]}" ;;
        apt)    $SUDO apt-get update -y && $SUDO apt-get install -y "${pkgs[@]}" ;;
        pacman) $SUDO pacman -S --noconfirm --needed "${pkgs[@]}" ;;
        brew)   brew install "${pkgs[@]}" ;;
        *)
            err "No supported package manager found. Install manually: ${pkgs[*]}"
            return 1
            ;;
    esac
}

version_ge() {
    # version_ge "8.4.1" "8.4" → 0 if first >= second
    [ "$(printf '%s\n%s\n' "$2" "$1" | sort -V | head -n1)" = "$2" ]
}

check_php() {
    log "Checking PHP (>= ${PHP_MIN_MAJOR}.${PHP_MIN_MINOR})"
    if have php; then
        local v
        v=$(php -r 'echo PHP_VERSION;' 2>/dev/null || echo "0")
        if version_ge "$v" "${PHP_MIN_MAJOR}.${PHP_MIN_MINOR}"; then
            ok "PHP $v already installed"
            return 0
        fi
        warn "PHP $v is too old; need >= ${PHP_MIN_MAJOR}.${PHP_MIN_MINOR}"
    else
        warn "PHP not installed"
    fi

    log "Installing PHP and required extensions"
    case "$PKG_MGR" in
        dnf)
            pkg_install php php-cli php-mbstring php-xml php-tokenizer php-zip \
                php-pgsql php-redis php-curl php-bcmath php-intl php-gd php-fpm
            ;;
        apt)
            pkg_install php php-cli php-mbstring php-xml php-zip php-pgsql \
                php-redis php-curl php-bcmath php-intl php-gd
            ;;
        pacman)
            pkg_install php php-pgsql php-redis php-gd php-intl
            ;;
        brew)
            pkg_install php
            ;;
        *)
            err "Install PHP >= ${PHP_MIN_MAJOR}.${PHP_MIN_MINOR} manually."
            return 1
            ;;
    esac
    ok "PHP installed: $(php -v | head -n1)"

    # On Arch, extensions must be explicitly enabled in php.ini
    if [ "$PKG_MGR" = "pacman" ] && [ -f /etc/php/php.ini ]; then
        local exts=(iconv mbstring)
        for ext in "${exts[@]}"; do
            if ! php -m 2>/dev/null | grep -qi "^${ext}$"; then
                log "Enabling PHP extension: $ext"
                $SUDO sed -i "s/^;extension=${ext}/extension=${ext}/" /etc/php/php.ini
            fi
        done
    fi
}

check_composer() {
    log "Checking Composer"
    if have composer; then
        ok "Composer already installed: $(composer --version | head -n1)"
        return 0
    fi
    warn "Composer not installed"

    case "$PKG_MGR" in
        dnf|apt|pacman) pkg_install composer ;;
        brew)           pkg_install composer ;;
        *)
            log "Installing Composer via official installer"
            local tmp
            tmp=$(mktemp -d)
            php -r "copy('https://getcomposer.org/installer', '$tmp/composer-setup.php');"
            php "$tmp/composer-setup.php" --install-dir="$tmp" --filename=composer
            $SUDO mv "$tmp/composer" /usr/local/bin/composer
            rm -rf "$tmp"
            ;;
    esac
    ok "Composer installed"
}

check_node() {
    log "Checking Node.js (>= ${NODE_MIN_MAJOR})"
    if have node; then
        local v
        v=$(node -v | sed 's/^v//')
        if version_ge "$v" "${NODE_MIN_MAJOR}.0.0"; then
            ok "Node.js v$v already installed"
            return 0
        fi
        warn "Node.js v$v is too old; need >= v${NODE_MIN_MAJOR}"
    else
        warn "Node.js not installed"
    fi

    case "$PKG_MGR" in
        dnf)    pkg_install nodejs npm ;;
        apt)    pkg_install nodejs npm ;;
        pacman) pkg_install nodejs npm ;;
        brew)   pkg_install node ;;
        *)
            err "Install Node.js >= ${NODE_MIN_MAJOR} manually."
            return 1
            ;;
    esac
    ok "Node.js installed: $(node -v)"
}

check_git() {
    log "Checking Git"
    if have git; then
        ok "Git already installed: $(git --version)"
        return 0
    fi
    warn "Git not installed"
    pkg_install git
    ok "Git installed"
}

check_docker() {
    log "Checking Docker"
    if have docker; then
        ok "Docker already installed: $(docker --version)"
    else
        warn "Docker not installed"
        case "$PKG_MGR" in
            dnf)    pkg_install docker ;;
            apt)    pkg_install docker.io ;;
            pacman) pkg_install docker ;;
            brew)
                warn "Install Docker Desktop manually from https://www.docker.com/products/docker-desktop/"
                return 0
                ;;
            *)
                err "Install Docker manually."
                return 1
                ;;
        esac
        ok "Docker installed"
    fi

    if docker compose version >/dev/null 2>&1; then
        ok "Docker Compose plugin available"
    elif have docker-compose; then
        ok "docker-compose (legacy) available"
    else
        warn "Docker Compose plugin not available; installing"
        case "$PKG_MGR" in
            dnf)    pkg_install docker-compose-plugin || pkg_install docker-compose ;;
            apt)    pkg_install docker-compose-plugin || pkg_install docker-compose ;;
            pacman) pkg_install docker-compose ;;
            *)      warn "Install Docker Compose manually." ;;
        esac
    fi

    if [ "$(uname -s)" = "Linux" ] && have systemctl; then
        if ! systemctl is-active --quiet docker; then
            log "Starting and enabling docker service"
            $SUDO systemctl enable --now docker || warn "Could not enable docker service"
        fi
    fi
}

bootstrap_env() {
    log "Bootstrapping .env"
    if [ -f .env ]; then
        ok ".env already exists"
    else
        cp .env.example .env
        ok ".env created from .env.example"
    fi
}

composer_install() {
    log "Installing PHP dependencies (composer install)"
    if [ -d vendor ] && [ -f vendor/autoload.php ]; then
        ok "vendor/ already present — running composer install to sync"
    fi
    composer install --no-interaction --prefer-dist
}

npm_install() {
    log "Installing JS dependencies (npm install)"
    if [ -d node_modules ]; then
        ok "node_modules already present — running npm install to sync"
    fi
    npm install
}

generate_app_key() {
    log "Generating APP_KEY (if needed)"
    if grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
        ok "APP_KEY already set"
        return 0
    fi
    php artisan key:generate --ansi
    ok "APP_KEY generated"
}

main() {
    cd "$(dirname "$0")"

    log "Skyair setup starting"
    detect_pkg_mgr
    log "Detected package manager: ${PKG_MGR:-none}"

    check_git
    check_php
    check_composer
    check_node
    check_docker

    bootstrap_env
    composer_install
    npm_install
    generate_app_key

    cat <<EOF

${C_GREEN}${C_BOLD}Setup complete.${C_RESET}

Next steps:
  1. Start the dev stack (Postgres + Redis via Docker):
       ${C_BOLD}./vendor/bin/sail up -d${C_RESET}
     Or run services directly without Sail and adjust .env (DB_HOST=127.0.0.1).

  2. Run migrations:
       ${C_BOLD}./vendor/bin/sail artisan migrate${C_RESET}
     or, with a local PHP:
       ${C_BOLD}php artisan migrate${C_RESET}

  3. Start the dev server:
       ${C_BOLD}composer run dev${C_RESET}

EOF
}

main "$@"
