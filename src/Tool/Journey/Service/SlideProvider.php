<?php

declare(strict_types=1);

namespace App\Tool\Journey\Service;

/**
 * Inhalte der Geburtstags-Journey für Kristin (aus advent übernommen, Texte unverändert). Bilder liegen im
 * Medienordner unter journey/ und kommen über media_url(…, 'journey').
 */
final class SlideProvider
{
    public function getSlides(): array
    {
        return [
            [
                'id' => 'intro-welcome-1',
                'title' => 'Happy Birthday Kristin',
                'text' => 'Dies ist ein kleines Side-Project von Wolf, einem Developer-Nerd, der das Glück hatte, Kristin in ihrem bislang aktuellstem Lebensjahr kennen gelernt haben zu dürfen. Und der es irgendwie geschafft hat, diesen äußerst liebenswerten Menschen von sich zu begeistern.',
                'image' => 'journey/intro.jpg',
            ],
            [
                'id' => 'intro-welcome-2',
                'title' => 'Rückblick',
                'text' => 'Heute startet ein weiteres Kristin-Lebensjahr. Da ist Wolf natürlich schon gespannt, was er in diesem wohl alles Neues über sie erfahren wird. Aber vorab schaut er einmal zurück, was er im letzten so alles über sie erfahren hat.',
                'image' => 'journey/intro.jpg',
            ],
            [
                'id' => 'intro-role',
                'title' => 'Kurzer Sicherheitscheck',
                'text' => 'Als echter DevOp hat Wolf sich natürlich auch um Einlasskontrollen gekümmert. Es soll ja nicht jeder Kristins dunkelste Geheimnis erfahren dürfen. Also:',
                'image' => 'journey/intro-3.jpg',
            ],
            [
                'id' => 'neptunbad-aussen',
                'title' => 'Das Neptunbad',
                'text' => 'Eine der allerersten Lektionen, die Wolf über Kristin lernen durfte: Entspannung darf nicht zu kurz kommen. Auch wenn die Urban-Sports Mitgliedschaft natürlich primär fürs Reformer-Pilatis abgeschlossen wurde. Das eigentliche Highlight sind günstigen Besuche im Neptunbad',
                'image' => 'journey/neptunbad.jpg',
            ],
            [
                'id' => 'neptunbad-sauna',
                'title' => 'Der perfekte Abend',
                'text' => 'Aber wer mit Kristin in die Sauna geht, lernt schnell: Es gibt feste Abläufe und ein perfektes Timing für die Reihenfolge in der die Aufgüsse besucht werden! Dabei ist besonders ein Aufguss ein Muss-Besuch. Der „Funkazan“-Aufguss ist der heißeste und intensivste Aufguss im Neptunbad und findet bei einer Raumtemperatur von rund 90 Grad Celsius statt',
                'image' => 'journey/neptunbad-sauna.jpg',
            ],
            [
                'id' => 'autobahn-fahrt',
                'title' => 'Unterwegs auf der A1',
                'text' => 'Entspannt aus dem Fenster schauen auf langen Autofahrten? Fehlanzeige! Wenn Kristin und ihre Freundin Anika unterwegs sind, verwandelt sich die Autobahn in eine hochengagierte Sammeljagd nach Kennzeichen. Als gelegentlicher Beifahrer wurde Wolf natürlich sofort rekrutiert und durfte schon so einige Kennzeichen notieren',
                'image' => 'journey/autobahn.jpg',
            ],
            [
                'id' => 'kennzeichen-game',
                'title' => 'Die Kennzeichen-Jägerin',
                'text' => 'Dabei wurden Kennzeichen aus den verschiedensten Teilen Deutschlands gesichtet. Da stellt sich natürlich die Frage ob Kristin nur die reinen Kennzeichen gesammelt hat oder auch das Wissen wo diese überhaupt verortet sind.',
                'image' => 'journey/nummernschild.jpg',
            ],
            [
                'id' => 'rio-intro',
                'title' => 'Urlaub in Rio de Janeiro',
                'text' => 'Rio de Janeiro – Sonne, Ipanema-Beach und... hier und da ein wenig Warten auf das Essen im Restaurant. Kristin ist als Globe-Trotterin bekannt. Und normalerweise auch für ihre zarte Ungeduld... In allen Lebenslagen. Doch im Urlaub durfte Wolf eine ziemlich smarte Lösung für das Überbrücken der Wartezeiten von ihr kennenlernen: bisschen Zocken!',
                'image' => 'journey/rio.jpg',
            ],
            [
                'id' => 'rio-kniffel',
                'title' => 'Die Kniffel-Challenge',
                'text' => 'Während also in der Küche geschuftet wird, verwandelte sie den Esstisch kurzerhand in wahlweise ein Toto oder Kniffel-Eldorado. Wolf, selbst bekennender weise einer der schlechteste Verlierer der Welt, hatte ordentlich gegen ihr Würfelgeschick zu kämpfen...',
                'image' => 'journey/kniffel.jpg',
            ],
            [
                'id' => 'taylor-kardashian-intro',
                'title' => 'The Era of Keeping Up with Kristin',
                'text' => 'Ob die neusten Easter Eggs im Taylor-Swift-Universum oder der wöchentliche Drama-Report direkt aus Calabasas von den Kardashians – Kristin weiß wie man aus Insta die wichtigsten Infos für den Tag zusammenbekommt. Aber auch über die neusten Entwicklungen beim Effzeh, weiß sie meist erstaunlich gut und sogar schon vor Wolf Bescheid',
                'image' => 'journey/instagram.jpg',
            ],
            [
                'id' => 'taylor-kardashian-game',
                'title' => 'The Ultimate Pop-Culture Quiz',
                'text' => 'Wolf hat zwar versucht zu lernen und ein ähnlich professionelles Niveau zu erreichen, aber hier muss er passen:',
                'image' => 'journey/magazine.jpg',
            ],
            [
                'id' => 'food-intro',
                'title' => 'Königlicher Spargel & Die Hollandaise-Frage',
                'text' => 'Auch gutes Essen kommt in Kristins Leben nicht zu kurz. Es soll ja hemmungslose Soßenfanatiker geben, die alles ertränken, bis der Tellerboden nicht mehr sichtbar ist. Aber wenn Kristin kocht – ob knusprige Bagels, buntes Ofengemüse oder ihre legendäre Pilzrahmsoße –, ist jeder hin und weg. Auch ohne extra Soße.',
                'image' => 'journey/spargel.jpg',
            ],
            [
                'id' => 'food-game',
                'title' => 'Das Hollandaise-Gießen',
                'text' => 'Für alle anderen Essen gilt: Es verdient die perfekte Dosis Liebe – oder eben Hollandaise! Halte den Button gedrückt und gieße genau die richtige Menge Soße über den Spargel.',
                'image' => 'journey/saucen.jpg',
            ],
            [
                'id' => 'taste-tinder-intro',
                'title' => 'Suche im Netz',
                'text' => 'Kristin schwärmt für Romcoms und Romance-Bücher über charmante Eishockeyspieler. Kitschig ist sie trotzdem keineswegs – schließlich hat sie Wolf die Chance gegeben, sie auf dem unromantischsten Weg überhaupt kennenzulernen: über das Internet! Wie viele Jungs sie wohl vor ihm nach links gewischt hat?',
                'image' => 'journey/paerchen.jpg',
            ],
            [
                'id' => 'taste-tinder',
                'title' => 'Taste Match',
                'text' => 'Zeit für das ultimative Swipe-Match! Wische nach rechts für Top und nach links für Flop – kennst du Kristins Lieblings-Snacks und Vorlieben?',
                'image' => 'journey/tinder.jpg',
            ],
            [
                'id' => 'macbook-uniklinik',
                'title' => 'MacBook in der Uniklinik',
                'text' => 'Apropos coole Kerle. In der Uniklinik herrscht bekanntlich strenge Windows-Diktatur. Doch Kristin ist mit ihrem MacBook als mutige IT-Rebellin unterwegs. Jedes Mal, wenn sie beim Support anruft, heißt es zähneknirschend: "Mac? Machen wir nicht!" Aber Kristin beißt sich durch – Technikaffinität hat sie definitiv im Blut.',
                'image' => 'journey/macbook.jpg',
            ],
            [
                'id' => 'it-hacking',
                'title' => 'Home-Support',
                'text' => 'Ein ganzes Jahr kennt sie nun Wolf schon – aber weiß Kristin eigentlich, was dieser komische Kerl da stundenlang im dunklen Kämmerlein am Terminal tippt? Oder wie diese Geburtstags-Journey hier gebaut wurde? Für die ehemalige PC-Schrauberin sollte so ne kleine Coding-Challenge doch kein Problem sein!',
                'image' => 'journey/hacking.jpg',
            ],
            [
                'id' => 'scratch-card-intro',
                'title' => 'Die große Überraschung',
                'text' => 'So ein Lebensjahr steckt voller Höhen und Tiefen. Bei Kristin beschränken sich die Tiefen zum Glück in der Regel auf kleine Tollpatschigkeiten im Alltag, wie das Kleckern beim Essen. Für die Höhen sorgt sie ja meist selbst mit einer professionellen und umfangreichen Urlaubsplanung. Dennoch kann ein klein bisschen extra Glück zum Start ins neue Lebensjahr ja nicht schaden',
                'image' => 'journey/geschenk.jpg',
            ],
            [
                'id' => 'scratch-card-game',
                'title' => 'Rubbellos-Glück',
                'text' => 'Rubbel das Feld frei und schau, welcher legendäre, grandios wertvolle Gewinn (gesponsert von der WH-Company) auf dich wartet!',
                'image' => 'journey/lose.jpg',
            ],
            [
                'id' => 'end-slide',
                'title' => 'And that\'s it',
                'text' => 'Toll, dass du den kleinen Spaß bis hierin mitgemacht hast. Wir hoffen es hat ein wenig Freude bereitet. Ansonsten kann sich gerne an den 24/7 nicht besetzten Support der WH-Company gerichtet und Beschwerde eingelegt werden. Alles Gute zum Geburtstag Kristin',
                'image' => 'journey/intro.jpg',
            ],
        ];
    }

