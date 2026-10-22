#!/usr/bin/env bash
# Sets up KJH Hall on a fresh Ubuntu Droplet. Only this repo's checkout is
# needed on the server, for the compose files, proxy and Caddy config and
# scripts; the app image is built by GitHub Actions and pulled from GHCR
# (ghcr.io/tareqsifat/focushall).
#
#   /opt/focushall/   <- this repo (git clone), plus the server's own .env
#
# Run as root:
#
#   bash /opt/focushall/deploy.sh          # pull images and start
#   bash /opt/focushall/deploy.sh --pull   # update this checkout to origin/main first
#
# If the GHCR package is private, log in once as the user that runs compose:
#   echo ghp_xxx | docker login ghcr.io -u tareqsifat --password-stdin
#
# Safe to re-run: Docker, docker-rollout, swap and .env are only created when
# missing. Day-to-day deploys come from GitHub Actions (bin/release.sh), which
# uses `docker rollout` for zero-downtime swaps.
# Override the detected address with: APP_URL=https://example.com bash deploy.sh
set -euo pipefail

cd "$(dirname "$0")"
APP_DIR="$(pwd)"

PULL=false
for arg in "$@"; do
    case "$arg" in
        --pull) PULL=true ;;
        *) echo "Unknown option: $arg (only --pull is supported)" >&2; exit 1 ;;
    esac
done

if [ "$(id -u)" -ne 0 ]; then
    echo "Run this as root (or with sudo)." >&2
    exit 1
fi

# --- Docker Engine + compose plugin -----------------------------------------
if ! command -v docker >/dev/null 2>&1; then
    echo "==> Installing Docker"
    curl -fsSL https://get.docker.com | sh
fi
docker compose version >/dev/null

# --- docker-rollout (zero-downtime `docker rollout <service>`) ---------------
# Installed system-wide so the CI deploy user gets it too
ROLLOUT_PLUGIN=/usr/local/lib/docker/cli-plugins/docker-rollout
ROLLOUT_VERSION=v0.14   # pinned; bump deliberately
ROLLOUT_STAMP="$ROLLOUT_PLUGIN.version"
if [ ! -x "$ROLLOUT_PLUGIN" ] || [ "$(cat "$ROLLOUT_STAMP" 2>/dev/null)" != "$ROLLOUT_VERSION" ]; then
    echo "==> Installing docker-rollout $ROLLOUT_VERSION"
    mkdir -p "$(dirname "$ROLLOUT_PLUGIN")"
    curl -fsSL "https://raw.githubusercontent.com/wowu/docker-rollout/${ROLLOUT_VERSION}/docker-rollout" -o "$ROLLOUT_PLUGIN"
    chmod +x "$ROLLOUT_PLUGIN"
    echo "$ROLLOUT_VERSION" > "$ROLLOUT_STAMP"
fi

if [ "$PULL" = true ] && [ -d "$APP_DIR/.git" ]; then
    # CI leaves the checkout on a detached commit, so check out main's tip
    # rather than `git pull`
    echo "==> Updating $APP_DIR to origin/main"
    git -C "$APP_DIR" fetch --quiet origin main
    git -C "$APP_DIR" checkout --quiet --detach origin/main
fi

# --- Swap, so small Droplets do not run out of memory -----------------------
# Skipped when any swap already exists
if [ "$(swapon --show --noheadings | wc -l)" -eq 0 ]; then
    echo "==> Adding 2 GB swap"
    fallocate -l 2G /swapfile
    chmod 600 /swapfile
    mkswap /swapfile >/dev/null
    swapon /swapfile
    grep -q '^/swapfile ' /etc/fstab || echo '/swapfile none swap sw 0 0' >> /etc/fstab
fi

# --- Production .env (compose interpolation only; never baked into the image)
if [ ! -f .env ]; then
    if [ -z "${APP_URL:-}" ]; then
        ip="$(curl -fs --max-time 3 http://169.254.169.254/metadata/v1/interfaces/public/0/ipv4/address || true)"
        [ -n "$ip" ] || ip="$(hostname -I | awk '{print $1}')"
        # sslip.io resolves hall.1-2-3-4.sslip.io to 1.2.3.4, so the site gets
        # a hostname (and a real certificate) without buying a domain
        APP_URL="https://hall.${ip//./-}.sslip.io"
    fi

    echo "==> Creating $APP_DIR/.env for $APP_URL"
    umask 077
    cat > .env <<EOF
COMPOSE_FILE=docker-compose.yml:docker-compose.prod.yml

APP_DEBUG=false
APP_KEY=base64:$(openssl rand -base64 32)
APP_URL=$APP_URL
LOG_LEVEL=warning
SESSION_SECURE_COOKIE=true

# Caddy gets the HTTPS certificate for this host
SITE_HOST=${APP_URL#*://}

# Set by bin/release.sh on every release; edit only to roll back by hand
APP_TAG=latest

# Database (applied only when the db volume is first created)
DB_PASSWORD=$(openssl rand -hex 24)
DB_ROOT_PASSWORD=$(openssl rand -hex 24)

# Seeds the Section 10 demo data and demo logins (password: "password") into
# an empty database on first start. Set to false for a real deployment.
SEED_DEMO_DATA=true
EOF
    chmod 600 .env
else
    echo "==> Using existing .env"
    if ! grep -q '^COMPOSE_FILE=.*docker-compose.prod.yml' .env; then
        echo ".env is not a production .env (it does not load docker-compose.prod.yml)." >&2
        echo "It was probably copied from a local machine. Move it aside and re-run," >&2
        echo "and a production .env with fresh secrets will be generated:" >&2
        echo "  mv .env .env.local-backup && bash deploy.sh" >&2
        exit 1
    fi
fi

# --- Start ------------------------------------------------------------------
echo "==> Pulling images"
docker compose pull

if [ -n "$(docker compose ps -q app 2>/dev/null)" ]; then
    # Already live: start anything missing, then swap the app container
    echo "==> Stack is running; rolling the app without downtime"
    docker compose up -d --no-recreate --remove-orphans
    docker compose run --rm --no-deps --entrypoint php app artisan migrate --force
    docker rollout --timeout 180 --wait-after-healthy 5 app
else
    echo "==> First start"
    docker compose up -d --remove-orphans --wait
fi
docker image prune -f >/dev/null

docker compose ps
url="$(grep '^APP_URL=' .env | cut -d= -f2-)"
echo
echo "App:    $url"
echo "Health: $url/up"
