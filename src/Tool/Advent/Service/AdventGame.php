<?php

declare(strict_types=1);

namespace App\Tool\Advent\Service;

use App\Tool\Advent\Entity\AdventAnswer;
use App\Tool\Advent\Entity\AdventCalendar;
use App\Tool\Advent\Entity\AdventDoor;
use App\Tool\Advent\Entity\AdventParticipant;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

/**
 * Spielregeln, alle serverseitig: Nur offene Türen, nur Antworten dieser Tür, je Tür genau eine Antwort.
 * Was die Oberfläche anzeigt, ist dafür egal – ein manipulierter Request kommt hier nicht durch.
 */
final readonly class AdventGame
{
    public function __construct(
        private DoorUnlockPolicy $unlock,
        private EntityManagerInterface $em,
        private ClockInterface $clock,
    ) {
    }

    /**
     * Tür, die der Teilnehmer jetzt öffnen darf, sonst null (gibt es nicht, ist zu, gehört zu einem anderen Kalender).
     */
    public function openableDoor(AdventParticipant $participant, int $number): ?AdventDoor
    {
        $calendar = $participant->getCalendar();
        if (null === $calendar || !$this->unlock->isUnlocked($calendar, $number)) {
            return null;
        }

        return $calendar->getDoor($number);
    }

    /**
     * @throws AdventGameException wenn die Tür zu ist, die Antwort nicht dazugehört oder schon geantwortet wurde
     */
    public function answer(AdventParticipant $participant, int $doorNumber, int $choiceId): AdventAnswer
    {
        $door = $this->openableDoor($participant, $doorNumber)
            ?? throw new AdventGameException('Diese Tür ist noch zu.');
        if (null !== $participant->getAnswerFor($door)) {
            throw new AdventGameException('Diese Tür hast du schon beantwortet.');
        }
        $choice = $door->getChoice($choiceId)
            ?? throw new AdventGameException('Diese Antwort gehört nicht zu der Tür.');

        $answer = new AdventAnswer($participant, $door, $choice, $this->clock->now());
        $this->em->persist($answer);
        try {
            $this->em->flush();
        } catch (UniqueConstraintViolationException) {
            // Doppelklick bzw. zwei Geräte gleichzeitig: Der erste Eintrag gilt.
            throw new AdventGameException('Diese Tür hast du schon beantwortet.');
        }

        return $answer;
    }

    public function touch(AdventParticipant $participant): void
    {
        $now = $this->clock->now();
        $last = $participant->getLastSeenAt();
        if (null === $last || $now->getTimestamp() - $last->getTimestamp() > 300) {
            $participant->setLastSeenAt($now);
            $this->em->flush();
        }
    }

    /**
     * Türen in fester, aber gemischter Reihenfolge je Kalender (wie bei einem echten Adventskalender).
     *
     * @return list<int>
     */
    public static function doorOrder(AdventCalendar $calendar): array
    {
        $numbers = range(1, AdventCalendar::DOORS);
        $seed = crc32($calendar->getYear().'|'.$calendar->getId().'|'.$calendar->getCreatedAt()->format('U'));

        return new \Random\Randomizer(new \Random\Engine\Mt19937($seed))->shuffleArray($numbers);
    }

    /** Nicht erreichte Gewinne erscheinen verwürfelt (wie im alten Kalender) – je Tag gleich, damit es nicht flackert. */
    public static function scramble(string $text, int $seed): string
    {
        $chars = mb_str_split($text);

        return implode('', new \Random\Randomizer(new \Random\Engine\Mt19937($seed))->shuffleArray($chars));
    }
}
