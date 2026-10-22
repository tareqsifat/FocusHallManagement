#!/usr/bin/env bash
# Zero-downtime release of the app to an image tag (the git SHA).
# CI calls it after pushing an image; run it by hand to roll back.
#
#   bash bin/release.sh <git-sha>
set -Eeuo pipefail
cd "$(dirname "$0")/.."

tag="${1:?usage: bin/release.sh <tag>}"

# A manual rollback and a CI release can overlap; both edit .env
exec 9>/tmp/focushall-release.lock
flock 9

previous="$(grep -E '^APP_TAG=' .env | cut -d= -f2- || true)"

set_tag() {
    if grep -qE '^APP_TAG=' .env; then
        sed -i "s|^APP_TAG=.*|APP_TAG=$1|" .env
    else
        printf 'APP_TAG=%s\n' "$1" >> .env
    fi
}

restore() {
    echo "==> Release failed; app stays on ${previous:-latest}" >&2
    set_tag "${previous:-latest}"
}
trap restore ERR

set_tag "$tag"
echo "==> app: ${previous:-latest} -> $tag"

docker compose pull app

# Schema first, with the new code, before it gets traffic. The old container
# keeps serving meanwhile, so migrations must be backward compatible
# (see docs/DEPLOYMENT.md, "Migrations without downtime").
docker compose run --rm --no-deps --entrypoint php app artisan migrate --force

# New container starts beside the old one, must pass its healthcheck, then
# the old one is stopped gracefully; the proxy retries across both meanwhile
docker rollout --timeout 180 --wait-after-healthy 5 app

trap - ERR

# Pick up a proxy config change that came with this commit; a reload drops
# no connections
docker compose exec -T hall-proxy nginx -t -q && docker compose exec -T hall-proxy nginx -s reload

# Images still in use are kept; older rollback targets can be re-pulled from GHCR
docker image prune -af --filter "until=240h" >/dev/null || true
echo "==> app is on $tag"
