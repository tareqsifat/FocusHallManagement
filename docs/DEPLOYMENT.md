# Docker and deployment

Everything lives in this one repo:

```
Dockerfile                  app image: PHP 8.3.36 + Apache, built assets, no Composer/Node
docker-compose.yml          local: build + MySQL, http://localhost:8000
docker-compose.prod.yml     server overrides: GHCR image, nginx proxy, Caddy (HTTPS)
docker/                     php.ini, opcache.ini, entrypoint, proxy config, Caddyfile.example
deploy.sh                   one-time server setup (and safe re-run)
bin/release.sh              zero-downtime release or rollback to an image tag
.github/workflows/deploy.yml  test -> build + push to GHCR -> release on the Droplet
```

PHP is pinned to **8.3.36**, the first 8.3 release with the PHP2Shell RCE
fixes (CVE-2026-109618, CVE-2026-109619). CI fails the build if the image
reports anything older.

## Run locally

```sh
docker compose up -d --build     # http://localhost:8000
```

The first start migrates, seeds the Section 10 demo data (log in as any demo
account from the README, password `password`) and generates an `APP_KEY`
that is kept in the storage volume. MySQL is on host port 3307 so it does not
clash with Laragon.

```sh
docker compose logs -f app       # follow the app log
docker compose down              # stop, keep data
docker compose down -v           # stop and delete the database and uploads
```

The local stack runs in production mode (built assets, `APP_DEBUG=false`).
Rebuild to see code changes.

## How a release works

```
            :80/:443
browser ──► caddy (HTTPS) ──► proxy (nginx, never redeployed)
                                 │  resolver 127.0.0.11 valid=1s → "app" = [old, new]
                                 │  proxy_next_upstream error timeout
                                 ├──► app (old)  ← SIGWINCH: finishes in-flight requests
                                 └──► app (new)  ← started beside old, must pass /up
```

1. A push to `main` runs the tests (Pint and Pest), builds the image on GitHub
   and pushes it to `ghcr.io/tareqsifat/focushall` with tags `latest` and the commit SHA.
2. The deploy job connects to the Droplet over SSH, checks out that same commit
   (so compose and proxy changes ship with it) and runs `bin/release.sh <sha>`.
3. `release.sh` writes the tag to `.env`, pulls the image, runs migrations in a
   one-off container, then `docker rollout app` swaps the containers with no
   downtime. If any step fails, `.env` goes back to the previous tag and the old
   container keeps serving.

Pull requests run only the tests.

## One-time server setup

Starting from a fresh Ubuntu 24.04 Droplet. You already have swap, so
`deploy.sh` will not add any.

### 1. Firewall

Use a DigitalOcean **cloud firewall** with inbound **22** (your IP only), **80**
and **443**. Don't rely on `ufw`, because Docker's iptables rules bypass it.

### 2. Clone the repo with a read-only deploy key

```sh
apt-get update && apt-get install -y git
ssh-keygen -t ed25519 -N "" -f /root/.ssh/focushall_repo -C "droplet read-only"
cat /root/.ssh/focushall_repo.pub
```

Add that public key in GitHub → this repo → **Settings → Deploy keys**
(leave "Allow write access" off). Then:

```sh
cat >> /root/.ssh/config <<'EOF'
Host github.com
    IdentityFile /root/.ssh/focushall_repo
    IdentitiesOnly yes
EOF
ssh-keyscan github.com >> /root/.ssh/known_hosts
git clone git@github.com:tareqsifat/FocusHallManagement.git /opt/focushall
```

### 3. Caddyfile

```sh
cd /opt/focushall
cp docker/caddy/Caddyfile.example docker/caddy/Caddyfile
nano docker/caddy/Caddyfile   # keep the domain block, or switch to the IP-only block
```

With no domain yet, use the **IP-only** block. `deploy.sh` sets
`SITE_HOST` to the Droplet IP.

### 4. Log in to GHCR

The first CI run creates the package. It is private by default, so log in
once with a classic PAT that has only the `read:packages` scope:

```sh
echo ghp_xxx | docker login ghcr.io -u tareqsifat --password-stdin
```

