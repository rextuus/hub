# Hub betreiben (Komodo auf abyss)

Hub läuft als Komodo-Stack **`hub`** auf **abyss** (Komodo v2.3, Core + Periphery). Öffentlich erreichbar über den
Caddy auf dem VPS, der TLS macht und über WireGuard an `10.0.0.2:8197` weiterleitet.

| | |
|---|---|
| Domain | <https://wh-company.com> (www leitet um; Verwaltung: `/admin`, Anmeldung über das Logo) |
| Repo / Branch | `rextuus/hub` / `main` |
| Stack-Ordner | `/etc/komodo/stacks/hub` (Klon von Komodo, keine Daten darin) |
| Compose | `docker compose -p hub -f compose.prod.yaml --env-file .env.prod.local` |
| Komodo-Ressourcen | `deploy/komodo/hub.toml` (Resource Sync): Stack `hub`, Procedure `hub-backup` |
| Variablen | `.env.prod.dist` (Vorlage), Secrets als Komodo-Variablen `HUB_*` |

## Was auf dem Server läuft

| Dienst | Image | Aufgabe |
|---|---|---|
| `web` | `hub-web:prod` (Caddy 2.11, gebaut) | einziger Host-Port `10.0.0.2:8197`, liefert die Assets aus, leitet `/img/*` an `imgproxy` und alles andere an `app` weiter. Healthcheck `/healthz` (bis zur Datenbank) |
| `imgproxy` | `ghcr.io/imgproxy/imgproxy:v4.0.25` | Bilder in passender Größe aus dem Medienordner (nur lesend), nur mit gültiger Signatur. Kein Host-Port (Baustein `homelab/docs/baustein-imgproxy.md`) |
| `app` | `hub-app:prod` (PHP 8.5 FPM, gebaut) | Symfony. Healthcheck per FPM-Ping |
| `migrate` | wie `app` | läuft bei jedem Deploy einmal `doctrine:migrations:migrate`, beendet sich danach. `app` startet erst, wenn das geklappt hat |
| `database` | `mysql:8.4.13` | Datenbank `hub`, kein Host-Port |
| `backup` | `mysql:8.4.13` | nur auf Abruf (Profil): Dump nach `/srv/hub/backups` (`deploy/komodo/db-backup.sh`) |

Keine Worker, keine Cron-Jobs: Messenger ist zwar eingerichtet (Doctrine-Transport), aber der Code verschickt
nichts asynchron, und Mails gibt es derzeit nicht (`MAILER_DSN=null://null`).

**Persistente Daten** (nie im Stack-Ordner):

| Wo | Was | Gesichert |
|---|---|---|
| Volume `hub_database_data` | MySQL | täglich als Dump (`/srv/hub/backups`) + offsite |
| Volume `hub_media_data` → `/srv/app/var/media` (`app`, schreibend) bzw. `/images` (`imgproxy`, lesend) | gemeinsamer Medienordner: `bet_ai/logos/`, `fotokalender/`, `advent/`, `projekte/` (Aufbau: `docs/homelab/daten.md`) | offsite direkt aus dem Volume |
| Volumes `hub_caddy_data`, `hub_caddy_config` | Caddy-Zustand (ohne TLS unwichtig) | nein |
| `/srv/hub/backups` | Datenbank-Dumps | offsite |

Sitzungen liegen als Dateien im Container und sind nach einem Deploy weg. Wer „Angemeldet bleiben“ gewählt hat,
bleibt angemeldet, solange `APP_SECRET` gleich bleibt.

## Was bei einem Deploy passiert

