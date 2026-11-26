<?php

declare(strict_types=1);

namespace App\Tool\Advent\Component;

use App\Tool\Advent\Entity\AdventAnswer;
use App\Tool\Advent\Entity\AdventCalendar;
use App\Tool\Advent\Entity\AdventChoice;
use App\Tool\Advent\Entity\AdventDoor;
use App\Tool\Advent\Entity\AdventParticipant;
use App\Tool\Advent\Repository\AdventCalendarRepository;
use App\Tool\Advent\Repository\AdventParticipantRepository;
use App\Tool\Advent\Service\AdventGame;
use App\Tool\Advent\Service\AdventGameException;
use App\Tool\Advent\Service\DoorUnlockPolicy;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Der Adventskalender eines Teilnehmers (oder die Vorschau für Admins).
 *
 * Alle Props sind nur lesbar (signiert): Der Browser kann weder Teilnehmer noch Tür noch Vorschau-Modus fälschen.
 * Jede Aktion prüft trotzdem erneut, ob die Tür offen ist (DoorUnlockPolicy über AdventGame).
 * Antworttexte, Reaktionen und Bilder werden erst nach dem Antworten gerendert.
 */
#[AsLiveComponent('AdventBoard', template: 'tool/advent/components/board.html.twig')]
final class AdventBoard
{
    use DefaultActionTrait;

    /** Token des Teilnehmers (aus seinem Link). */
    #[LiveProp]
    public string $token = '';

    /** Admin-Vorschau: Kalender-ID; alle Türen offen, nichts wird gespeichert. */
    #[LiveProp]
    public ?int $previewCalendarId = null;

    /** Geöffnete Tür (Dialog). */
    #[LiveProp]
    public ?int $door = null;

    /** Vorschau: zuletzt gewählte Antwort (wird nicht gespeichert). */
    #[LiveProp]
    public ?int $previewChoice = null;

    #[LiveProp]
    public ?string $message = null;

    private ?AdventParticipant $participant = null;
    private ?AdventCalendar $calendar = null;

    public function __construct(
        private readonly AdventParticipantRepository $participants,
        private readonly AdventCalendarRepository $calendars,
        private readonly AdventGame $game,
        private readonly DoorUnlockPolicy $unlock,
        private readonly Security $security,
        private readonly ClockInterface $clock,
    ) {
    }

    public function isPreview(): bool
    {
        return null !== $this->previewCalendarId;
    }

    public function getParticipant(): ?AdventParticipant
    {
        if ($this->isPreview()) {
            return null;
        }

        return $this->participant ??= $this->participants->findOneByToken($this->token)
            ?? throw new NotFoundHttpException();
    }

    public function getCalendar(): AdventCalendar
    {
        if (null !== $this->calendar) {
            return $this->calendar;
        }
        if ($this->isPreview()) {
            if (!$this->security->isGranted('ROLE_ADMIN')) {
                throw new NotFoundHttpException();
            }

            return $this->calendar = $this->calendars->find($this->previewCalendarId) ?? throw new NotFoundHttpException();
        }

        return $this->calendar = $this->getParticipant()?->getCalendar() ?? throw new NotFoundHttpException();
    }

    #[LiveAction]
    public function open(#[LiveArg] int $number): void
    {
        $this->message = null;
        $this->previewChoice = null;
        $this->door = null !== $this->allowedDoor($number) ? $number : null;
        if (null === $this->door) {
            $this->message = 'Geduld! Diese Tür ist noch zu.';
        }
    }

    #[LiveAction]
    public function close(): void
    {
        $this->door = null;
        $this->previewChoice = null;
        $this->message = null;
    }

    #[LiveAction]
    public function answer(#[LiveArg] int $choice): void
    {
        $this->message = null;
        if (null === $this->door) {
            return;
        }
        if ($this->isPreview()) {
            $this->previewChoice = null !== $this->allowedDoor($this->door)?->getChoice($choice) ? $choice : null;

            return;
        }
        $participant = $this->getParticipant();
        if (null === $participant) {
            return;
        }
        try {
            $this->game->answer($participant, $this->door, $choice);
        } catch (AdventGameException $e) {
            $this->message = $e->getMessage();
        }
    }

    /**
     * Alle Türen in Kalender-Reihenfolge mit ihrem Zustand für die Oberfläche.
     *
     * @return list<array{number: int, unlocked: bool, unlocksAt: \DateTimeImmutable, hasContent: bool, answered: bool, correct: ?bool, today: bool}>
     */
    public function getDoors(): array
    {
        $calendar = $this->getCalendar();
        $now = $this->clock->now();
        $today = $now->setTimezone(new \DateTimeZone($calendar->getTimezone()))->format('Y-m-d');
        $doors = [];
        foreach (AdventGame::doorOrder($calendar) as $number) {
            $door = $calendar->getDoor($number);
            $answer = null !== $door ? $this->getParticipant()?->getAnswerFor($door) : null;
            $unlocksAt = $this->unlock->unlocksAt($calendar, $number);
            $doors[] = [
                'number' => $number,
                'unlocked' => $this->isPreview() || $this->unlock->isUnlocked($calendar, $number, $now),
                'unlocksAt' => $unlocksAt,
                'hasContent' => null !== $door,
                'answered' => null !== $answer,
                'correct' => $answer?->isCorrect(),
                'today' => $unlocksAt->format('Y-m-d') === $today,
            ];
        }

        return $doors;
    }

    public function getOpenDoor(): ?AdventDoor
    {
        return null !== $this->door ? $this->allowedDoor($this->door) : null;
    }

    public function getGivenAnswer(): ?AdventAnswer
    {
        $door = $this->getOpenDoor();

        return null !== $door ? $this->getParticipant()?->getAnswerFor($door) : null;
    }

    /** Gewählte Antwort (gespeichert bzw. in der Vorschau), sonst null = noch nicht beantwortet. */
    public function getChosen(): ?AdventChoice
    {
        if ($this->isPreview()) {
            return null !== $this->previewChoice ? $this->getOpenDoor()?->getChoice($this->previewChoice) : null;
        }

        return $this->getGivenAnswer()?->getChoice();
    }

    public function isAnswered(): bool
    {
        return null !== $this->getChosen() || null !== $this->getGivenAnswer();
    }

    public function getPoints(): int
    {
        return $this->getParticipant()?->getPoints() ?? 0;
    }

    public function getOpenedCount(): int
    {
        return $this->getParticipant()?->getAnswers()->count() ?? 0;
    }

    /**
     * @return list<array{label: string, prize: string, reached: bool}>
     */
    public function getStages(): array
    {
        $points = $this->isPreview() ? PHP_INT_MAX : $this->getPoints();
        $seed = (int) $this->clock->now()->format('Ymd');

        return array_map(static fn ($stage): array => [
            'label' => $stage->getLabel(),
            'prize' => $stage->isReached($points) ? $stage->getPrize() : AdventGame::scramble($stage->getPrize(), $seed + $stage->getMinPoints()),
            'reached' => $stage->isReached($points),
        ], $this->getCalendar()->getWinStages()->getValues());
    }

    public function getNextUnlock(): ?\DateTimeImmutable
    {
        return $this->isPreview() ? null : $this->unlock->nextUnlock($this->getCalendar());
    }

    private function allowedDoor(int $number): ?AdventDoor
    {
        if ($this->isPreview()) {
            return $number >= 1 && $number <= AdventCalendar::DOORS ? $this->getCalendar()->getDoor($number) : null;
        }
        $participant = $this->getParticipant();

        return null !== $participant ? $this->game->openableDoor($participant, $number) : null;
    }
}