Alternatively, make the package public (GitHub → Packages → focushall →
Package settings) and skip this step.

### 5. First start

Push to `main` first (with `DEPLOY_ENABLED` unset) so that an image exists, then:

```sh
bash /opt/focushall/deploy.sh
```

This installs Docker and docker-rollout and writes `/opt/focushall/.env`
with fresh secrets (`APP_KEY`, database passwords). Then it pulls and starts
the stack. The first start migrates and seeds the demo data
(`SEED_DEMO_DATA=true`). Open `https://<droplet-ip>`.

### 6. Deploy user for CI

```sh
adduser --disabled-password --gecos "" deploy
usermod -aG docker deploy          # the docker group is root-equivalent; keep the key in GitHub only
chown -R deploy:deploy /opt/focushall
# the deploy user needs the repo key too, for `git fetch` in the deploy job
install -d -m 700 -o deploy -g deploy /home/deploy/.ssh
cp /root/.ssh/focushall_repo /root/.ssh/config /root/.ssh/known_hosts /home/deploy/.ssh/
sed -i 's|/root/|/home/deploy/|' /home/deploy/.ssh/config
# and GHCR access, if the package is private
cp -r /root/.docker /home/deploy/.docker 2>/dev/null || true
chown -R deploy:deploy /home/deploy/.ssh /home/deploy/.docker 2>/dev/null || true

# CI's SSH key: generate it on your PC, paste the public half here
nano /home/deploy/.ssh/authorized_keys
chown deploy:deploy /home/deploy/.ssh/authorized_keys && chmod 600 /home/deploy/.ssh/authorized_keys
```

### 7. GitHub settings

| Kind | Name | Value |
|---|---|---|
| Secret | `SSH_HOST` | Droplet IP |
| Secret | `SSH_USER` | `deploy` |
| Secret | `SSH_PRIVATE_KEY` | private half of the CI key from step 6 |
| Secret | `SSH_PORT` | only if not 22 |
| Variable | `DEPLOY_ENABLED` | `true` once steps 1 to 6 are done |
| Variable | `APP_DIR` | only if not `/opt/focushall` |
| Environment | `production` | optional: add "required reviewers" for a manual approve button |

## Day to day

| You want to… | Do this | Downtime |
|---|---|---|
| Ship | `git push` to `main` | none |
| Roll back | `bash bin/release.sh <older-sha>` | none |
| See what's live | `grep APP_TAG= .env` and `docker compose ps` | none |
| Change app env (`.env`) | edit `.env`, then `bash bin/release.sh "$(grep ^APP_TAG= .env \| cut -d= -f2)"` | none |
| Change the Caddyfile | `docker compose exec caddy caddy reload --config /etc/caddy/Caddyfile` | none |
| Change `docker-compose*.yml` | push, then `bash deploy.sh` on the server | none for the app |
| Back up the database | see below | none |

```sh
cd /opt/focushall
docker compose exec -T db sh -c 'mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction kjh_hall' \
  | gzip > /root/kjh_hall-$(date +%F-%H%M).sql.gz
```

**Don't run** `docker compose up -d` (with no service name), `docker compose restart app`
or `docker compose down` on the live server. Each of those stops or recreates
the app in place. `docker compose down -v` also **deletes the database and uploads**.

## Migrations without downtime

During a release the **old code runs against the new schema** for a few
seconds. Every migration must keep the old code working:

- Add columns as nullable or with a default. Start using them in the same or a later release.
- Rename or drop a column over two releases: first stop using it, then drop it.
- Never `migrate:fresh`, `migrate:reset` or `db:wipe` in production.
- MySQL DDL is not transactional. Keep one schema change per migration, and back up before risky ones.

## Adding a domain later

Point the domain's A record at the Droplet. In `.env`, set
`APP_URL=https://your-domain` and `SITE_HOST=your-domain`. Switch
`docker/caddy/Caddyfile` to the domain block, then:

```sh
docker compose up -d caddy        # picks up the new SITE_HOST (about 1 s blip)
bash bin/release.sh "$(grep ^APP_TAG= .env | cut -d= -f2)"
```
