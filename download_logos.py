#!/usr/bin/env python3

import csv
import html
import re
import time
from pathlib import Path
from urllib.parse import urljoin

import requests
from bs4 import BeautifulSoup


BASE_URL = "https://football-logos.cc"

OUTPUT_DIR = Path("club-logos")
CSV_FILE = Path("football_clubs_2026_27.csv")

HEADERS = {
    "User-Agent": (
        "Mozilla/5.0 (X11; Linux x86_64) "
        "AppleWebKit/537.36 "
        "(KHTML, like Gecko) "
        "Chrome/139.0 Safari/537.36"
    )
}

session = requests.Session()
session.headers.update(HEADERS)


# ================================================================
# Die 10 gewünschten Ligen
# ================================================================

LEAGUES = [
    {
        "country": "England",
        "league": "Premier League",
        "url": f"{BASE_URL}/england/english-premier-league/",
    },
    {
        "country": "England",
        "league": "EFL Championship",
        "url": f"{BASE_URL}/england/efl-championship/",
    },

    {
        "country": "Spain",
        "league": "La Liga",
        "url": f"{BASE_URL}/spain/la-liga/",
    },
    {
        "country": "Spain",
        "league": "La Liga 2",
        "url": f"{BASE_URL}/spain/la-liga-2/",
    },

    {
        "country": "Germany",
        "league": "Bundesliga",
        "url": f"{BASE_URL}/germany/bundesliga/",
    },
    {
        "country": "Germany",
        "league": "2. Bundesliga",
        "url": f"{BASE_URL}/germany/2-bundesliga/",
    },

    {
        "country": "Italy",
        "league": "Serie A",
        "url": f"{BASE_URL}/italy/serie-a/",
    },
    {
        "country": "Italy",
        "league": "Serie B",
        "url": f"{BASE_URL}/italy/serie-b/",
    },

    {
        "country": "France",
        "league": "Ligue 1",
        "url": f"{BASE_URL}/france/ligue-1/",
    },
    {
        "country": "France",
        "league": "Ligue 2",
        "url": f"{BASE_URL}/france/ligue-2/",
    },
]


# ================================================================
# HTTP
# ================================================================

def get(url):

    print(f"GET {url}")

    response = session.get(
        url,
        timeout=30,
    )

    response.raise_for_status()

    return response


# ================================================================
# Dateiname / Slug
# ================================================================

def slugify(value):

    value = value.strip()

    value = value.lower()

    value = (
        value
        .replace("ä", "ae")
        .replace("ö", "oe")
        .replace("ü", "ue")
        .replace("ß", "ss")
        .replace("é", "e")
        .replace("è", "e")
        .replace("ê", "e")
        .replace("á", "a")
        .replace("à", "a")
        .replace("ó", "o")
        .replace("ò", "o")
        .replace("í", "i")
        .replace("ì", "i")
        .replace("ú", "u")
        .replace("ù", "u")
    )

    value = re.sub(
        r"[^a-z0-9]+",
        "-",
        value,
    )

    return value.strip("-")


# ================================================================
# HTML normalisieren
# ================================================================

def clean_html(value):

    value = html.unescape(value)

    value = value.replace(
        "\\/",
        "/",
    )

    value = value.replace(
        "&quot;",
        '"',
    )

    return value


# ================================================================
# LIGA -> CLUBSEITEN
# ================================================================

def get_clubs_from_league(league):

    response = get(league["url"])

    soup = BeautifulSoup(
        response.text,
        "html.parser",
    )

    clubs = []

    for link in soup.find_all(
        "a",
        href=True,
    ):

        href = urljoin(
            BASE_URL,
            link["href"],
        )

        href = href.split("?")[0].rstrip("/")

        # Nur football-logos.cc
        if not href.startswith(BASE_URL):
            continue

        # Keine externen Links
        if any(
            domain in href.lower()
            for domain in [
                "x.com",
                "twitter.com",
                "facebook.com",
                "instagram.com",
                "youtube.com",
                "linkedin.com",
            ]
        ):
            continue

        # Nur Links innerhalb desselben Landes
        country_slug = slugify(
            league["country"]
        )

        expected_prefix = (
            f"{BASE_URL}/{country_slug}/"
        )

        if not href.startswith(
            expected_prefix
        ):
            continue

        # Die Liga selbst überspringen
        if href == league["url"].rstrip("/"):
            continue

        # --------------------------------------------------------
        # Club-URL erkennen:
        #
        # /france/club/
        # /england/club/
        # --------------------------------------------------------

        path = href.replace(
            BASE_URL,
            "",
            1,
        )

        parts = [
            x for x in path.split("/")
            if x
        ]

        if len(parts) != 2:
            continue

        if parts[0] != country_slug:
            continue

        club_name = link.get_text(
            " ",
            strip=True,
        )

        if not club_name:
            continue

        # Keine offensichtlichen Nicht-Clubs
        if club_name.lower() in [
            "home",
            "contact",
            "privacy",
            "about",
            "facebook",
            "instagram",
            "twitter",
        ]:
            continue

        club = {
            "name": club_name,
            "url": href + "/",
        }

        # Duplikate entfernen
        if not any(
            existing["url"] == club["url"]
            for existing in clubs
        ):
            clubs.append(club)

    return clubs