1. **Git:** Komodo klont bzw. pullt `rextuus/hub` nach `/etc/komodo/stacks/hub`.
2. **Env-Datei:** Komodo schreibt das Environment des Stacks (Secrets eingesetzt) nach `.env.prod.local` (600).
3. **Pre Deploy** (`sh deploy/komodo/pre-deploy.sh`, im Periphery-Container):
   - bricht ab, wenn in der Env-Datei noch ein unaufgelöster Platzhalter steht (Komodo lässt unbekannte Variablen
     wörtlich stehen),
   - bricht ab, wenn Projektname und `VOLUME_PREFIX` nicht zusammenpassen (Schutz vor Probeläufen in echten Daten),
   - sichert die laufende Datenbank (`…-pre-deploy.sql.gz`). Allererster Deploy ohne Datenbank: übersprungen.
   Scheitert der Schritt, ändert Komodo nichts.
4. `compose build`, dann `up -d --wait --wait-timeout 300`: `database` → `migrate` → `app` → `web`. Erfolg erst,
   wenn alles gesund ist.
5. **Post Deploy** (`sh deploy/komodo/post-deploy.sh`): Zustand und `/healthz` ins Log, alte namenlose Images
   **dieses** Projekts löschen.

Beim Bauen holt das Image die JavaScript-Pakete (jsDelivr, `importmap:install`) und dart-sass (GitHub) aus dem
Netz. Fällt einer davon aus, scheitert der Build – die laufende Version bleibt dann unberührt.

## Einrichtung in Komodo (einmalig)

1. **GitHub:** Das Fine-grained Token, das Komodo nutzt (`komodo-abyss`, Account `rextuus`), muss `rextuus/hub`
   lesen dürfen: GitHub → Settings → Developer settings → Fine-grained tokens → `komodo-abyss` → Repository access
   → `rextuus/hub` hinzufügen (Contents: Read-only).
2. **Sicherungsordner** auf abyss (Docker legt ihn absichtlich nicht selbst an):
   ```bash
   sudo install -d -m 700 /srv/hub/backups
   ```
3. **Variablen** unter Settings → Variables, jeweils mit Häkchen „Secret“:

   | Variable | Wert kommt aus |
   |---|---|
   | `HUB_APP_SECRET` | `APP_SECRET` der bisherigen Instanz auf securitas (siehe Umzug A.2) – nicht neu würfeln |
   | `HUB_MYSQL_PASSWORD` | neu: `openssl rand -hex 24` (gilt beim ersten Start des leeren Volumes) |
   | `HUB_MYSQL_ROOT_PASSWORD` | neu: `openssl rand -hex 24` (auch für die Sicherungen) |
   | `HUB_GEMINI_API_KEY` | `GEMINI_API_KEY` von securitas (Pflicht; ohne Wert startet `app` nicht) |
   | `HUB_SENTRY_DSN` | `SENTRY_DSN` von securitas bzw. aus Sentry. Ohne Sentry: Zeile im Stack-Environment löschen |
   | `HUB_MAILER_DSN` | `null://null` (Hub verschickt keine Mails) |
   | `HUB_IMGPROXY_KEY` | neu: `openssl rand -hex 32` (Pflicht; gilt für `app` und `imgproxy`) |
   | `HUB_IMGPROXY_SALT` | neu: `openssl rand -hex 32` (Pflicht) |

   Nicht geheim und direkt in `hub.toml`: `DEFAULT_URI=https://wh-company.com`, `HTTP_BIND=10.0.0.2`,
   `HTTP_PORT=8197`, `REVERSE_PROXY_IPS=10.0.0.1`, `BACKUP_DIR=/srv/hub/backups`, `BACKUP_KEEP=14`,
   `MYSQL_DATABASE=hub`, `MYSQL_USER=hub`. Alle Secrets zusätzlich im Passwortmanager ablegen.
4. **Resource Sync:** Syncs → New Resource Sync, Git-Repo `rextuus/hub` (Account `rextuus`), Branch `main`,
   Resource Path `deploy/komodo/hub.toml`. **„Delete unmatched resources“ aus lassen** (sonst löscht der Sync
   die Stacks der anderen Projekte). Ausführen: legt Stack `hub` und Procedure `hub-backup` an, deployt nichts.
