# Docker and deployment

Everything lives in this one repo:

```
Dockerfile                    app image: PHP 8.3.36 + Apache, built assets, no Composer/Node
docker-compose.yml            local: build + MySQL, http://localhost:8000
docker-compose.prod.yml       server: GHCR image, MySQL, hall-proxy (nginx), Caddy (HTTPS)
docker/                       php.ini, opcache.ini, Apache worker cap, entrypoint, proxy, Caddyfile
deploy.sh                     one-time server setup (safe to re-run)
bin/release.sh                zero-downtime release or rollback to an image tag
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
clash with a local MySQL.

```sh
docker compose logs -f app       # follow the app log
docker compose down              # stop, keep data
docker compose down -v           # stop and delete the database and uploads
```

The local stack runs in production mode (built assets, `APP_DEBUG=false`).
Rebuild to see code changes.

## Production layout

```
https://hall.<ip-with-dashes>.sslip.io  (or your domain)
        │
        ▼
      caddy (owns :80/:443, Let's Encrypt)
        │
        ▼
      hall-proxy (nginx, never redeployed)
        │  resolver 127.0.0.11 valid=1s → "app" = [old, new] during a release
        ▼
      app (PHP 8.3.36 + Apache, rolled)  ──►  db (MySQL 8.4)
```

- [sslip.io](https://sslip.io) resolves `hall.1-2-3-4.sslip.io` to `1.2.3.4`,
  so the site gets a hostname and a real certificate before you have a domain.
- Apache runs at most 4 PHP processes (`APACHE_MAX_WORKERS` in `.env`). MySQL
  starts with small-server settings (no performance schema, 128 MB buffer
  pool). Both fit a 1 GB Droplet. On a bigger one, raise `APACHE_MAX_WORKERS`.

## How a release works

1. A push to `main` runs the tests (Pint and Pest), builds the image on GitHub
   and pushes it to `ghcr.io/tareqsifat/focushall` with tags `latest` and the commit SHA.
2. The deploy job connects to the Droplet over SSH, checks out that same commit
   (so compose and proxy changes ship with it) and runs `bin/release.sh <sha>`.
3. `release.sh` writes the tag to `.env`, pulls the image, runs migrations in a
   one-off container, then `docker rollout app` swaps the containers with no
   downtime. If any step fails, `.env` goes back to the previous tag and the old
   container keeps serving.

Pull requests run only the tests.

## One-time setup

### 1. Push once to build the image

Push to `main` with `DEPLOY_ENABLED` unset. The test and build jobs run, the
image appears under GitHub → your profile → **Packages → focushall**, and the
deploy job is skipped.

Make the package **public** (Package settings → Change visibility). The image
holds only the app code, which is in this repo anyway, and no secrets.
The server can then pull without logging in. If you keep it private, see
step 4.

### 2. DigitalOcean firewall

Create a **cloud firewall** for the Droplet. Allow inbound **22** (your IP
only, if you can), **80** and **443**. Don't rely on `ufw`, because Docker's
iptables rules bypass it.

### 3. Clone the repo on the Droplet

As root:

```sh
apt-get update && apt-get install -y git
```

**Public repo:**

```sh
git clone https://github.com/tareqsifat/FocusHallManagement.git /opt/focushall
```

**Private repo:** use a read-only deploy key.

```sh
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

### 4. First start

```sh
bash /opt/focushall/deploy.sh
```

This installs Docker and docker-rollout, adds 2 GB of swap if there is none,
and writes `/opt/focushall/.env` with fresh secrets (`APP_KEY`, database
passwords). Then it pulls and starts the stack. The first start migrates and
seeds the demo data (`SEED_DEMO_DATA=true`). Caddy fetches the certificate
within seconds. Open the URL it prints.

If the package is private, log in first, with a classic PAT that has only the
`read:packages` scope:

```sh
echo ghp_xxx | docker login ghcr.io -u tareqsifat --password-stdin
```

### 5. Deploy user for CI

```sh
adduser --disabled-password --gecos "" deploy
usermod -aG docker deploy          # the docker group is root-equivalent; keep the key in GitHub only
chown -R deploy:deploy /opt/focushall
install -d -m 700 -o deploy -g deploy /home/deploy/.ssh
```

On your PC, make a key pair just for CI:

```sh
ssh-keygen -t ed25519 -N "" -f focushall_ci -C "github-actions"
```

Put the contents of `focushall_ci.pub` into `/home/deploy/.ssh/authorized_keys`
on the Droplet. The private file `focushall_ci` becomes the `SSH_PRIVATE_KEY`
secret (step 6).

```sh
chown deploy:deploy /home/deploy/.ssh/authorized_keys && chmod 600 /home/deploy/.ssh/authorized_keys
```

**Private repo only:** the deploy job runs `git fetch` as `deploy`, so give
it the repo key too:

```sh
cp /root/.ssh/focushall_repo /root/.ssh/config /home/deploy/.ssh/
sed -i 's|/root/|/home/deploy/|' /home/deploy/.ssh/config
ssh-keyscan github.com >> /home/deploy/.ssh/known_hosts
chown -R deploy:deploy /home/deploy/.ssh && chmod 600 /home/deploy/.ssh/*
```

**Private package only:** repeat the `docker login` from step 4 as `deploy`
(`sudo -iu deploy`).

Check that it works without a password prompt:

```sh
sudo -iu deploy sh -c 'cd /opt/focushall && git fetch origin main && docker compose ps'
```

### 6. GitHub settings

In the repo: **Settings → Secrets and variables → Actions**.

| Kind | Name | Value |
|---|---|---|
| Secret | `SSH_HOST` | the Droplet's public IP |
| Secret | `SSH_USER` | `deploy` |
| Secret | `SSH_PRIVATE_KEY` | contents of `focushall_ci` (the private key from step 5), including the `BEGIN`/`END` lines |
| Secret | `SSH_PORT` | only if SSH is not on 22 |
| Variable | `DEPLOY_ENABLED` | `true` (turns on the deploy job) |
| Variable | `APP_DIR` | only if the checkout is not `/opt/focushall` |

Pushing to GHCR needs no secret, because the workflow uses the built-in
`GITHUB_TOKEN`. Optional: create an environment named `production`
(**Settings → Environments**) with "required reviewers" to get a manual
approve button before each deploy.

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
docker compose exec -T db sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysqldump -uroot --single-transaction kjh_hall' \
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

Point the domain's A record at the Droplet. Then, in `/opt/focushall`:

```sh
sed -i 's|^APP_URL=.*|APP_URL=https://your-domain|; s|^SITE_HOST=.*|SITE_HOST=your-domain|' .env
docker compose up -d caddy        # picks up the new SITE_HOST (about 1 s blip)
bash bin/release.sh "$(grep ^APP_TAG= .env | cut -d= -f2)"
```