# ================================================================
# ASSETS AUF CLUBSEITE
# ================================================================

def find_assets(club):

    response = get(
        club["url"]
    )

    source = clean_html(
        response.text
    )

    soup = BeautifulSoup(
        source,
        "html.parser",
    )

    png_urls = set()
    svg_urls = set()

    # ============================================================
    # PNG
    # ============================================================

    png_pattern = re.compile(
        r'https?://assets\.football-logos\.cc/'
        r'[^"\'>\s\\]+?\.png',
        re.IGNORECASE,
    )

    for match in png_pattern.findall(
        source
    ):

        match = match.rstrip(
            '",]}'
        )

        png_urls.add(match)

    # ============================================================
    # SVG
    #
    # WICHTIG:
    # NICHT footballshirts.com.svg nehmen!
    # ============================================================

    svg_pattern = re.compile(
        r'https?://[^"\'>\s\\]+?\.svg',
        re.IGNORECASE,
    )

    for match in svg_pattern.findall(
        source
    ):

        match = match.rstrip(
            '",]}'
        )

        if (
            "footballshirts.com.svg"
            in match
        ):
            continue

        if (
            "football-logos.cc"
            not in match
        ):
            continue

        svg_urls.add(match)

    # ============================================================
    # Attribute durchsuchen
    # ============================================================

    for tag in soup.find_all(
        True
    ):

        for attr in [
            "href",
            "src",
            "data-src",
            "data-original",
            "data-url",
        ]:

            value = tag.get(attr)

            if not value:
                continue

            value = clean_html(
                value
            )

            url = urljoin(
                club["url"],
                value,
            )

            if (
                ".png"
                in url.lower()
                and
                "assets.football-logos.cc"
                in url
            ):

                png_urls.add(url)

            if (
                ".svg"
                in url.lower()
                and
                "footballshirts.com.svg"
                not in url
                and
                "football-logos.cc"
                in url
            ):

                svg_urls.add(url)

    # ============================================================
    # PNG priorisieren
    # ============================================================

    def png_score(url):

        score = 0

        if "/1500x1500/" in url:
            score += 100

        if "/3000x3000/" in url:
            score += 90

        if "/700x700/" in url:
            score += 80

        if "/512x512/" in url:
            score += 50

        return score

    png_urls = sorted(
        png_urls,
        key=png_score,
        reverse=True,
    )

    # ============================================================
    # SVG
    # ============================================================

    # Falls die Seite mehrere SVGs enthält,
    # keine generischen UI-/Footer-SVGs nehmen.

    ignored_svg_names = [
        "footballshirts.com.svg",
        "logo.svg",
        "favicon.svg",
        "icon.svg",
    ]

    filtered_svg = []

    for url in svg_urls:

        filename = (
            url
            .split("/")[-1]
            .lower()
        )

        if filename in ignored_svg_names:
            continue

        filtered_svg.append(url)

    svg_urls = filtered_svg

    png_url = (
        png_urls[0]
        if png_urls
        else None
    )

    svg_url = (
        svg_urls[0]
        if svg_urls
        else None
    )

    return svg_url, png_url


# ================================================================
# DOWNLOAD
# ================================================================

