# Daten

| Wo | Was | Gesichert |
|---|---|---|
| Volume `hub_database_data` | MySQL: Benutzer, Projekte, BetAI, ESC, Fotokalender, Adventskalender (Fortschritt der Teilnehmer) | täglich 02:15 als Dump nach `/srv/hub/backups` + vor jedem Deploy, offsite |
| Volume `hub_media_data` (in `app` unter `/srv/app/var/media`, in `imgproxy` unter `/images`, nur lesend) | Originale aller Bilder und Videos | offsite direkt aus dem Volume |
| `/srv/hub/backups` | Datenbank-Dumps (je Anlass 14) | offsite |
| Volumes `hub_caddy_data`, `hub_caddy_config` | Caddy-Zustand (ohne TLS unwichtig) | nein |

Was imgproxy rechnet, wird nirgends gespeichert (nur im Browser-Cache) und ist jederzeit ersetzbar.

## Medienordner

```
hub_media_data/
├── bet_ai/logos/<land>/<liga>/<team>/<team>.png   Team-Logos (DB: /media/bet_ai/logos/…)
├── fotokalender/2022/<monat>/titel.jpg, dsc_….jpg  Foto-Kalender (Import)
├── fotokalender/<jahr>/<monat>/<zufall>.jpg        Fotos per Admin-Upload
├── advent/<zufall>.jpg                             Bilder zu Fragen und Antworten
└── projekte/<datei>                                Startseite (Bilder, about.mp4)
```

- Dateien gehören `82:82` (www-data im PHP-Image); imgproxy läuft als `65534:82` und liest über die Gruppe.
- Pfade in der Datenbank sind relativ zum Ordner (`advent/3f9c….jpg`); nur BetAI speichert `/media/bet_ai/logos/…`.
- Neue Uploads bekommen zufällige Namen. Wird ein Bild im Admin ersetzt, löscht EasyAdmin die alte Datei.

## Wiederherstellen

**Datenbank:** siehe `docs/deployment.md` → „Wiederherstellen“ (Dump einspielen, danach Deploy).

**Medienordner** aus einem tar oder aus restic/B2 (`homelab/docs/offsite.md`) zurück ins Volume:

```bash
sudo docker run --rm -v hub_media_data:/m -v /tmp/medien.tgz:/t.tgz:ro alpine:3.22 \
  sh -c 'tar -C /m -xzf /t.tgz && chown -R 82:82 /m && du -sh /m/*'
```

Fehlen Dateien, zeigt die Seite an der Stelle kein Bild; die Datensätze bleiben gültig.