5. **Kontrolle im Stack:** Server `abyss`, Project Name `hub`, File Paths `compose.prod.yaml`, Env File Path
   `.env.prod.local`, Pre/Post Deploy gesetzt, Ignore Services `migrate`, `backup`, Links im Kopf.
   Procedure `hub-backup`: „Every day at 02:15“, Europe/Berlin, Schedule und Failure Alert an.

*Annahmen zu Komodo:* `server = "abyss"` (Name statt ID) und `[[procedure.config.stage]]` sind die Schreibweise
der Sync-Doku (komo.do/docs/automate/sync-resources) und laufen bei Wedora genauso; `links` ist laut
`komodo_client` 2.3 ein Feld von `StackConfig`.

## Umzug von securitas (einmalig)

**Ausgangslage:** Hub läuft auf securitas unter `/var/www/hub` (Apache, PHP-FPM, MySQL 8.0 nativ, Datenbank
`hub_db`, etwa 1 MB). Das DNS von `hub.wh-company.de` (Strato) zeigt direkt auf securitas. **Ziel:** Komodo-Stack
`hub` auf abyss, Daten übernommen, DNS auf den VPS (`217.154.91.97`).

**Datenbank-Version:** MySQL 8.0 → 8.4. Ein logischer Dump aus 8.0 lässt sich direkt in 8.4 einspielen; danach
laufen bei Deploy die Doctrine-Migrationen (`serverVersion=8.4.13`). Geprüft in der Generalprobe: Dump ohne
`CREATE DATABASE` in eine leere Datenbank `hub` eingespielt, Migrationen „up to date“, `/healthz` ok.

**Erwartete Ausfallzeit:** 15–30 Minuten Schreibpause (Dump, Kopieren, Import – die Datenbank ist klein) plus die
DNS-Umstellung. Während der DNS-Umstellung (TTL bei Strato, meist bis zu einer Stunde) landen manche Besucher noch
auf securitas; dort ist Hub dann schon abgeschaltet (Schritt B.1). Ruhiges Zeitfenster wählen, nicht während einer
ESC-Abstimmung.

### A. Vorbereitung (ohne Ausfall)

1. **securitas – Bestand prüfen:**
   ```bash
   apache2ctl -S 2>/dev/null | grep -i hub                 # Name der vHost-Datei merken
   ls -la /var/www/hub/.env.local* 2>/dev/null; grep -c . /var/www/hub/.env.local.php 2>/dev/null
   du -sh /var/www/hub/public/assets/bet_ai/logos           # Team-Logos (fehlen sie, gibt es nichts zu kopieren)
   crontab -l; sudo crontab -l; ls /etc/supervisor/conf.d/ 2>/dev/null | grep -i hub   # Altlasten mit /var/www/hub?
   ```
   Gibt es Cron-/Supervisor-Einträge für Hub, notieren – im Code ist keiner vorgesehen; gegebenenfalls als eigener
   Dienst nachrüsten, bevor umgestellt wird.
2. **securitas – Werte holen** (für die Komodo-Variablen): `APP_SECRET`, `GEMINI_API_KEY`, `SENTRY_DSN` aus
   `/var/www/hub/.env.local` bzw. `.env.local.php` (oder `.env.prod.local`). Den Datenbank-Benutzer brauchst du nicht,
   abyss bekommt neue Passwörter.
3. **abyss:** Einrichtung in Komodo (Abschnitt oben) vollständig durchführen.
4. **abyss – Probestart mit leerer Datenbank:** Stack `hub` → **Deploy**. Im Log: „Noch keine Datenbank vorhanden –
   Sicherung übersprungen“, Build, `migrate` „Exited“, alles „Healthy“, Post Deploy `{"status":"ok",…}`.
   Vom VPS: `curl -s http://10.0.0.2:8197/healthz`. Die leere Instanz ist so vorab geprüft; das DNS zeigt weiter
   auf securitas. Danach **Procedure `hub-backup` → Run**: `sudo ls -l /srv/hub/backups` zeigt
   `hub-db-…-daily.sql.gz` mit `-rw-------`.
