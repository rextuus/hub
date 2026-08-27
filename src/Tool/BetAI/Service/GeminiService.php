<?php

namespace App\Tool\BetAI\Service;

use Gemini;
use Gemini\Client;
use Gemini\Data\Content;
use Gemini\Data\Tool;
use Gemini\Data\GoogleSearch;
use App\Tool\BetAI\Entity\BetAISetting;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class GeminiService
{
    private Client $client;

    public function __construct(
        #[Autowire(env: 'GEMINI_API_KEY')]
        string $apiKey,
        private EntityManagerInterface $entityManager
    ) {
        $this->client = Gemini::client($apiKey);
    }

    public function listModels(): array
    {
        try {
            $models = $this->client->models()->list();
            $result = [];
            foreach ($models->models as $model) {
                // Nur Modelle mit generateContent Unterstützung
                if (in_array('generateContent', $model->supportedGenerationMethods)) {
                    $result[] = [
                        'name' => $model->name,
                        'displayName' => $model->displayName ?? $model->name,
                        'description' => $model->description ?? '',
                    ];
                }
            }
            return $result;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getSelectedModel(): string
    {
        $setting = $this->entityManager->getRepository(BetAISetting::class)->findOneBy(['key' => 'gemini_model']);
        return $setting?->value ?? 'models/gemini-3.5-flash';
    }

    public function generateContentWithSystemInstruction(string $prompt, string $systemPrompt, bool $useSearch = false): string
    {
        $modelName = $this->getSelectedModel();

        $model = $this->client->generativeModel($modelName)
            ->withSystemInstruction(Content::parse($systemPrompt));

        if ($useSearch) {
            $model = $model->withTool(new Tool(googleSearch: new GoogleSearch()));
        }

        $response = $model->generateContent($prompt);
        $text = $response->text();

        if (null === $text) {
            throw new \RuntimeException('Gemini API did not return any text.');
        }

        return $text;
    }

    public function generateBetPredictions(string $startDate, string $endDate): string
    {
        $systemPrompt = $this->getSystemPrompt($startDate, $endDate);
        $userPrompt = "Analysiere das kommende Fussball-Wochenende vom {$startDate} bis zum {$endDate}. Recherchiere die Spielpläne der Top-5-Ligen und Pokale und generiere mir deine besten Wett-Vorschläge als reines JSON.";

        return $this->generateContentWithSystemInstruction($userPrompt, $systemPrompt, true);
    }

    public function replaceBetSuggestion(string $startDate, string $endDate, string $problematicBetJson, string $previousResponseJson, string $reason): string
    {
        $systemPrompt = $this->getSystemPrompt($startDate, $endDate);

        $userPrompt = <<<EOT
Ich benötige einen Ersatz für eine meiner aktuellen Wett-Vorschläge für den Zeitraum {$startDate} bis {$endDate}.

**Das Problem:**
Die folgende Wette weicht zu stark von den realen Marktquoten ab:
{$problematicBetJson}

**Grund für den Austausch:**
{$reason}

**Wichtige Anweisung:**
Hier ist meine bisherige vollständige Antwort von dir:
{$previousResponseJson}

Bitte generiere EINEN NEUEN Wett-Vorschlag, der die oben genannte problematische Wette ersetzt.
Achte darauf:
1. Schlage KEINE der Wetten vor, die bereits in der vorherigen Antwort enthalten waren.
2. Der neue Vorschlag muss den gleichen Qualitätskriterien entsprechen.
3. Antworte wieder EXAKT im gleichen JSON-Format wie zuvor, aber das `suggested_bets` Array soll NUR diesen EINEN neuen Ersatz-Vorschlag enthalten.
4. **WICHTIG:** Der neue Vorschlag MUSS den gleichen Wett-Typ (`type`) wie die problematische Wette haben (z.B. wenn es eine COMBI war, muss der Ersatz auch eine COMBI sein).
5. Falls es eine Kombiwette (COMBI) war, soll der neue Vorschlag idealerweise auch die gleiche Anzahl an Spielen (`matches_count`) enthalten wie das Original.
EOT;

        return $this->generateContentWithSystemInstruction($userPrompt, $systemPrompt, true);
    }

    private function getSystemPrompt(string $startDate, string $endDate): string
    {
        return <<<EOT
Du bist ein erfahrener, datengetriebener Sportwetten-Analyst und ein hochentwickeltes KI-System. Deine Aufgabe ist es, für die anstehenden Spieltage im europäischen Spitzenfussball fundierte Wett-Empfehlungen zu generieren.

## 1. Relevanter Scope (Ligen & Pokale)
Berücksichtige Spiele aus den **Top-5-Ligen inklusive ihrer nationalen Pokalwettbewerbe**:
- **Deutschland:** Bundesliga, 2. Bundesliga, DFB-Pokal
- **England:** Premier League, EFL Championship, FA Cup, EFL Cup
- **Spanien:** La Liga, Copa del Rey
- **Italien:** Serie A, Coppa Italia
- **Frankreich:** Ligue 1, Coupe de France

## 2. Deine Arbeitsweise & Recherche
- Nutze deine integrierte Websuche, um die **exakten und aktuell gültigen Spielpläne sowie Pokalrunden** für den Zeitraum von {$startDate} bis {$endDate} zu recherchieren.
- Erfinde keine Spiele. Alle vorgeschlagenen Wetten müssen auf realen Begegnungen basieren, die in diesem Zeitraum stattfinden.
- Verwende bei den Teamnamen stets die **offiziellen, gängigen Vereinsnamen** (z.B. "FC Bayern München" statt nur "Bayern"), damit mein automatisches Datenbanksystem sie per Text-Matching zuordnen kann.

## 3. Wett-Regeln & Kriterien
- Schlage eine gesunde Mischung aus **Einzelwetten (SINGLE)** und **Kombiwetten (COMBI)** vor (insgesamt maximal 6-8 Vorschläge).
- Begrenze Kombiwetten auf maximal 3 Spiele pro Kombi, um das Risiko zu kontrollieren.
- Bewerte jede Wette mit einem `confidence_score` von **1 bis 10** (1 = sehr unsicher/hohes Risiko, 10 = extrem hohe analytische Sicherheit).
- Begründe jede Wette kurz und präzise im Feld `ai_reasoning`.

## 4. Strenges Ausgabeformat (JSON-Schema)
Antworte **ausschließlich** mit einem validen JSON-Objekt. Verwende keinen Markdown-Codeblock (kein ```json ... ```) um das JSON herum, keinen einleitenden Text und keine erklärenden Sätze. Das JSON muss exakt folgender Struktur entsprechen:

{
  "gameweek_info": {
    "name": "String",
    "start_date": "YYYY-MM-DD",
    "end_date": "YYYY-MM-DD"
  },
  "suggested_bets": [
    {
      "type": "SINGLE",
      "market": "String",
      "prediction": "String",
      "total_odds": float,
      "confidence_score": int,
      "ai_reasoning": "String",
      "matches": [
        {
          "home_team": "String",
          "away_team": "String",
          "match_date": "YYYY-MM-DD HH:MM"
        }
      ]
    }
  ]
}
EOT;
    }
}
