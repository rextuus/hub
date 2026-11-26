<?php

declare(strict_types=1);

namespace App\Tool\Advent\Entity;

use App\Tool\Advent\Repository\AdventParticipantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Ein Teilnehmer eines Kalenders, ohne Konto: Der persönliche Link /advent/<token> ist der Zugang
 * (128 Bit Zufall, nicht erratbar). Fortschritt und Punkte hängen am Teilnehmer, nicht an der Frage.
 */
#[ORM\Entity(repositoryClass: AdventParticipantRepository::class)]
#[ORM\Table(name: 'advent_participant')]
class AdventParticipant
{
    public const string TOKEN_PATTERN = '[a-f0-9]{32}';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'participants')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?AdventCalendar $calendar = null;

    #[ORM\Column(length: 80)]
    #[Assert\NotBlank]
    private string $name = '';

    #[ORM\Column(length: 32, unique: true)]
    private string $token;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastSeenAt = null;

    /** @var Collection<int, AdventAnswer> */
    #[ORM\OneToMany(targetEntity: AdventAnswer::class, mappedBy: 'participant', cascade: ['remove'], orphanRemoval: true)]
    private Collection $answers;

    public function __construct(string $name = '')
    {
        $this->name = $name;
        $this->token = self::newToken();
        $this->createdAt = new \DateTimeImmutable();
        $this->answers = new ArrayCollection();
    }

    public static function newToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    public function getId(): ?int { return $this->id; }

    public function getCalendar(): ?AdventCalendar { return $this->calendar; }

    public function setCalendar(?AdventCalendar $calendar): static { $this->calendar = $calendar; return $this; }

    public function getName(): string { return $this->name; }

    public function setName(?string $name): static { $this->name = (string) $name; return $this; }

    public function getToken(): string { return $this->token; }

    /** Neuer Link, der alte wird ungültig (z. B. wenn er in falsche Hände geraten ist). */
    public function regenerateToken(): static { $this->token = self::newToken(); return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getLastSeenAt(): ?\DateTimeImmutable { return $this->lastSeenAt; }

    public function setLastSeenAt(?\DateTimeImmutable $lastSeenAt): static { $this->lastSeenAt = $lastSeenAt; return $this; }

    /** @return Collection<int, AdventAnswer> */
    public function getAnswers(): Collection { return $this->answers; }

    public function getAnswerFor(AdventDoor $door): ?AdventAnswer
    {
        return $this->answers->findFirst(static fn (int $k, AdventAnswer $a): bool => $a->getDoor() === $door);
    }

    public function addAnswer(AdventAnswer $answer): static
    {
        if (!$this->answers->contains($answer)) {
            $this->answers->add($answer);
        }

        return $this;
    }

    public function getPoints(): int
    {
        return array_sum($this->answers->map(static fn (AdventAnswer $a): int => $a->getPoints())->toArray());
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