5. **homelab:** Eintrag `hub` in `inventory.yaml` zunächst mit `status: migrating`, `ingress: legacy`,
   `legacy_host: securitas` (siehe
   „Eintrag im Repo homelab“), `bin/inventory`, `bin/check`.

### B. Umstellung (Schreibpause)

1. **securitas – Hub abschalten** (Rückweg bleibt offen, nichts wird gelöscht):
   ```bash
   sudo a2dissite <hub-vhost> && sudo systemctl reload apache2      # Name aus A.1
   ```
2. **securitas – Daten sichern:**
   ```bash
   cd ~ && umask 077
   sudo mysqldump --single-transaction --quick --routines --triggers --events --hex-blob \
     --default-character-set=utf8mb4 --set-gtid-purged=OFF --no-tablespaces hub_db | gzip > hub_db.sql.gz
   gunzip -c hub_db.sql.gz | tail -n 1                                 # muss „-- Dump completed …“ zeigen
   sudo tar -C /var/www/hub/public/assets/bet_ai/logos -czf hub-logos.tgz .   # nur falls A.1 Logos gefunden hat
   sudo chown "$USER" hub_db.sql.gz hub-logos.tgz
   ```
   Wichtig: **ohne** `--databases` – der Dump soll in die Datenbank `hub` (nicht `hub_db`) gehen.
3. **Übertragen** (securitas hängt noch nicht im WireGuard, also über den Arbeitsrechner):
   ```bash
   scp securitas:hub_db.sql.gz securitas:hub-logos.tgz /tmp/
   scp /tmp/hub_db.sql.gz /tmp/hub-logos.tgz abyss:/tmp/
   ```
4. **abyss – Datenbank ersetzen** (die leere Probedatenbank aus A.4):
   ```bash
   cd /etc/komodo/stacks/hub
   DC="sudo docker compose -p hub -f compose.prod.yaml --env-file .env.prod.local"
   $DC stop app web
   $DC exec -T database sh -c 'MYSQL_PWD=$MYSQL_ROOT_PASSWORD mysql -u root' <<'SQL'
   DROP DATABASE hub;
   CREATE DATABASE hub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   GRANT ALL ON hub.* TO 'hub'@'%';
   SQL
   gunzip -c /tmp/hub_db.sql.gz | $DC exec -T database sh -c 'MYSQL_PWD=$MYSQL_ROOT_PASSWORD mysql -u root hub'
   ```
5. **abyss – Logos ins Volume** (nur falls vorhanden; `82` = www-data im PHP-Image):
   ```bash
   sudo docker run --rm -v hub_media_data:/m -v /tmp/hub-logos.tgz:/l.tgz:ro alpine:3.22 \
     sh -c 'tar -C /m -xzf /l.tgz && chown -R 82:82 /m && find /m -type f | wc -l'
   ```
6. **Komodo:** Stack `hub` → **Deploy** (Pre Deploy sichert schon die übernommenen Daten, `migrate` holt das Schema
   auf den Stand von `main`).
7. **Caddy und DNS** (Repo homelab, Arbeitsrechner): Eintrag auf `status: live-komodo`, `ingress: caddy` umstellen,
   dann `bin/inventory`, `bin/gen-docs`, `bin/gen-caddy`, `bin/gen-offsite`, `bin/caddy-deploy` (Trockenlauf),
   `bin/caddy-deploy --apply`. **Danach** bei Strato den A-Record `hub.wh-company.de` auf `217.154.91.97` setzen
   (Caddy holt das Zertifikat, sobald das DNS stimmt). Zum Schluss `bin/check`.
8. **Aufräumen:** `rm /tmp/hub_db.sql.gz /tmp/hub-logos.tgz` auf abyss und dem Arbeitsrechner (oder verschlüsselt
   ablegen).

