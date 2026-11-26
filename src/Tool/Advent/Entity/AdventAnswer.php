<?php

declare(strict_types=1);

namespace App\Tool\Advent\Entity;

use App\Tool\Advent\Repository\AdventAnswerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Die Antwort eines Teilnehmers auf eine Tür – genau eine je Tür (Unique-Index). Ob sie richtig war und wie viele
 * Punkte sie brachte, wird beim Antworten festgehalten: Spätere Änderungen an den Antworten im Admin ändern
 * vergebene Punkte nicht.
 */
#[ORM\Entity(repositoryClass: AdventAnswerRepository::class)]
#[ORM\Table(name: 'advent_answer')]
#[ORM\UniqueConstraint(name: 'uniq_advent_answer', columns: ['participant_id', 'door_id'])]
class AdventAnswer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'answers')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private AdventParticipant $participant;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private AdventDoor $door;

    /** Gewählte Antwort; null, falls sie später im Admin gelöscht wurde. */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?AdventChoice $choice;

    #[ORM\Column]
    private bool $correct;

    #[ORM\Column(type: Types::SMALLINT)]
    private int $points;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $answeredAt;

    public function __construct(AdventParticipant $participant, AdventDoor $door, AdventChoice $choice, \DateTimeImmutable $answeredAt)
    {
        $this->participant = $participant;
        $this->door = $door;
        $this->choice = $choice;
        $this->correct = $choice->isCorrect();
        $this->points = $choice->isCorrect() ? $door->getPoints() : 0;
        $this->answeredAt = $answeredAt;
        $participant->addAnswer($this);
    }

    public function getId(): ?int { return $this->id; }

    public function getParticipant(): AdventParticipant { return $this->participant; }

    public function getDoor(): AdventDoor { return $this->door; }

    public function getChoice(): ?AdventChoice { return $this->choice; }

    public function isCorrect(): bool { return $this->correct; }

    public function getPoints(): int { return $this->points; }

    public function getAnsweredAt(): \DateTimeImmutable { return $this->answeredAt; }

    public function __toString(): string
    {
        return \sprintf('%s – Tür %d: %s', $this->participant, $this->door->getNumber(), $this->correct ? 'richtig' : 'falsch');
    }
}
