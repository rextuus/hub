<?php

declare(strict_types=1);

namespace App\Tests\Tool\Journey;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Journey auf kresh.wh-company.com: Dort gibt es nur die Journey (privat, ohne Sitzung), die Hauptdomain liefert sie
 * nicht. Die Prüf-Routen nehmen nur POST an und weisen fremde Seiten ab.
 */
final class JourneyTest extends WebTestCase
{
    private const string HOST = 'https://kresh.wh-company.com';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
    }

    public function testJourneyAnswersOnItsHostWithoutSessionAndIndexing(): void
    {
        $this->client->request('GET', self::HOST.'/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('title', 'Ein Lebensjahr mit Kristin');
        self::assertSelectorExists('meta[name=robots][content*=noindex]');
        self::assertSelectorExists('#section-intro-welcome-1');
        self::assertSelectorExists('#section-scratch-card-game');
        self::assertResponseHeaderSame('X-Robots-Tag', 'noindex, nofollow');
        self::assertFalse($this->client->getRequest()->hasSession() && $this->client->getRequest()->getSession()->isStarted());
        self::assertResponseNotHasCookie('MOCKSESSID');
        self::assertResponseNotHasCookie('REMEMBERME');

        $html = (string) $this->client->getResponse()->getContent();
        // Keine externen Abhängigkeiten mehr, nichts von hub (Header, Footer, Anmeldung)
        foreach (['cloudinary', 'cdn.tailwindcss.com', 'cdnjs', '/login', 'navbar', '<footer', 'logo-mark', 'bootstrap'] as $needle) {
            self::assertStringNotContainsString($needle, $html, $needle);
        }
        self::assertStringContainsString('/journey/gsap.min', $html);
        self::assertStringContainsString('styles/journey.tailwind', $html);
    }

    /** @return iterable<string, array{string}> */
    public static function foreignPaths(): iterable
    {
        foreach (['/admin', '/login', '/logout', '/kalender', '/projekte', '/testing', '/media/projekte/x.jpg', '/gibt-es-nicht'] as $path) {
            yield $path => [$path];
        }
    }

    #[DataProvider('foreignPaths')]
    public function testOtherHubPathsAreNotFoundOnJourneyHost(string $path): void
    {
        $this->client->request('GET', self::HOST.$path);

        self::assertResponseStatusCodeSame(404);
        self::assertResponseHeaderSame('X-Robots-Tag', 'noindex, nofollow');
        self::assertFalse($this->client->getRequest()->hasSession() && $this->client->getRequest()->getSession()->isStarted());
    }

    public function testHealthcheckStillAnswersOnJourneyHost(): void
    {
        $this->client->request('GET', self::HOST.'/healthz');

        self::assertResponseIsSuccessful();
    }

    public function testMainHostDoesNotServeTheJourney(): void
    {
        $this->client->request('GET', 'https://wh-company.com/');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextNotContains('title', 'Kristin');
        self::assertResponseHeaderNotSame('X-Robots-Tag', 'noindex, nofollow');

        $this->postJson('https://wh-company.com/api/sauna-choice', ['choice' => 'Um 21:00 Uhr'], 'https://wh-company.com');
        self::assertResponseStatusCodeSame(404);
    }

    public function testSaunaChoice(): void
    {
        self::assertTrue($this->postJson('/api/sauna-choice', ['choice' => 'Um 21:00 Uhr'])['success']);

        $wrong = $this->postJson('/api/sauna-choice', ['choice' => 'Um 19:00 Uhr']);
        self::assertFalse($wrong['success']);
        self::assertStringContainsString('Theon', $wrong['message']);
    }

    public function testKennzeichenCheck(): void
    {
        $right = $this->postJson('/api/kennzeichen-check', ['inputs' => ['mi', ' LK ', 'k', 'HH', 'D', 'mon', 'VS']]);
        self::assertTrue($right['success']);
        self::assertSame('Absoluter Profi! Alle Kennzeichen geknackt!', $right['message']);

        $wrong = $this->postJson('/api/kennzeichen-check', ['inputs' => ['MI', 'LK', 'KÖ', 'HH']]);
        self::assertFalse($wrong['success']);
        self::assertSame('Du hast 3 von 7 Kennzeichen richtig. Schau noch mal genau hin!', $wrong['message']);
    }

    public function testRunCode(): void
    {
        self::assertTrue($this->postJson('/api/run-code', ['variable' => 'kristin'])['success']);
        self::assertStringStartsWith('Status: 418', $this->postJson('/api/run-code', ['variable' => 'kaffee'])['output']);
        self::assertStringStartsWith('Status: 500', $this->postJson('/api/run-code', ['variable' => 'schlaf'])['output']);
    }

    public function testKniffelRoll(): void
    {
        $roll = $this->postJson('/api/kniffel-roll', ['attempts' => 1, 'dice' => [0, 0, 0, 0, 0], 'held' => []]);
        self::assertFalse($roll['success']);
        self::assertCount(5, $roll['dice']);

        $last = $this->postJson('/api/kniffel-roll', ['attempts' => 3, 'dice' => [6, 6, 6, 6, 6], 'held' => [true, true, true, true, true]]);
        self::assertTrue($last['success']);
        self::assertSame([6, 6, 6, 6, 6], $last['dice']);
        self::assertSame(50, $last['scores']['kniffel']);
        self::assertSame(30, $last['scores']['chance']);
    }

    public function testApiOnlyAcceptsPostFromTheJourneyItself(): void
    {
        $this->client->request('GET', self::HOST.'/api/sauna-choice');
        self::assertResponseStatusCodeSame(405);

        $this->postJson('/api/sauna-choice', ['choice' => 'Um 21:00 Uhr'], 'https://evil.example');
        self::assertResponseStatusCodeSame(403);

        // Ohne Origin entscheidet Sec-Fetch-Site; fehlen beide (ältere iPhones), geht es durch
        $this->client->request('POST', self::HOST.'/api/sauna-choice', server: ['HTTP_SEC_FETCH_SITE' => 'cross-site'], content: '{"choice":"Um 21:00 Uhr"}');
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', self::HOST.'/api/sauna-choice', server: ['HTTP_SEC_FETCH_SITE' => 'same-origin'], content: '{"choice":"Um 21:00 Uhr"}');
        self::assertResponseIsSuccessful();
        $this->client->request('POST', self::HOST.'/api/sauna-choice', content: '{"choice":"Um 21:00 Uhr"}');
        self::assertResponseIsSuccessful();
    }

    /**
     * @param array<string, mixed> $body
     *
     * @return array<string, mixed>
     */
    private function postJson(string $url, array $body, string $origin = self::HOST): array
    {
        $this->client->request('POST', str_starts_with($url, 'https://') ? $url : self::HOST.$url, server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ORIGIN' => $origin,
        ], content: json_encode($body, \JSON_THROW_ON_ERROR));

        return json_decode((string) $this->client->getResponse()->getContent(), true) ?? [];
    }
}