### C. Smoke-Test

```bash
curl -s http://10.0.0.2:8197/healthz                     # vom VPS: {"status":"ok","database":"ok"}
curl -sI https://wh-company.com/ | head -1               # HTTP/2 200 (nach DNS-Umstellung)
curl -sI https://wh-company.com/admin | grep -i location    # → /login
```

- Im Browser anmelden (bestehendes Konto), `/admin` öffnen, Benutzerliste und eine ESC-Edition ansehen.
- BetAI öffnen: Team-Logos erscheinen (prüft `hub_media_data`), eine Spielwoche öffnen.
- Session-Cookie ist `Secure` (Entwicklertools) – Symfony erkennt HTTPS hinter dem Proxy.
- Komodo: Stack „Running“; `sudo docker volume ls --filter name=hub_` zeigt `hub_database_data`,
  `hub_media_data`, `hub_caddy_data`, `hub_caddy_config`.

### Rückfallebene

Solange auf securitas nichts gelöscht ist:

1. DNS `hub.wh-company.de` zurück auf securitas (bzw. die alte Adresse aus der Strato-Verwaltung).
2. `sudo a2ensite <hub-vhost> && sudo systemctl reload apache2` auf securitas.
3. Im Repo homelab den Eintrag zurück auf `status: migrating`, `ingress: legacy`, `bin/gen-caddy`,
   `bin/caddy-deploy --apply`.

Was auf abyss nach dem Umzug geschrieben wurde, fehlt dann auf securitas (bei Bedarf per Dump zurückholen).
Nach einigen ruhigen Tagen: vHost, Zertifikat und `/var/www/hub` auf securitas entfernen, die Datenbank `hub_db`
zunächst stehen lassen – wie bei Debes und Optatio (`homelab/docs/securitas.md`).

## Deploy im Alltag

Committen, `git push`, dann in Komodo Stack `hub` → **Deploy**. Kein Auto-Deploy bei Push.

Konsolenbefehle (z. B. Admin anlegen, Logos zuordnen) im Komodo-Terminal des Containers `app` oder:

```bash
sudo docker exec -it hub-app-1 php bin/console app:admin:create-user <email> <passwort>
sudo docker exec -it hub-app-1 php bin/console app:bet-ai:import-logos --dry-run
```

Neue Logos landen per `docker cp <ordner> hub-app-1:/srv/app/var/media/bet_ai/logos/` im Volume. Bilder für
Fotokalender, Adventskalender und Startseite lädt man in EasyAdmin hoch.

## Umstellung auf wh-company.com, Medienordner und imgproxy (einmalig, Stand 26.11.2026)

Mit diesem Stand zieht die Startseite von `wh-company.de` (securitas) zu hub, `hub.wh-company.de` fällt weg, das
Volume `hub_media_data` wird zum gemeinsamen Medienordner, und `imgproxy` kommt dazu. Reihenfolge:

1. **Komodo-Variablen** `HUB_IMGPROXY_KEY` und `HUB_IMGPROXY_SALT` anlegen (je `openssl rand -hex 32`, Secret).
   Ohne sie bricht der Deploy ab, ohne etwas zu ändern.
2. **Resource Sync** ausführen (neue Links, `DEFAULT_URI`, Variablen aus `hub.toml`).
3. **abyss – Logos im Volume umziehen** (heute liegen sie flach im Volume; danach unter `bet_ai/logos/`).
   Direkt vor dem Deploy ausführen, dazwischen fehlen der alten Version die Logos. Der Befehl ist wiederholbar:
   ```bash
   sudo docker run --rm -v hub_media_data:/m alpine:3.22 sh -c '
     set -e; cd /m
     if [ -d bet_ai/logos ]; then echo "bet_ai/logos gibt es schon – nichts zu tun"; exit 0; fi
     mkdir -p bet_ai/logos
     for f in *; do [ -e "$f" ] || continue; [ "$f" = bet_ai ] && continue; mv "$f" bet_ai/logos/; done
     chown -R 82:82 /m
     echo "Dateien unter bet_ai/logos: $(find bet_ai/logos -type f | wc -l)"'
   ```
