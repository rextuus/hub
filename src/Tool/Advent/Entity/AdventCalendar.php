<?php

declare(strict_types=1);

namespace App\Tool\Advent\Entity;

use App\Tool\Advent\Repository\AdventCalendarRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Eine Ausgabe des Adventskalenders (z. B. „Janas Adventskalender 2026“) mit 24 Türen.
 * Teilnehmer legt der Admin an; jeder bekommt einen eigenen Link (AdventParticipant::$token).
 */
#[ORM\Entity(repositoryClass: AdventCalendarRepository::class)]
#[ORM\Table(name: 'advent_calendar')]
class AdventCalendar
{
    public const int DOORS = 24;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank]
    private string $title = '';

    #[ORM\Column]
    #[Assert\Range(min: 2000, max: 2100)]
    private int $year;

    /** Tag, an dem Tür 1 aufgeht (00:00 in $timezone); Tür n folgt n−1 Tage später. */
    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $startDate;

    #[ORM\Column(length: 64, options: ['default' => 'Europe/Berlin'])]
    #[Assert\Timezone]
    private string $timezone = 'Europe/Berlin';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $intro = null;

    /**
     * Optional eigene Domain (z. B. advent.example.com). Dann zeigen die Teilnehmer-Links dorthin; „/“ auf dieser
     * Domain begrüßt statt der Startseite. DNS und Caddy auf dem VPS muss es dafür geben (docs/homelab/aufbau.md).
     */
    #[ORM\Column(length: 190, unique: true, nullable: true)]
    #[Assert\Hostname(requireTld: true)]
    private ?string $host = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, AdventDoor> */
    #[ORM\OneToMany(targetEntity: AdventDoor::class, mappedBy: 'calendar', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['number' => \SortDirection::Ascending])]
    private Collection $doors;

    /** @var Collection<int, AdventWinStage> */
    #[ORM\OneToMany(targetEntity: AdventWinStage::class, mappedBy: 'calendar', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['minPoints' => \SortDirection::Ascending])]
    private Collection $winStages;

    /** @var Collection<int, AdventParticipant> */
    #[ORM\OneToMany(targetEntity: AdventParticipant::class, mappedBy: 'calendar', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['name' => \SortDirection::Ascending])]
    private Collection $participants;

    public function __construct(string $title = '', ?int $year = null, ?\DateTimeImmutable $startDate = null)
    {
        $this->title = $title;
        $this->year = $year ?? (int) date('Y');
        $this->startDate = $startDate ?? new \DateTimeImmutable($this->year.'-12-01');
        $this->createdAt = new \DateTimeImmutable();
        $this->doors = new ArrayCollection();
        $this->winStages = new ArrayCollection();
        $this->participants = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getTitle(): string { return $this->title; }

    public function setTitle(?string $title): static { $this->title = (string) $title; return $this; }

    public function getYear(): int { return $this->year; }

    public function setYear(int $year): static { $this->year = $year; return $this; }

    public function getStartDate(): \DateTimeImmutable { return $this->startDate; }

    public function setStartDate(\DateTimeImmutable $startDate): static { $this->startDate = $startDate; return $this; }

    public function getTimezone(): string { return $this->timezone; }

    public function setTimezone(?string $timezone): static { $this->timezone = $timezone ?: 'Europe/Berlin'; return $this; }

    public function getIntro(): ?string { return $this->intro; }

    public function setIntro(?string $intro): static { $this->intro = $intro; return $this; }

    public function getHost(): ?string { return $this->host; }

    public function setHost(?string $host): static
    {
        $host = null !== $host ? strtolower(trim($host)) : null;
        $this->host = '' === $host ? null : $host;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    /** @return Collection<int, AdventDoor> */
    public function getDoors(): Collection { return $this->doors; }

    public function getDoor(int $number): ?AdventDoor
    {
        return $this->doors->findFirst(static fn (int $k, AdventDoor $d): bool => $d->getNumber() === $number);
    }

    public function addDoor(AdventDoor $door): static
    {
        if (!$this->doors->contains($door)) {
            $this->doors->add($door);
            $door->setCalendar($this);
        }

        return $this;
    }

    public function removeDoor(AdventDoor $door): static
    {
        $this->doors->removeElement($door);

        return $this;
    }

    /** @return Collection<int, AdventWinStage> */
    public function getWinStages(): Collection { return $this->winStages; }

    public function addWinStage(AdventWinStage $stage): static
    {
        if (!$this->winStages->contains($stage)) {
            $this->winStages->add($stage);
            $stage->setCalendar($this);
        }

        return $this;
    }

    public function removeWinStage(AdventWinStage $stage): static
    {
        $this->winStages->removeElement($stage);

        return $this;
    }

    /** @return Collection<int, AdventParticipant> */
    public function getParticipants(): Collection { return $this->participants; }

    public function addParticipant(AdventParticipant $participant): static
    {
        if (!$this->participants->contains($participant)) {
            $this->participants->add($participant);
            $participant->setCalendar($this);
        }

        return $this;
    }

    public function removeParticipant(AdventParticipant $participant): static
    {
        $this->participants->removeElement($participant);

        return $this;
    }

    public function getMaxPoints(): int
    {
        return array_sum($this->doors->map(static fn (AdventDoor $d): int => $d->getPoints())->toArray());
    }

    public function __toString(): string
    {
        return $this->title ?: 'Adventskalender '.$this->year;
    }
}
