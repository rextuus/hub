<?php

declare(strict_types=1);

namespace App\Tool\Advent\Entity;

use App\Tool\Advent\Repository\AdventDoorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/** Eine Tür (1–24) mit ihrer Frage und den Antwortmöglichkeiten. */
#[ORM\Entity(repositoryClass: AdventDoorRepository::class)]
#[ORM\Table(name: 'advent_door')]
#[ORM\UniqueConstraint(name: 'uniq_advent_door_number', columns: ['calendar_id', 'number'])]
#[UniqueEntity(['calendar', 'number'], message: 'Diese Tür gibt es in dem Kalender schon.', errorPath: 'number')]
class AdventDoor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'doors')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?AdventCalendar $calendar = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\Range(min: 1, max: AdventCalendar::DOORS)]
    private int $number = 1;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private string $question = '';

    /** Bild zur Frage im Medienordner (advent/…). */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    /** Punkte für die richtige Antwort. */
    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 1])]
    #[Assert\Range(min: 0, max: 100)]
    private int $points = 1;

    /** @var Collection<int, AdventChoice> */
    #[ORM\OneToMany(targetEntity: AdventChoice::class, mappedBy: 'door', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    #[Assert\Valid]
    #[Assert\Count(min: 2, minMessage: 'Eine Tür braucht mindestens zwei Antworten.')]
    private Collection $choices;

    public function __construct()
    {
        $this->choices = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getCalendar(): ?AdventCalendar { return $this->calendar; }

    public function setCalendar(?AdventCalendar $calendar): static { $this->calendar = $calendar; return $this; }

    public function getNumber(): int { return $this->number; }

    public function setNumber(int $number): static { $this->number = $number; return $this; }

    public function getQuestion(): string { return $this->question; }

    public function setQuestion(?string $question): static { $this->question = (string) $question; return $this; }

    public function getImage(): ?string { return $this->image; }

    public function setImage(?string $image): static { $this->image = $image; return $this; }

    public function getPoints(): int { return $this->points; }

    public function setPoints(int $points): static { $this->points = $points; return $this; }

    /** @return Collection<int, AdventChoice> */
    public function getChoices(): Collection { return $this->choices; }

    public function getChoice(int $id): ?AdventChoice
    {
        return $this->choices->findFirst(static fn (int $k, AdventChoice $c): bool => $c->getId() === $id);
    }

    public function getCorrectChoice(): ?AdventChoice
    {
        return $this->choices->findFirst(static fn (int $k, AdventChoice $c): bool => $c->isCorrect());
    }

    public function addChoice(AdventChoice $choice): static
    {
        if (!$this->choices->contains($choice)) {
            $this->choices->add($choice);
            $choice->setDoor($this);
        }

        return $this;
    }

    public function removeChoice(AdventChoice $choice): static
    {
        $this->choices->removeElement($choice);

        return $this;
    }

    #[Assert\IsTrue(message: 'Genau eine Antwort muss als richtig markiert sein.')]
    public function hasExactlyOneCorrectChoice(): bool
    {
        return 1 === $this->choices->filter(static fn (AdventChoice $c): bool => $c->isCorrect())->count();
    }

    public function __toString(): string
    {
        return \sprintf('Tür %d (%s)', $this->number, $this->calendar ?? '–');
    }
}
