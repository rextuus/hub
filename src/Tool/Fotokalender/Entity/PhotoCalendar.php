<?php

declare(strict_types=1);

namespace App\Tool\Fotokalender\Entity;

use App\Tool\Fotokalender\Repository\PhotoCalendarRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/** Ein Foto-Kalender (ein Jahr), z. B. „Foto-Kalender 2022“. */
#[ORM\Entity(repositoryClass: PhotoCalendarRepository::class)]
#[ORM\Table(name: 'fotokalender_calendar')]
#[UniqueEntity('year', message: 'Für dieses Jahr gibt es schon einen Kalender.')]
class PhotoCalendar
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(unique: true)]
    #[Assert\Range(min: 2000, max: 2100)]
    private int $year;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $intro = null;

    /** Unveröffentlichte Kalender sieht nur der Admin. */
    #[ORM\Column(options: ['default' => true])]
    private bool $published = true;

    /** @var Collection<int, PhotoCalendarMonth> */
    #[ORM\OneToMany(targetEntity: PhotoCalendarMonth::class, mappedBy: 'calendar', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['month' => 'ASC'])]
    private Collection $months;

    public function __construct(int $year = 2022, string $title = '')
    {
        $this->year = $year;
        $this->title = $title;
        $this->months = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getYear(): int { return $this->year; }

    public function setYear(int $year): static { $this->year = $year; return $this; }

    public function getTitle(): string { return $this->title; }

    public function setTitle(?string $title): static { $this->title = (string) $title; return $this; }

    public function getIntro(): ?string { return $this->intro; }

    public function setIntro(?string $intro): static { $this->intro = $intro; return $this; }

    public function isPublished(): bool { return $this->published; }

    public function setPublished(bool $published): static { $this->published = $published; return $this; }

    /** @return Collection<int, PhotoCalendarMonth> */
    public function getMonths(): Collection { return $this->months; }

    public function getMonth(int $month): ?PhotoCalendarMonth
    {
        return $this->months->findFirst(static fn (int $k, PhotoCalendarMonth $m): bool => $m->getMonth() === $month);
    }

    public function addMonth(PhotoCalendarMonth $month): static
    {
        if (!$this->months->contains($month)) {
            $this->months->add($month);
            $month->setCalendar($this);
        }

        return $this;
    }

    public function removeMonth(PhotoCalendarMonth $month): static
    {
        $this->months->removeElement($month);

        return $this;
    }

    public function __toString(): string
    {
        return $this->title ?: 'Kalender '.$this->year;
    }
}