4. **Komodo → Deploy.** Die Migration `Version20261126120000` schreibt die Logo-Pfade in der DB von
   `/assets/bet_ai/logos/…` auf `/media/bet_ai/logos/…` um; die weiteren legen Fotokalender und Adventskalender an.
5. **Fotos und Startseiten-Bilder ins Volume** (vom Arbeitsrechner, etwa 43 MB; der Ordner liegt nicht im Repo):
   ```bash
   tar -C ~/Documents/programming/hub-altbestand/media -cz fotokalender projekte \
     | ssh abyss 'sudo docker run --rm -i -v hub_media_data:/m alpine:3.22 sh -c "tar -C /m -xz && chown -R 82:82 /m/fotokalender /m/projekte && du -sh /m/*"'
   ```
6. **Inhalte anlegen:**
   ```bash
   sudo docker exec hub-app-1 php bin/console app:projects:seed
   sudo docker exec hub-app-1 php bin/console app:fotokalender:import --year=2022
   ```
7. **homelab:** Domains und Weiterleitung umstellen (Eintrag `hub` im Inventar: `wh-company.com`,
   `www.wh-company.com` mit `caddy.redirects` auf `https://wh-company.com{uri}`, `hub.wh-company.de` unter
   `former_domains`), DNS bei Strato für `wh-company.com` und `www` auf den VPS. Dann `bin/inventory`, `bin/gen-docs`,
   `bin/gen-caddy`, `bin/caddy-deploy` (Trockenlauf), `bin/caddy-deploy --apply`, `bin/check`.
8. **securitas – alte Adressen umleiten** (Apache, vHosts von `wh-company.de`/`www.wh-company.de`). Nextcloud unter
   `https://wh-company.de/nextcloud` bleibt, wie es ist:
   ```apache
   RedirectMatch 301 ^/calendar/(.*)$ https://wh-company.com/calendar/$1
   RedirectMatch 301 ^/$ https://wh-company.com/
   ```
   hub leitet `/calendar/overview.html` auf `/kalender` und `/calendar/detail/<month>.html` auf
   `/kalender/2022/<monat>` weiter.

**Prüfen:** `curl -sI https://wh-company.com/ | head -1` → 200; ein Bild auf `/kalender` öffnen (`/img/…` 200), dieselbe
URL mit verändertem Zeichen in der Signatur → 403; BetAI zeigt die Team-Logos; `/healthz` ok.

## Rollback

- **Code:** Im Stack unter **Commit** den letzten guten Hash eintragen → **Deploy**, danach das Feld wieder leeren.
  (Ein Resource Sync setzt `commit` aus `hub.toml` zurück.)
- **Migrationen** laufen nur vorwärts: Hat die neue Version das Schema geändert, zusätzlich den Dump vom Deploy
  (`hub-db-…-pre-deploy.sql.gz`) einspielen (siehe Wiederherstellen).
- **Scheitert `migrate`**, ist die alte `app` schon ersetzt und die neue startet nicht: **Seite unten, bis du
  handelst.** Log von `migrate` lesen, beheben und neu deployen oder zurückrollen (Commit + Dump).

## Sicherung

| Wann | Wer | Datei in `/srv/hub/backups` | Bleiben |
|---|---|---|---|
| täglich 02:15 (Europe/Berlin) | Procedure `hub-backup` (`RunStackService hub → backup daily`, `no_deps`) | `hub-db-<UTC>-daily.sql.gz` | 14 |
| vor jedem Deploy | `pre-deploy.sh` | `hub-db-<UTC>-pre-deploy.sql.gz` | 14 |
| von Hand | du | `hub-db-<UTC>-manuell.sql.gz` | 14 |