    public function getCrosswordData(): array
    {
        return [
            "title" => "Kreuzworträtsel",
            "width" => 22,
            "height" => 22,
            "grid" => [
                "#################.####",
                "##############.##.####",
                "##############.##.####",
                "#######.#####.....####",
                "#######.#.##.#.##.....",
                "#######.#......#######",
                "######....##.#########",
                "#########......#######",
                "#########.##.#########",
                "####......############",
                "###.##.#.#############",
                "##.....#.....#########",
                "###.##.#.#############",
                "###.##.#.#############",
                ".....##......#########",
                "##########.###########",
                "#######.....##########",
                "#######.###.##########",
                "#######.#.....########",
                "#######.###.#.########",
                "#######.#.....########",
                "#############.########"
            ],
            "entries" => [
                ["number" => 1, "direction" => "down", "row" => 1, "col" => 18, "length" => 5, "answer" => "VOGEL", "clue" => "Fliegendes Tier"],
                ["number" => 2, "direction" => "down", "row" => 2, "col" => 15, "length" => 5, "answer" => "BLATT", "clue" => "Teil einer Pflanze"],
                ["number" => 3, "direction" => "down", "row" => 4, "col" => 8, "length" => 4, "answer" => "HOLA", "clue" => "Spanisch für „Hallo“"],
                ["number" => 4, "direction" => "across", "row" => 4, "col" => 14, "length" => 5, "answer" => "KATZE", "clue" => "Samtpfote"],
                ["number" => 5, "direction" => "down", "row" => 5, "col" => 10, "length" => 6, "answer" => "VHAGAR", "clue" => "Targaryen-Drache (sehr fett)"],
                ["number" => 6, "direction" => "down", "row" => 5, "col" => 13, "length" => 5, "answer" => "DEUTZ", "clue" => "Traktor- und Landmaschinenhersteller"],
                ["number" => 7, "direction" => "across", "row" => 5, "col" => 18, "length" => 5, "answer" => "LILIE", "clue" => "Blume mit auffälliger Blüte"],
                ["number" => 8, "direction" => "across", "row" => 6, "col" => 10, "length" => 6, "answer" => "HUBERT", "clue" => "Vorname von Wolf"],
                ["number" => 9, "direction" => "across", "row" => 7, "col" => 7, "length" => 4, "answer" => "JAVA", "clue" => "Programmiersprache / Insel im Pazifik"],
                ["number" => 10, "direction" => "across", "row" => 8, "col" => 10, "length" => 6, "answer" => "GARTEN", "clue" => "Grünfläche am Haus"],
                ["number" => 11, "direction" => "across", "row" => 10, "col" => 5, "length" => 6, "answer" => "DÜMMER", "clue" => "Durchgehend etwa 1,50 m tiefes Gewässer in der Mitte Deutschlands"],
                ["number" => 12, "direction" => "down", "row" => 10, "col" => 7, "length" => 5, "answer" => "MUSIK", "clue" => "Tonkunst"],
                ["number" => 13, "direction" => "down", "row" => 10, "col" => 9, "length" => 6, "answer" => "EFFZEH", "clue" => "Abkürzung des 1. FC Köln"],
                ["number" => 14, "direction" => "down", "row" => 11, "col" => 4, "length" => 5, "answer" => "BLUME", "clue" => "Pflanze mit Blüte"],
                ["number" => 15, "direction" => "across", "row" => 12, "col" => 3, "length" => 5, "answer" => "FLUSS", "clue" => "Größeres fließendes Gewässer"],
                ["number" => 16, "direction" => "across", "row" => 12, "col" => 9, "length" => 5, "answer" => "FISCH", "clue" => "Tier im Wasser"],
                ["number" => 17, "direction" => "across", "row" => 15, "col" => 1, "length" => 5, "answer" => "REGEN", "clue" => "Wasser vom Himmel"],
                ["number" => 18, "direction" => "across", "row" => 15, "col" => 8, "length" => 6, "answer" => "PHILIP", "clue" => "Ehemann von Queen Elizabeth II."],
                ["number" => 19, "direction" => "down", "row" => 15, "col" => 11, "length" => 3, "answer" => "LEO", "clue" => "Verschmuster Hund"],
                ["number" => 20, "direction" => "across", "row" => 17, "col" => 8, "length" => 5, "answer" => "NAXOS", "clue" => "Insel Griechenlands"],
                ["number" => 20, "direction" => "down", "row" => 17, "col" => 8, "length" => 5, "answer" => "NACHT", "clue" => "Zeit ohne Tageslicht"],
                ["number" => 21, "direction" => "down", "row" => 17, "col" => 12, "length" => 5, "answer" => "STERN", "clue" => "Leuchtet am Nachthimmel"],
                ["number" => 22, "direction" => "across", "row" => 19, "col" => 10, "length" => 5, "answer" => "WIESE", "clue" => "Grünfläche mit Gras"],
                ["number" => 23, "direction" => "down", "row" => 19, "col" => 14, "length" => 4, "answer" => "ESEL", "clue" => "Graues Nutztier"],
                ["number" => 24, "direction" => "across", "row" => 21, "col" => 10, "length" => 5, "answer" => "SONNE", "clue" => "Stern unseres Sonnensystems"]
            ]
        ];
    }

