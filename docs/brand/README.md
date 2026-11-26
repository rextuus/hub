# Logo WH-Company

Erzeugt mit `make_logo.py` (Python, fontTools) nach der Vorlage `banner.png` der alten Startseite. Die Schrift ist
**Forum** von Denis Masharov (SIL Open Font License 1.1, <https://fonts.google.com/specimen/Forum>), in Pfade
umgewandelt – das SVG braucht also keine Webfont und sieht als `<img>` und Favicon überall gleich aus.

```bash
curl -sSfLO https://github.com/google/fonts/raw/main/ofl/forum/Forum-Regular.ttf
python3 docs/brand/make_logo.py Forum-Regular.ttf assets/images/brand
```

| Datei | Inhalt |
|---|---|
| `assets/images/brand/logo.svg` | Säule, Strich, „WH“, „WH-COMPANY“, Unterzeile |
| `assets/images/brand/logo-mark.svg` | Säule, Strich, „WH“ (Kopfzeile) |
| `assets/images/brand/favicon.svg` | nur die Säule, kräftiger (lesbar ab 16 px) |

Farbe `#d97824`, Hintergrund durchsichtig.