- Rotation je Anlass getrennt. Ein Dump wird erst unter seinem Namen abgelegt, wenn er vollständig ist
  (Abschlusszeile, `CREATE TABLE`, `gzip -t`); Dateien 600, Ordner 700, `umask 077`.
- Läuft der Stack nicht, scheitert die tägliche Sicherung absichtlich (Exit ≠ 0, Komodo alarmiert) und startet nichts.
- Von Hand: Procedure `hub-backup` → Run, oder
  ```bash
  cd /etc/komodo/stacks/hub
  sudo docker compose -p hub -f compose.prod.yaml --env-file .env.prod.local run --rm --no-deps backup manuell
  ```
- **Außer Haus:** Die nächtliche Offsite-Sicherung von abyss (restic → Backblaze B2, 05:15, Stack `offsite-abyss`)
  nimmt `/srv/hub/backups` und das Logo-Volume (`backup.offsite_paths` im Inventar) mit. Nichts im Projekt nötig.

## Wiederherstellen

**Datenbank aus einem lokalen Dump** (geprüft in der Generalprobe: Datenbank gelöscht, eingespielt, gesund):

```bash
cd /etc/komodo/stacks/hub
DC="sudo docker compose -p hub -f compose.prod.yaml --env-file .env.prod.local"
sudo ls -1 /srv/hub/backups
$DC stop app web
$DC run --rm --no-deps -T --entrypoint sh backup -c 'gunzip -c /backups/hub-db-<zeit>-<anlass>.sql.gz | mysql -h database -u root'
```

Danach in Komodo **Deploy**. Der Dump enthält `CREATE DATABASE IF NOT EXISTS` und ersetzt alle Tabellen.

**Medienordner** zurück ins Volume: aus einem tar (`docs/homelab/daten.md`) bzw. Dateien nach
`/var/lib/docker/volumes/hub_media_data/_data/` kopieren und `chown -R 82:82`.

**Aus der Offsite-Sicherung** (Dump oder Logos aus restic/B2 holen): `homelab/docs/offsite.md`; danach wie oben.

## Fehlersuche

- **Deploy-Log in Komodo** (Stack → Updates): Pre Deploy rot = Variablen/Sicherung, nichts verändert. Compose
  Up rot = Build, `migrate` oder ein Dienst wurde nicht gesund.
- **„In Komodo fehlen diese Variablen/Secrets“:** Variable unter Settings → Variables anlegen (Schreibweise) oder
  Zeile aus dem Environment löschen.
- **„bind source path does not exist: /srv/hub/backups“:** Ordner fehlt (`sudo install -d -m 700 /srv/hub/backups`).
- **„VOLUME_PREFIX ist gesetzt …“ / „Projekt … ohne VOLUME_PREFIX …“:** In Produktion Projekt `hub` ohne
  `VOLUME_PREFIX`.
- **`app` startet nicht, „Environment variable not found: GEMINI_API_KEY“** o. ä.: Secret fehlt oder ist leer.
- **Build scheitert bei `importmap:install` / `sass:build`:** Netz bzw. jsDelivr/GitHub nicht erreichbar – später
  erneut deployen; die alte Version läuft weiter.
- **Login schlägt fehl / Redirects auf `http://`:** Kommt die Anfrage nicht von `10.0.0.1` (`REVERSE_PROXY_IPS`),
  ignoriert Caddy `X-Forwarded-Proto`, und der CSRF-Schutz lehnt den Login wegen des Origins ab. VPS-Weg prüfen.
- **Logos fehlen:** Volume noch nicht umgezogen (siehe „Umstellung“) oder Pfad in der DB passt nicht –
  `sudo docker exec hub-app-1 ls /srv/app/var/media/bet_ai/logos`.
- **Bilder fehlen, `/img/…` 403:** `IMGPROXY_KEY`/`IMGPROXY_SALT` fehlen oder unterscheiden sich; weitere Fälle in
  `docs/homelab/probleme.md`.
