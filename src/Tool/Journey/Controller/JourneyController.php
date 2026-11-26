<?php

declare(strict_types=1);

namespace App\Tool\Journey\Controller;

use App\Tool\Journey\Service\SlideProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Geburtstags-Journey für Kristin (aus advent übernommen; Texte, Antworten und Abläufe unverändert).
 * Nur auf der eigenen Domain (app.journey_host, lokal kresh.localhost); was es dort sonst gibt, regelt
 * JourneyHostListener. Keine Benutzer, keine Datenbank, keine Sitzung.
 *
 * Die Prüf-Routen nehmen nur POST an und weisen fremde Seiten ab (Origin bzw. Sec-Fetch-Site): Die fetch-Aufrufe der
 * Seite brauchen dafür nichts, ein CSRF-Token wäre ohne Sitzung nicht prüfbar.
 */
#[Route(host: '%app.journey_host%')]
final class JourneyController extends AbstractController
{
    public function __construct(
        private readonly SlideProvider $slideProvider,
    ) {
    }

    #[Route('/', name: 'app_journey', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('tool/journey/index.html.twig', $this->context());
    }

    /** Dieselbe Journey ohne Scroll-Sperre (Entwickler-Vorschau) – nur in der lokalen Entwicklung. */
    #[Route('/testing', name: 'app_journey_testing', methods: ['GET'], env: 'dev')]
    public function testing(): Response
    {
        return $this->render('tool/journey/testing.html.twig', $this->context());
    }

    #[Route('/api/sauna-choice', name: 'app_journey_sauna_choice', methods: ['POST'])]
    public function handleSaunaChoice(Request $request): JsonResponse
    {
        $data = $this->payload($request);
        $selectedTime = $data['choice'] ?? null;

        if ($selectedTime === 'Um 21:00 Uhr') {
            return $this->json([
                'success' => true,
                'message' => 'Exakt! Erst der legendäre Aufguss von Theon, und danach gibt es guten Gewissens noch einen Kumpir!',
            ]);
        }

        return $this->json([
            'success' => false,
            'message' => 'Bist du sicher, dass das wirklich schon der heißeste Aufguss ist? Denk nochmal an Theon...',
        ]);
    }

    #[Route('/api/kennzeichen-check', name: 'app_journey_kennzeichen_check', methods: ['POST'])]
    public function handleKennzeichenCheck(Request $request): JsonResponse
    {
        $data = $this->payload($request);
        $userInputs = \is_array($data['inputs'] ?? null) ? $data['inputs'] : [];

        $riddle = $this->slideProvider->getKennzeichenRiddle();
        $items = $riddle['items'];

        $correctCount = 0;
        foreach ($items as $index => $item) {
            $input = strtoupper(trim((string) ($userInputs[$index] ?? '')));
            if ($input === $item['code']) {
                $correctCount++;
            }
        }

        if ($correctCount === \count($items)) {
            return $this->json([
                'success' => true,
                'message' => 'Absoluter Profi! Alle Kennzeichen geknackt!',
            ]);
        }

        return $this->json([
            'success' => false,
            'message' => \sprintf('Du hast %d von %d Kennzeichen richtig. Schau noch mal genau hin!', $correctCount, \count($items)),
        ]);
    }

    #[Route('/api/kniffel-roll', name: 'app_journey_kniffel_roll', methods: ['POST'])]
    public function handleKniffelRoll(Request $request): JsonResponse
    {
        $data = $this->payload($request);
        $attempts = (int) ($data['attempts'] ?? 0);
        $currentDice = \is_array($data['dice'] ?? null) ? $data['dice'] : [];
        $heldDice = \is_array($data['held'] ?? null) ? $data['held'] : [];

        $dice = [];
        for ($i = 0; $i < 5; $i++) {
            if (isset($heldDice[$i]) && $heldDice[$i]) {
                $dice[] = $currentDice[$i] ?? random_int(1, 6);
            } else {
                $dice[] = random_int(1, 6);
            }
        }

        if ($attempts < 3) {
            return $this->json([
                'dice' => $dice,
                'success' => false,
                'message' => $this->getMessageForAttempts($attempts),
            ]);
        }

        // 3rd attempt: Calculate scores
        return $this->json([
            'dice' => $dice,
            'success' => true,
            'scores' => $this->calculateScores($dice),
            'message' => 'Wähle eine Kategorie für dein Ergebnis!',
        ]);
    }

    #[Route('/api/run-code', name: 'app_journey_run_code', methods: ['POST'])]
    public function handleRunCode(Request $request): JsonResponse
    {
        $data = $this->payload($request);
        $variable = $data['variable'] ?? null;

        if ($variable === 'kristin') {
            return $this->json([
                'success' => true,
                'output' => "Status: 200 OK\n\n// Wolf's Life Konfiguration...\nErgebnis: Kristin macht Wolfs Leben viel viel schöner.",
            ]);
        }

        if ($variable === 'kaffee') {
            return $this->json([
                'success' => false,
                'output' => "Status: 418 I'm a teapot\n\nKaffee ist zwar essenziell, bringt aber alleine noch kein Herzschmelzen. Versuch es nochmal!",
            ]);
        }

        return $this->json([
            'success' => false,
            'output' => "Status: 500 Internal Error\n\nSchlafen ist schön, bringt das Programm aber zum Stillstand. Wähle lieber die wichtigere Variable!",
        ]);
    }

    /** @return array<string, mixed> */
    private function context(): array
    {
        return [
            'slides' => $this->slideProvider->getSlides(),
            'saunaQuestion' => $this->slideProvider->getSaunaQuestion(),
            'kennzeichenRiddle' => $this->slideProvider->getKennzeichenRiddle(),
            'crosswordData' => $this->slideProvider->getCrosswordData(),
        ];
    }

    /**
     * JSON-Body einer Anfrage von dieser Seite. Fremde Seiten bekommen 403: Browser schicken bei POST von einer anderen
     * Seite immer Origin (bzw. Sec-Fetch-Site: cross-site). Verglichen wird nur der Host – Schema und Port sieht PHP
     * hinter den beiden Caddys nicht verlässlich. Fehlen beide Header (ältere iPhones bei Anfragen von derselben
     * Seite, curl), geht die Anfrage durch: Die Antworten sind ohnehin kein Geheimnis, nur fremde Seiten sollen draußen
     * bleiben.
     *
     * @return array<mixed>
     */
    private function payload(Request $request): array
    {
        $origin = $request->headers->get('Origin');
        $fetchSite = $request->headers->get('Sec-Fetch-Site');
        $sameOrigin = match (true) {
            null !== $origin => 0 === strcasecmp((string) parse_url($origin, \PHP_URL_HOST), $request->getHost()),
            null !== $fetchSite => \in_array($fetchSite, ['same-origin', 'none'], true),
            default => true,
        };
        if (!$sameOrigin) {
            throw new AccessDeniedHttpException('Nur von der Journey selbst.');
        }

        $data = json_decode($request->getContent(), true);

        return \is_array($data) ? $data : [];
    }

    private function getMessageForAttempts(int $attempts): string
    {
        if ($attempts > 2) {
            return 'Es bleibt zäh... Kristin wird langsam ungeduldig!';
        }
        return 'Gleich nochmal würfeln!';
    }

    /**
     * @param list<int> $dice
     *
     * @return array<string, int>
     */
    private function calculateScores(array $dice): array
    {
        $counts = array_count_values($dice);
        $scores = [];

        // Upper section
        for ($i = 1; $i <= 6; $i++) {
            $scores["{$i}er"] = ($counts[$i] ?? 0) * $i;
        }

        // Lower section
        $scores['3er-pasch'] = (max($counts) >= 3) ? array_sum($dice) : 0;
        $scores['4er-pasch'] = (max($counts) >= 4) ? array_sum($dice) : 0;
        $scores['full-house'] = (\in_array(3, $counts, true) && \in_array(2, $counts, true)) ? 25 : 0;

        sort($dice);
        $uniqueDice = array_unique($dice);
        $straight = implode('', $uniqueDice);
        $scores['kleine-strasse'] = (str_contains($straight, '1234') || str_contains($straight, '2345') || str_contains($straight, '3456')) ? 30 : 0;
        $scores['grosse-strasse'] = (str_contains($straight, '12345') || str_contains($straight, '23456')) ? 40 : 0;
        $scores['kniffel'] = (max($counts) === 5) ? 50 : 0;
        $scores['chance'] = array_sum($dice);

        return $scores;
    }
}
