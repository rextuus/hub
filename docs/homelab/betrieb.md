# Betrieb

Deployt wird **von Hand in Komodo** (Stack `hub`, kein Auto-Deploy). Stack und Procedure `hub-backup` beschreibt
[deploy/komodo/hub.toml](https://github.com/rextuus/hub/blob/main/deploy/komodo/hub.toml), den vollständigen Ablauf
mit Umzug und Wiederherstellung [docs/deployment.md](https://github.com/rextuus/hub/blob/main/docs/deployment.md).

## Deploy

<div class="hl-steps" markdown>

1. **Lokal fertig machen**
   Migrationen erzeugen und prüfen, `vendor/bin/phpunit` grün, dann `git push` auf `main`.
2. **Komodo → Stack `hub` → Deploy**
   Komodo zieht den Code und schreibt `.env.prod.local` mit den Secrets.
3. **Pre Deploy prüft und sichert**
   Unaufgelöste Platzhalter oder ein falscher `VOLUME_PREFIX` brechen ab; danach folgt ein Dump `…-pre-deploy.sql.gz`.
4. **Build und Start**
   `database` → `migrate` → `app`, dazu `imgproxy` → `web`. Grün erst, wenn alle gesund sind.
5. **Post Deploy lesen**
   `Webserver: {"status":"ok","database":"ok"}` und eine `[OK]`-Zeile der Migrationen.
6. **Smoke-Test**
   wh-company.com öffnen (Bilder erscheinen = imgproxy läuft), `/kalender`, über das Logo anmelden, `/admin`.

</div>

## Variablen

Nur Namen; die Werte liegen als Komodo-Secrets (Settings → Variables) bzw. fest in `hub.toml`.

| Variable | Woher | Hinweis |
|---|---|---|
| `APP_SECRET` | `HUB_APP_SECRET` | nicht ändern (Sitzungen, Remember-me) |
| `MYSQL_PASSWORD`, `MYSQL_ROOT_PASSWORD` | `HUB_MYSQL_PASSWORD`, `HUB_MYSQL_ROOT_PASSWORD` | gehören zum Volume |
| `GEMINI_API_KEY` | `HUB_GEMINI_API_KEY` | Pflicht (BetAI) |
| `IMGPROXY_KEY`, `IMGPROXY_SALT` | `HUB_IMGPROXY_KEY`, `HUB_IMGPROXY_SALT` | je `openssl rand -hex 32`; gelten für `app` und `imgproxy` |
| `SENTRY_DSN`, `MAILER_DSN` | `HUB_SENTRY_DSN`, `HUB_MAILER_DSN` | |
| `DEFAULT_URI` | fest: `https://wh-company.com` | Links in Konsolenbefehlen (z. B. Teilnehmer-Links) |
| `HTTP_BIND`, `HTTP_PORT`, `REVERSE_PROXY_IPS` | fest: `10.0.0.2`, `8197`, `10.0.0.1` | |

## Konsolenbefehle

Im Komodo-Terminal des Containers `app` oder per `sudo docker exec -it hub-app-1 php bin/console …`:

| Befehl | Wofür |
|---|---|
| `app:admin:create-user <email> <passwort>` | Admin anlegen |
| `app:projects:seed` | Einträge der Startseite abgleichen (entfernt die alten Platzhalter) |
| `app:fotokalender:import --year=2022` | Foto-Kalender aus `fotokalender/2022/<monat>/` anlegen (wiederholbar) |
| `app:advent:import <datei.yaml> [--dry-run]` | Adventskalender aus YAML anlegen/aktualisieren, gibt die Teilnehmer-Links aus |
| `app:bet-ai:import-logos --dry-run` | Team-Logos aus `bet_ai/logos/` zuordnen |

Eine YAML-Datei kommt so in den Container: `sudo docker cp kalender.yaml hub-app-1:/tmp/`.

## Jedes Jahr: Adventskalender

<div class="hl-steps" markdown>

1. **Kalender anlegen**
   EasyAdmin → Adventskalender → Neu: Titel, Jahr, Start (Tür 1), Zeitzone. Oder per YAML-Import.
2. **Türen füllen**
   Je Tür eine Frage, Bild, mindestens zwei Antworten, genau eine richtig, dazu Reaktionen (Text, Bild oder YouTube-Link).
3. **Gewinnstufen**
   Ab wie vielen Punkten welcher Gewinn. Nicht erreichte Gewinne erscheinen verwürfelt.
4. **Vorschau prüfen**
   Aktion „Vorschau“ am Kalender: alle Türen offen, nichts wird gespeichert.
5. **Teilnehmer anlegen und Links verschicken**
   EasyAdmin → Teilnehmer; den Link mit dem Kopier-Knopf weitergeben. Landet ein Link in falschen Händen: „Neuer Link“.

</div>

Eine Tür für jemanden zurücksetzen: unter Mehr → Gegebene Antworten den Eintrag löschen.

## Rollback

Wie in `docs/deployment.md`: im Stack einen Commit eintragen und deployen. Migrationen laufen nur vorwärts; nach
Schema-Änderungen zusätzlich den Dump `…-pre-deploy.sql.gz` einspielen. Der Medienordner ist davon unabhängig.