- **Stack „Unhealthy“:** `migrate` und `backup` müssen unter Ignore Services stehen.
- **Ein Dienst wird nicht gesund:** `sudo docker inspect --format '{{json .State.Health}}' hub-<dienst>-1`.
- **Logs:** `sudo docker logs -f hub-app-1` (Symfony schreibt in prod nach stderr, JSON).

## Generalprobe auf dem eigenen Rechner

Die Entwicklungsumgebung im Ordner `hub` legt ebenfalls `hub_database_data` an. Probeläufe deshalb **immer** mit
eigenem Projekt und `VOLUME_PREFIX`:

```bash
cp .env.prod.dist .env.prod.local     # Werte ausfüllen; HTTP_BIND=127.0.0.1, HTTP_PORT=18197,
                                      # BACKUP_DIR=<vorhandener Ordner>, VOLUME_PREFIX=hubprobe
docker compose -p hubprobe -f compose.prod.yaml --env-file .env.prod.local up -d --build --wait
curl -s http://127.0.0.1:18197/healthz
COMPOSE_PROJECT=hubprobe sh deploy/komodo/pre-deploy.sh
# realistisch wie Komodo: im Periphery-Image, Repo unter gleichem Pfad
docker run --rm -v /var/run/docker.sock:/var/run/docker.sock -v "$PWD:$PWD" -w "$PWD" -e COMPOSE_PROJECT=hubprobe \
  --entrypoint sh ghcr.io/moghtech/komodo-periphery:2 -c 'sh deploy/komodo/pre-deploy.sh && sh deploy/komodo/post-deploy.sh'
docker compose -p hubprobe -f compose.prod.yaml --env-file .env.prod.local down -v   # löscht nur hubprobe_*
rm .env.prod.local
```

## Eintrag im Repo homelab

Nicht hier, sondern in `~/Documents/programming/homelab/inventory.yaml` (Abschnitt `projects:`). Endzustand nach
dem Umzug (während der Vorbereitung: `status: migrating`, `ingress: legacy`, zusätzlich `legacy_host: securitas`):

```yaml
  hub:
    description: Sammlung kleiner Tools, die eine gemeinsame User-Anmeldung und DB bieten
    repo: rextuus/hub
    branch: main
    host: abyss
    komodo_stack: hub
    compose_project: hub
    status: live-komodo
    ingress: caddy
    domains:
      - wh-company.com          # erste Domain = Monitor
      - www.wh-company.com
    former_domains:
      - hub.wh-company.de
    monitor: { path: /healthz }
    port: 8197
    bind: 10.0.0.2
    caddy:
      comment: hub (WH-Company)
      redirects:
        www.wh-company.com: https://wh-company.com{uri}
    data:
      - Named Volume hub_database_data
      - Named Volume hub_media_data (Medienordner: Team-Logos, Fotokalender, Adventskalender, Startseite)
      - /srv/hub/backups
    backup:
      time: "02:15"
      what: DB
      target: /srv/hub/backups
      active: true
      offsite_paths:
        - /var/lib/docker/volumes/hub_media_data/_data # Medienordner (Originale), nicht im Dump
      notes: Komodo-Procedure hub-backup, täglich 02:15; dazu ein Dump vor jedem Deploy. Je Anlass bleiben 14.
    notes: >-
      Von securitas umgezogen (/var/www/hub, MySQL 8.0 hub_db → 8.4). Seit 11/2026 Startseite von WH-Company
      (wh-company.com, vorher statisch auf securitas) und imgproxy im Stack. Kein Worker, keine Mails.
```

Danach: `bin/inventory`, `bin/gen-docs`, `bin/gen-caddy`, `bin/gen-offsite`, `bin/caddy-deploy` (Trockenlauf),
`bin/caddy-deploy --apply`, `bin/check`. In `docs/securitas.md` den Hub-Eintrag nachziehen, sobald die alte
Instanz abgeschaltet ist.
