<?php

declare(strict_types=1);

namespace App\Tool\Fotokalender\Entity;

use App\Tool\Fotokalender\Repository\PhotoCalendarPhotoRepository;
use Doctrine\ORM\Mapping as ORM;

/** Ein weiteres Foto zu einem Monatsblatt. */
#[ORM\Entity(repositoryClass: PhotoCalendarPhotoRepository::class)]
#[ORM\Table(name: 'fotokalender_photo')]
class PhotoCalendarPhoto
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'photos')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?PhotoCalendarMonth $month = null;

    /** Bild im Medienordner (fotokalender/…). */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $caption = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $position = 0;

    public function getId(): ?int { return $this->id; }

    public function getMonth(): ?PhotoCalendarMonth { return $this->month; }

    public function setMonth(?PhotoCalendarMonth $month): static { $this->month = $month; return $this; }

    public function getImage(): ?string { return $this->image; }

    public function setImage(?string $image): static { $this->image = $image; return $this; }

    public function getCaption(): ?string { return $this->caption; }

    public function setCaption(?string $caption): static { $this->caption = $caption; return $this; }

    public function getPosition(): int { return $this->position; }

    public function setPosition(int $position): static { $this->position = $position; return $this; }

    public function __toString(): string
    {
        return (string) ($this->caption ?: basename((string) $this->image));
    }
}