    public function getSaunaQuestion(): array
    {
        return [
            'question' => 'Aber wann besucht man ihn am besten?',
            'optionA' => 'Um 19:00 Uhr',
            'optionB' => 'Um 21:00 Uhr',
            'theonImage' => 'journey/theon.png',
            'kumpirImage' => 'journey/kumpir.jpg',
        ];
    }

    public function getKennzeichenRiddle(): array
    {
        return [
            'items' => [
                ['city' => 'Minden', 'code' => 'MI', 'hint' => 'Nabel der Welt'],
                ['city' => 'Lübbecke', 'code' => 'LK', 'hint' => 'Kristins Heimat'],
                ['city' => 'Köln', 'code' => 'K', 'hint' => 'Heimat vun dä Dom, em Effzeh, eehrfelder Jungs un em lecker Mädche'],
                ['city' => 'Hamburg', 'code' => 'HH', 'hint' => 'Hier soll dieser Loris wohnen?!'],
                ['city' => 'Düsseldorf', 'code' => 'D', 'hint' => 'Die eigentlich verbotene Stadt'],
                ['city' => 'Monschau', 'code' => 'MON', 'hint' => 'Ziel von Rentnern aus aller Welt'],
                ['city' => 'Villingen-Schwenningen', 'code' => 'VS', 'hint' => 'Schwarzwald. Kennt man doch...'],
            ],
        ];
    }
}