def download(
    url,
    destination,
):

    if not url:
        return False

    if destination.exists():
        print(
            f"  ✓ Bereits vorhanden: "
            f"{destination}"
        )

        return True

    print(
        f"  ↓ {url}"
    )

    try:

        response = session.get(
            url,
            timeout=60,
        )

        response.raise_for_status()

        destination.parent.mkdir(
            parents=True,
            exist_ok=True,
        )

        with open(
            destination,
            "wb",
        ) as file:

            file.write(
                response.content
            )

        print(
            f"  ✓ {destination}"
        )

        return True

    except Exception as e:

        print(
            f"  ❌ {e}"
        )

        return False


# ================================================================
# MAIN
# ================================================================

def main():

    OUTPUT_DIR.mkdir(
        parents=True,
        exist_ok=True,
    )

    csv_rows = []

    total_clubs = 0
    successful = 0
    failed = 0

    for league in LEAGUES:

        print()
        print()
        print(
            "=" * 80
        )

        print(
            f"{league['country']} / "
            f"{league['league']}"
        )

        print(
            league["url"]
        )

        print(
            "=" * 80
        )

        try:

            clubs = get_clubs_from_league(
                league
            )

        except Exception as e:

            print(
                f"❌ Liga konnte nicht geladen werden: {e}"
            )

            continue

        print(
            f"Gefundene Clubs: {len(clubs)}"
        )

        for number, club in enumerate(
            clubs,
            start=1,
        ):

            total_clubs += 1

            print()
            print(
                "-" * 80
            )

            print(
                f"[{number}/{len(clubs)}] "
                f"{club['name']}"
            )

            print(
                f"  {club['url']}"
            )

            try:

                svg_url, png_url = find_assets(
                    club
                )

                print(
                    f"  SVG: {svg_url or 'NICHT GEFUNDEN'}"
                )

                print(
                    f"  PNG: {png_url or 'NICHT GEFUNDEN'}"
                )

                club_slug = slugify(
                    club["name"]
                )

                country_slug = slugify(
                    league["country"]
                )

                league_slug = slugify(
                    league["league"]
                )

                directory = (
                    OUTPUT_DIR
                    / country_slug
                    / league_slug
                    / club_slug
                )

                svg_file = (
                    directory
                    / f"{club_slug}.svg"
                )

                png_file = (
                    directory
                    / f"{club_slug}.png"
                )

                svg_ok = download(
                    svg_url,
                    svg_file,
                )

                png_ok = download(
                    png_url,
                    png_file,
                )

                if svg_ok and png_ok:

                    successful += 1

                    print(
                        "  ✅ PNG + SVG"
                    )

                elif svg_ok or png_ok:

                    successful += 1

                    print(
                        "  ⚠️ Nur ein Format"
                    )

                else:

                    failed += 1

                    print(
                        "  ❌ Kein Download"
                    )

                # CSV
                csv_rows.append({
                    "season": "2026/27",
                    "country": league["country"],
                    "league": league["league"],
                    "club": club["name"],
                    "club_url": club["url"],
                    "png_url": png_url or "",
                    "svg_url": svg_url or "",
                    "png_file": (
                        str(png_file)
                        if png_url
                        else ""
                    ),
                    "svg_file": (
                        str(svg_file)
                        if svg_url
                        else ""
                    ),
                })

            except Exception as e:

                failed += 1

                print(
                    f"  ❌ Fehler: {e}"
                )

            # Website nicht unnötig belasten
            time.sleep(
                0.5
            )

    # ============================================================
    # CSV schreiben
    # ============================================================

    with open(
        CSV_FILE,
        "w",
        encoding="utf-8-sig",
        newline="",
    ) as file:

        fieldnames = [
            "season",
            "country",
            "league",
            "club",
            "club_url",
            "png_url",
            "svg_url",
            "png_file",
            "svg_file",
        ]

        writer = csv.DictWriter(
            file,
            fieldnames=fieldnames,
        )

        writer.writeheader()

        writer.writerows(
            csv_rows
        )

    # ============================================================
    # Ergebnis
    # ============================================================

    print()
    print()
    print(
        "=" * 80
    )

    print(
        "FERTIG"
    )

    print(
        f"Clubs gefunden: {total_clubs}"
    )

    print(
        f"Erfolgreich:    {successful}"
    )

    print(
        f"Fehlgeschlagen: {failed}"
    )

    print()
    print(
        f"Logos: {OUTPUT_DIR}"
    )

    print(
        f"CSV:   {CSV_FILE}"
    )

    print(
        "=" * 80
    )


if __name__ == "__main__":
    main()
