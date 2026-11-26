# Hub – WH-Company

<div class="hl-showcase" data-device="browser" data-url="wh-company.com" markdown="0">
<p class="hl-showcase__claim">Die <em>Zentrale</em> von WH-Company – Startseite, Fotokalender und <em>Adventskalender</em> in einer App.</p>
<p class="hl-showcase__lead">Eine schlichte Startseite führt zu Debes, Nimbus und Secret Santa. Dahinter laufen kleine Tools mit gemeinsamer Anmeldung, gemeinsamer Datenbank und einem gemeinsamen Medienordner.</p>
<ol>
<li data-title="Startseite" data-icon="image" data-badges="schwarz-weiß|farbig beim Zeigen|fürs Handy">
<p>Vier Streifen wie auf der alten wh-company.de: grau, bis man darauf zeigt. „About“ ist ein kurzes Video. Alle weiteren Projekte liegen hinter „Alle Projekte“, die Anmeldung hinter dem Logo.</p>
<img src="bilder/showcase/startseite.webp" alt="Startseite: schwarze Leiste mit orangem Säulen-Logo, daneben vier Bildstreifen, nur Debes farbig">
</li>
<li data-title="Foto-Kalender" data-icon="calendar" data-badges="9 Monate|38 Fotos|Admin-Upload">
<p>Der Kalender 2022 mit Kippkarten je Monat und einer Galerie zum Wischen. Neue Fotos lädt der Admin hoch, gleich mehrere auf einmal.</p>
<img src="bilder/showcase/fotokalender.webp" alt="Übersicht des Foto-Kalenders mit Karten für Januar bis April">
</li>
<li data-title="Adventskalender" data-icon="star" data-badges="24 Türen|eigener Link|ohne Konto">
<p>Jeden Tag springt eine Tür auf, dahinter eine Quizfrage. Jeder Teilnehmer hat seinen Link, seinen Fortschritt und seine Punkte.</p>
<img src="bilder/showcase/advent-tueren.webp" alt="Adventskalender: 24 farbige Türen in gemischter Reihenfolge, Tür 12 golden umrandet">
</li>
<li data-title="Quiz hinter der Tür" data-icon="sparkle" data-badges="Antwort A–C|Bild/Video|Gewinnstufen">
<p>Die Tür schwingt am Scharnier auf, dann kommt die Frage. Nach der Antwort folgen eine Reaktion mit Bild oder YouTube-Video, Punkte und mehr freigeschaltete Gewinne.</p>
<img src="bilder/showcase/advent-frage.webp" alt="Geöffnete Tür 12 mit Frage und drei Antwortknöpfen">
</li>
<li data-title="Bilder in jeder Größe" data-icon="bolt" data-badges="imgproxy|WebP|signiert">
<p>Originale liegen einmal im Medienordner. imgproxy rechnet die passende Größe, und nur signierte Adressen funktionieren.</p>
</li>
<li data-title="Pflege im Admin" data-icon="edit" data-badges="EasyAdmin|Vorschau|YAML-Import">
<p>Projekte, Kalenderblätter, Türen mit Antworten, Gewinnstufen und Teilnehmer pflegt der Admin in EasyAdmin, mit einer Vorschau aller Türen.</p>
</li>
</ol>
<ul class="hl-showcase__tags"><li>BetAI</li><li>ESC Voting</li><li>gemeinsame Anmeldung</li><li>Logo als SVG</li><li>Schriften selbst gehostet</li><li>Türen serverseitig freigeschaltet</li><li>Europe/Berlin</li><li>nach dem 24. alles offen</li><li>Live Components</li><li>eigene Domain je Tool möglich</li><li>alte Adressen leiten um</li></ul>
</div>

Symfony-App auf **abyss** (Komodo-Stack `hub`), erreichbar unter **wh-company.com** (www leitet um). Sie bündelt
die Startseite von WH-Company und mehrere kleine Tools unter `src/Tool/<Name>/`: **Fotokalender**,
**Adventskalender**, **BetAI** (nur angemeldet) und **ESC Voting**. Die Anmeldung braucht nur der Admin.
Teilnehmer des Adventskalenders kommen über ihren persönlichen Link.

<div class="hl-stats">
  <div class="hl-stat hl-stat--accent"><b>4</b><span>Dienste dauerhaft<br>web, app, imgproxy, database</span></div>
  <div class="hl-stat"><b>2</b><span>Dienste auf Abruf<br>migrate, backup</span></div>
  <div class="hl-stat"><b>4</b><span>Tools<br>Fotokalender, Advent, BetAI, ESC</span></div>
  <div class="hl-stat"><b>31</b><span>Tabellen<br>eine Datenbank</span></div>
  <div class="hl-stat"><b>1</b><span>Medienordner<br>Volume hub_media_data</span></div>
</div>

<div class="grid cards" markdown>

-   :material-account-group-outline:{ .lg } **Für wen**

    ---

    Besucher von wh-company.com (Startseite, Fotokalender), eingeladene Teilnehmer eines Adventskalenders
    und der Admin, der alles pflegt.

-   :material-shield-lock-outline:{ .lg } **Wer was darf**

    ---

    Gäste sehen Startseite, „Alle Projekte“ (ohne BetAI) und den Fotokalender. Teilnehmer sehen nur ihren
    Kalender. `/admin` und die Vorschau sind nur für `ROLE_ADMIN` erreichbar.

-   :material-image-multiple-outline:{ .lg } **Bilder**

    ---

    Uploads landen im Medienordner (`advent/`, `fotokalender/`, `projekte/`, `bet_ai/logos/`), ausgeliefert über
    imgproxy. Details: [Daten](daten.md).

</div>

```mermaid
flowchart LR
    u(["Besucher"]) --> vps["Caddy VPS<br/>TLS"]
    vps -- "WireGuard<br/>10.0.0.2:8197" --> web["web<br/>Caddy"]
    web -- "/img/*" --> img["imgproxy"]
    web -- "PHP" --> app["app<br/>Symfony"]
    app --> db[("MySQL<br/>hub_database_data")]
    app -- "Uploads" --> m[("Medienordner<br/>hub_media_data")]
    img -- "nur lesend" --> m

    classDef user fill:transparent,stroke:#0d9488,stroke-width:2px
    classDef app fill:#0d9488,stroke:#0f766e,color:#fff
    classDef data fill:#f59e0b,stroke:#b45309,color:#1b1c1e
    class u user
    class vps,web,img,app app
    class db,m data
```

Weiter: [Aufbau](aufbau.md) · [Betrieb](betrieb.md) · [Daten](daten.md) · [Probleme](probleme.md)
