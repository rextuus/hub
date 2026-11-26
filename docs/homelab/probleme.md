# Probleme

| Symptom | Ursache | Abhilfe |
|---|---|---|
| Keine Bilder, `/img/…` liefert 403 | `IMGPROXY_KEY`/`IMGPROXY_SALT` in `app` und `imgproxy` verschieden, oder geändert, während der Browser alte Seiten zeigt | Beide Dienste bekommen dieselben Komodo-Variablen; nach einer Änderung neu deployen und neu laden |
| `/img/…` liefert 404 | Original fehlt im Medienordner | `sudo docker exec hub-app-1 ls var/media/<ordner>`; Datei zurückholen ([Daten](daten.md)) |
| `/img/…` liefert 502 | imgproxy läuft nicht | `sudo docker logs hub-imgproxy-1`; `web` startet erst, wenn imgproxy gesund ist |
| Deploy bricht ab: „IMGPROXY_KEY fehlt“ | Komodo-Variable fehlt | `HUB_IMGPROXY_KEY` und `HUB_IMGPROXY_SALT` anlegen (Secret) |
| Team-Logos fehlen in BetAI | Dateien liegen noch flach im Volume statt unter `bet_ai/logos/` | Umzugsbefehl aus `docs/deployment.md` → „Medienordner“ |
| wh-company.com zeigt 404 auf `/` | Host nicht in `app.main_host_pattern` (z. B. Aufruf per IP) | Über die Domain aufrufen; neue Hauptdomain in `config/services.yaml` eintragen |
| Upload scheitert („zu groß“) | über 20 MB je Datei bzw. 100 MB je Formular | Bild verkleinern; Grenzen in `docker/php/conf.d/app.ini` und `docker/caddy/Caddyfile` |
| Teilnehmer: „Diese Tür ist noch zu“ obwohl Tag erreicht | Startdatum oder Zeitzone des Kalenders falsch | Im Admin prüfen; Tür n = Start + (n − 1) Tage, 0 Uhr Ortszeit |
| Teilnehmer-Link führt zu 404 | Link neu erzeugt („Neuer Link“) oder Tippfehler | Aktuellen Link aus EasyAdmin → Teilnehmer kopieren |
| Antwort einer Tür soll zurückgesetzt werden | | Mehr → Gegebene Antworten → Eintrag löschen |
| Alte Adresse `wh-company.de/calendar/…` geht ins Leere | Weiterleitung auf securitas fehlt | Apache: `RedirectMatch 301 ^/calendar/(.*)$ https://wh-company.com/calendar/$1`; hub leitet weiter auf `/kalender…` |
