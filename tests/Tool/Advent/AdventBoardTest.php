<?php

declare(strict_types=1);

namespace App\Tests\Tool\Advent;

use App\Entity\User;
use App\Tool\Advent\Entity\AdventAnswer;
use App\Tool\Advent\Entity\AdventCalendar;
use App\Tool\Advent\Entity\AdventChoice;
use App\Tool\Advent\Entity\AdventDoor;
use App\Tool\Advent\Entity\AdventParticipant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

/**
 * Spielregeln über die echte Live Component (HTTP-Requests an /_components): Freischaltung serverseitig, je
 * Teilnehmer eigener Fortschritt, nichts verraten vor dem Antworten, Vorschau nur für Admins.
 */
final class AdventBoardTest extends WebTestCase
{
    use ClockSensitiveTrait;
    use InteractsWithLiveComponents;

    private const string SECRET_RESPONSE = 'Geheime Reaktion zu Tür 3';

    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private AdventCalendar $calendar;
    private AdventParticipant $jana;
    private AdventParticipant $max;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);

        // 3. Dezember, 10 Uhr in Berlin: Türen 1–3 offen
        self::mockTime('2026-12-03 10:00:00 Europe/Berlin');

        $this->calendar = new AdventCalendar('Test '.bin2hex(random_bytes(4)), 2026, new \DateTimeImmutable('2026-12-01'));
        foreach (range(1, 5) as $n) {
            $door = (new AdventDoor())->setNumber($n)->setQuestion('Frage '.$n);
            $door->addChoice((new AdventChoice())->setText('Richtig '.$n)->setCorrect(true)->setPosition(1)
                ->setResponse(3 === $n ? self::SECRET_RESPONSE : null));
            $door->addChoice((new AdventChoice())->setText('Falsch '.$n)->setPosition(2));
            $this->calendar->addDoor($door);
        }
        $this->calendar->addParticipant($this->jana = new AdventParticipant('Jana'));
        $this->calendar->addParticipant($this->max = new AdventParticipant('Max'));
        $this->em->persist($this->calendar);
        $this->em->flush();
    }

    protected function tearDown(): void
    {
        $this->em->remove($this->em->find(AdventCalendar::class, $this->calendar->getId()));
        $this->em->flush();
        parent::tearDown();
    }

    private function board(AdventParticipant $participant): \Symfony\UX\LiveComponent\Test\TestLiveComponent
    {
        return $this->createLiveComponent('AdventBoard', ['token' => $participant->getToken()], $this->client);
    }

    private function choiceId(int $door, bool $correct): int
    {
        return $this->calendar->getDoor($door)->getChoices()->findFirst(static fn ($k, AdventChoice $c) => $c->isCorrect() === $correct)->getId();
    }

    public function testOpenDoorAndAnswerCorrectly(): void
    {
        $board = $this->board($this->jana)->call('open', ['number' => 3]);

        $html = (string) $board->render();
        self::assertStringContainsString('Frage 3', $html);
        self::assertStringNotContainsString(self::SECRET_RESPONSE, $html, 'Reaktion erst nach dem Antworten');

        $board->call('answer', ['choice' => $this->choiceId(3, true)]);
        $html = (string) $board->render();
        self::assertStringContainsString('Richtig!', $html);
        self::assertStringContainsString(self::SECRET_RESPONSE, $html);

        $this->em->clear();
        self::assertSame(1, $this->em->getRepository(AdventParticipant::class)->find($this->jana->getId())->getPoints());
    }

    public function testLockedDoorCannotBeOpenedOrAnswered(): void
    {
        $board = $this->board($this->jana)->call('open', ['number' => 4]);

        self::assertNull($board->component()->door);
        self::assertStringContainsString('Diese Tür ist noch zu', (string) $board->render());
        self::assertStringNotContainsString('Frage 4', (string) $board->render());
        self::assertSame(0, $this->countAnswers());
    }

    public function testDoorOpensAtMidnight(): void
    {
        self::mockTime('2026-12-03 23:59:59 Europe/Berlin');
        self::assertNull($this->board($this->jana)->call('open', ['number' => 4])->component()->door);

        self::mockTime('2026-12-04 00:00:00 Europe/Berlin');
        self::assertSame(4, $this->board($this->jana)->call('open', ['number' => 4])->component()->door);
    }

    public function testAllDoorsStayOpenInJanuary(): void
    {
        self::mockTime('2027-01-10 12:00:00 Europe/Berlin');

        self::assertSame(5, $this->board($this->jana)->call('open', ['number' => 5])->component()->door);
    }

    public function testEachDoorCanOnlyBeAnsweredOnce(): void
    {
        $board = $this->board($this->jana)->call('open', ['number' => 1]);
        $board->call('answer', ['choice' => $this->choiceId(1, false)]);
        $board->call('answer', ['choice' => $this->choiceId(1, true)]);

        self::assertSame(1, $this->countAnswers());
        $this->em->clear();
        self::assertSame(0, $this->em->getRepository(AdventParticipant::class)->find($this->jana->getId())->getPoints());
    }

    public function testChoiceOfAnotherDoorIsRejected(): void
    {
        $board = $this->board($this->jana)->call('open', ['number' => 1]);
        $board->call('answer', ['choice' => $this->choiceId(2, true)]);

        self::assertSame(0, $this->countAnswers());
        self::assertStringContainsString('gehört nicht zu der Tür', (string) $board->render());
    }

    public function testProgressIsPerParticipant(): void
    {
        $this->board($this->jana)->call('open', ['number' => 2])->call('answer', ['choice' => $this->choiceId(2, true)]);

        $maxBoard = $this->board($this->max)->call('open', ['number' => 2]);
        $html = (string) $maxBoard->render();
        self::assertStringContainsString('Frage 2', $html);
        self::assertStringContainsString('data-live-action-param="answer"', $html, 'Max kann Tür 2 noch beantworten');

        $this->em->clear();
        $repo = $this->em->getRepository(AdventParticipant::class);
        self::assertSame(1, $repo->find($this->jana->getId())->getPoints());
        self::assertSame(0, $repo->find($this->max->getId())->getPoints());
    }

    public function testParticipantPageNeedsValidToken(): void
    {
        $this->client->request('GET', '/advent/'.$this->jana->getToken());
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('X-Robots-Tag', 'noindex, nofollow');

        $this->client->request('GET', '/advent/'.str_repeat('0', 32));
        self::assertResponseStatusCodeSame(404);
    }

    public function testPreviewOnlyForAdmins(): void
    {
        $this->client->request('GET', '/admin/advent/'.$this->calendar->getId().'/vorschau');
        self::assertResponseRedirects();

        $admin = $this->admin();
        $this->client->loginUser($admin);
        $this->client->request('GET', '/admin/advent/'.$this->calendar->getId().'/vorschau');
        self::assertResponseIsSuccessful();

        // Vorschau: auch Tür 5 (noch zu) öffnen und „antworten“ – ohne zu speichern
        $board = $this->createLiveComponent('AdventBoard', ['previewCalendarId' => $this->calendar->getId()], $this->client)->actingAs($admin);
        $board->call('open', ['number' => 5])->call('answer', ['choice' => $this->choiceId(5, true)]);
        self::assertStringContainsString('Richtig!', (string) $board->render());
        self::assertSame(0, $this->countAnswers());

        $this->em->remove($this->em->find(User::class, $admin->getId()));
        $this->em->flush();
    }

    private function countAnswers(): int
    {
        return $this->em->getRepository(AdventAnswer::class)->count(['participant' => [$this->jana->getId(), $this->max->getId()]]);
    }

    private function admin(): User
    {
        $user = new User();
        $user->setEmail('admin-'.bin2hex(random_bytes(4)).'@example.test');
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword('x');
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
}
