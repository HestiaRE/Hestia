# docker.md - rootless Docker per customer

> **The operator's view:** what the per-customer Docker model is, how it is built, what it
> promises and what it does not, and what to tell a customer. The other operator docs are listed
> in `docs/README.md`.

**Living doc.** A change to the companion account, the id mapping, the network, publishing,
limits or the backup scope updates this file in the same PR.

---

## What this is, and what it is not

Every customer can get **their own rootless Docker daemon**. From the customer's side it behaves
like an ordinary Docker install: `docker` and `docker compose` work in their shell, stock images run
unmodified, and a container's files land as the customer on the host. HestiaRE keeps doing what it
does for every other site: the web domain, Let's Encrypt, the proxy in front, and the databases.

Not part of it:

- **Docker for HestiaRE itself.** The panel and its services never run in containers.
- **Suspend.** `h-suspend-user` does not stop a customer's containers.
- **Quota.** Images, containers and volumes are not counted against a disk budget (#211).
- **A container platform.** No orchestration, no registry, no limit on the number of containers;
  the limits below cap resources, not counts.

---

## Switching it on

**On the box**, once:

```
h-add-sys-docker          # docker-ce from Docker's repo, rootful daemon off, rootless support on
h-delete-sys-docker       # refuses while a customer has Docker; FORCE disables it for them first
```

Docker's own apt repository is a deliberate exception to the OS-repo rule: no distribution ships
compose v2 together with the rootless tooling. The rootful daemon is disabled including
`docker.socket`, so socket activation cannot bring it back. The command refuses on cgroup v1.

**Per customer**, in the panel (Edit user, shown once `DOCKER_SYSTEM` is set) or at a shell:

```
h-add-user-docker USER [volumes|full]     # second argument: the backup scope, below
h-delete-user-docker USER                 # removes the companion with its containers, images, volumes
```

Docker needs an **unjailed login shell**; the command refuses a jailed account, whichever way it
is called. Running `h-add-user-docker` again heals a half-finished enable and leaves a healthy one
alone, so it is also the first thing to try when a customer's daemon is down.

---

## The model

### The companion account

| | |
|---|---|
| account | `<user>-docker`, uid one block below the customer, its own group, shell `nologin` |
| home | `/home/<user>/.companion`, 0710 with the customer's group: traverse to the socket, no listing |
| daemon | a systemd user service of the companion, kept alive by linger over logout and reboot |
| socket | `/home/<user>/.companion/docker.sock`, 0660, group = the customer |
| customer shell | `DOCKER_HOST` points there (`/etc/profile.d/hestia-docker.sh`, only when the socket exists) |

The daemon runs as the companion, never as the customer and never as root. The customer reaches
it through the socket's group and nothing else.

### Id mapping

| inside the container | on the host |
|---|---|
| root (0) | `<user>-docker`, the companion |
| 1 - 999 | subordinate ids without a name |
| **1000** | **the customer** |
| 1001 and up | subordinate ids without a name |

A file written by container uid 1000 belongs to the customer on the host, which is why most stock
images work as they are. A file written by container root belongs to the companion: the customer
can read it, but not change it, and not delete what lies in a directory the container created as
root. The way out is a container, which is root over both ids:

```
docker run --rm -v ./data:/data alpine chown -R 1000:1000 /data
```

The three lines per companion in `/etc/subuid` and `/etc/subgid` are **ordered on purpose**:
`newuidmap` hands out the ranges in file order, so moving the customer's line moves the customer to
another container uid. Do not sort or merge them by hand.

### Network

The daemon runs inside rootlesskit with slirp4netns and `--disable-host-loopback`: **a container
cannot reach the host's loopback**, so `127.0.0.1` inside a container is the container itself, and
services that listen on loopback only (MariaDB, PostgreSQL, Redis) are out of reach over TCP.

Every customer gets **one loopback /24** out of `127.20.0.0/16`, recorded as `DOCKER_IP` in their
`user.conf` (its `.1`). That `.1` is the daemon's default bind, for the default bridge and for every
network compose creates, so a published port without an address lands there. A rootless daemon
cannot bind below 1024.

The firewall renders **one output rule per customer /24**: only root, the customer, the companion
and the web server may connect to it. Another customer cannot reach a container's published port,
even though it sits on loopback.

### Publishing on a domain

```
h-add-web-domain-docker USER DOMAIN PORT [OCTET] [TEMPLATE]
h-delete-web-domain-docker USER DOMAIN
```

Or in the panel: Edit web domain, switch Docker on, choose the last octet and the port. The front
(nginx, or apache on an apache-only box) proxies the domain to `127.20.<n>.<octet>:<port>`. There is
no backend vhost and no PHP pool, and Let's Encrypt works as for any domain. `TPL` and `PROXY` stay
in the record, so a web-model switch keeps the Docker choice.

The command refuses a port that a wildcard listener on the host already holds (the container
could never bind it) and a target that a process of someone else holds. A container already on the
target is the normal case: app first, then the domain. Front templates are shipped in
`/usr/local/hestia/share/templates/docker/<front>/`, with no operator directory of their own;
`h-list-web-templates-docker` lists them, and the panel offers a choice once there is more than one.

### Databases

A container reaches the databases created in the panel **through the host's sockets, mounted into
the container**, not over TCP:

| engine | mount | connect to |
|---|---|---|
| MariaDB | `/run/mysqld:/run/mysqld` | socket `/run/mysqld/mysqld.sock` |
| PostgreSQL | `/run/postgresql:/run/postgresql` | `host=/run/postgresql` |

User and password are the ones from the panel (`<user>_<db>`). The server sees the connecting uid
through the socket, and from a container that is the customer, the companion or an id without a
name, never the database user: identity-based login (`peer`) cannot work, so the login is by
password. MariaDB does this
already. **PostgreSQL does it from v0.36 on**: the socket line `local all all` is `scram-sha-256`
instead of `peer` on a fresh install, and the `pgsql-socket-password` update entry converts an
existing box whose line was still the Debian default. A line changed by hand is left alone and
named in the update log.

---

## Limits

The package field `DOCKER_LIMIT` caps the companion's slice, `user-<companion uid>.slice`, where
the daemon and every container of that customer live. Enforced from the outside, so no compose file
can raise it.

| preset | memory | CPU | tasks |
|---|---|---|---|
| `unlimited` (default) | - | - | - |
| `low` | 10% | 50% (half a core) | 512 |
| `medium` | 25% | 100% (one core) | 1024 |
| `high` | 50% | 200% (two cores) | 2048 |

Memory is a share of the box's RAM. The limit is applied by `h-add-user-docker`,
`h-change-user-package` and `h-rebuild-user`. It is independent of the customer's other resource
limits.

---

## Backup and restore

The customer's `DOCKER_BACKUP` decides what the nightly backup keeps of the daemon's data root
(`~/.companion/.local/share/docker`):

| scope | in the backup |
|---|---|
| `volumes` (default) | named volumes only; images and containers are pulled and created again |
| `full` | the whole data root, images included, which can be large |

Nothing in between: a layer store without its index is useless. **Data bind-mounted from the
customer's web domain** (`~/web/<domain>/private/...`) is part of that domain's ordinary backup,
whatever the scope.

A database **inside a container** is backed up as raw files of a running server, which is not a
consistent copy. Use a database from the panel through its socket, which is dumped properly, or
have the app write a dump into `private/` before the nightly run (05:10).

A restore puts the companion back, re-owns its home to the companion's uid on the new box before
the daemon starts, and keeps the customer's /24 when it is free there.

---

## Operations

```
su - USER -c 'docker ps'                                  # as the customer sees it
DOCKER_HOST=unix:///home/USER/.companion/docker.sock docker ps     # the same as root
systemctl --user -M USER-docker@ status docker            # the daemon's unit
systemctl --user -M USER-docker@ restart docker           # restart it; containers with a restart policy come back
journalctl _UID=$(id -u USER-docker)                      # the daemon's log
```

Ask the API whether a daemon lives, never the socket file: a dead dockerd leaves its socket behind.

| symptom | cause |
|---|---|
| `Cannot connect to the Docker daemon` | daemon down: `h-add-user-docker USER` heals it; or the shell started before Docker was on, a new login fixes `DOCKER_HOST` |
| container stays in `created`, bind fails | port held by a wildcard listener on the host, or below 1024 |
| domain answers 502 | nothing listens on `127.20.<n>.<octet>:<port>`: compare `docker ps` with the domain's octet and port |
| `Permission denied` on a mounted directory | files written by container root, see the id mapping |
| database connection refused | socket not mounted, or `localhost` used inside the container |

---

## What to tell a customer

The short version of the customer guide, to pass on as it is:

1. **Ask for Docker to be switched on**, then log in by SSH. `docker` and `docker compose` work
   right away, there is nothing to configure.
2. **Keep an app in your domain's private folder**, never in `public_html`. The compose file and
   its `.env` go into `~/web/<domain>/private/`, the app's data into a folder below it, e.g.
   `private/data/`, mounted into the container. The folder is backed up with the domain.
3. **Publish the port without an address** (`"3000:3000"`): it lands on your own address. Then
   switch the domain to Docker in the panel, with the same port. Let's Encrypt works as usual. A
   second app takes another port, or another address of your range: the panel shows the range when
   you switch a domain to Docker, and the last number goes into the panel with the port.
4. **Use a database from the panel** by mounting its socket into the container:
   `/run/mysqld:/run/mysqld` for MariaDB, `/run/postgresql:/run/postgresql` for PostgreSQL, with
   the database user and password from the panel. `localhost` inside a container is the container.
5. **Container user 1000 is you.** Files the app writes as 1000 belong to you; files written as
   root belong to your Docker account and you cannot change them directly. Run the app as
   `user: "1000:1000"` where the image allows it.
6. **Limits and backup:** memory and CPU may be capped by your package. Your private folder and,
   by default, named volumes are backed up; images and containers are not, they are pulled again.

A compose file along these lines covers most apps:

```yaml
services:
  app:
    image: example/app:1.2
    user: "1000:1000"
    restart: unless-stopped
    ports:
      - "3000:3000"
    env_file: .env
    volumes:
      - ./data:/data
      - /run/mysqld:/run/mysqld
```
