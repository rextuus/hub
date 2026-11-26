<?php

declare(strict_types=1);

namespace App\Tool\Advent\Entity;

use App\Tool\Advent\Repository\AdventWinStageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/** Gewinnstufe: ab $minPoints Punkten gibt es $prize. Nicht erreichte Gewinne erscheinen verwürfelt. */
#[ORM\Entity(repositoryClass: AdventWinStageRepository::class)]
#[ORM\Table(name: 'advent_win_stage')]
class AdventWinStage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'winStages')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?AdventCalendar $calendar = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\PositiveOrZero]
    private int $minPoints = 0;

    /** Anzeige der Spanne, z. B. „5–12 Punkte“. */
    #[ORM\Column(length: 80)]
    #[Assert\NotBlank]
    private string $label = '';

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private string $prize = '';

    public function getId(): ?int { return $this->id; }

    public function getCalendar(): ?AdventCalendar { return $this->calendar; }

    public function setCalendar(?AdventCalendar $calendar): static { $this->calendar = $calendar; return $this; }

    public function getMinPoints(): int { return $this->minPoints; }

    public function setMinPoints(int $minPoints): static { $this->minPoints = $minPoints; return $this; }

    public function getLabel(): string { return $this->label; }

    public function setLabel(?string $label): static { $this->label = (string) $label; return $this; }

    public function getPrize(): string { return $this->prize; }

    public function setPrize(?string $prize): static { $this->prize = (string) $prize; return $this; }

    public function isReached(int $points): bool
    {
        return $points >= $this->minPoints;
    }

    public function __toString(): string
    {
        return $this->label.': '.$this->prize;
    }
}
