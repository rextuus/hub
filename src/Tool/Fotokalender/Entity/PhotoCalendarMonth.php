<?php

declare(strict_types=1);

namespace App\Tool\Fotokalender\Entity;

use App\Tool\Fotokalender\Model\Month;
use App\Tool\Fotokalender\Repository\PhotoCalendarMonthRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/** Ein Monatsblatt: Titelbild mit Motto („Schlafmütze“) und weitere Fotos vom Shooting. */
#[ORM\Entity(repositoryClass: PhotoCalendarMonthRepository::class)]
#[ORM\Table(name: 'fotokalender_month')]
#[ORM\UniqueConstraint(name: 'uniq_fotokalender_month', columns: ['calendar_id', 'month'])]
#[UniqueEntity(['calendar', 'month'], message: 'Diesen Monat gibt es in dem Kalender schon.', errorPath: 'month')]
class PhotoCalendarMonth
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'months')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?PhotoCalendar $calendar = null;

    #[ORM\Column(type: 'smallint')]
    #[Assert\Range(min: 1, max: 12)]
    private int $month = 1;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank]
    private string $motto = '';

    /** Titelbild im Medienordner (fotokalender/…). */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cover = null;

    /** @var Collection<int, PhotoCalendarPhoto> */
    #[ORM\OneToMany(targetEntity: PhotoCalendarPhoto::class, mappedBy: 'month', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => \SortDirection::Ascending, 'id' => \SortDirection::Ascending])]
    private Collection $photos;

    public function __construct()
    {
        $this->photos = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getCalendar(): ?PhotoCalendar { return $this->calendar; }

    public function setCalendar(?PhotoCalendar $calendar): static { $this->calendar = $calendar; return $this; }

    public function getMonth(): int { return $this->month; }

    public function setMonth(int $month): static { $this->month = $month; return $this; }

    public function getName(): string { return Month::name($this->month); }

    public function getSlug(): string { return Month::slug($this->month); }

    public function getMotto(): string { return $this->motto; }

    public function setMotto(?string $motto): static { $this->motto = (string) $motto; return $this; }

    public function getCover(): ?string { return $this->cover; }

    public function setCover(?string $cover): static { $this->cover = $cover; return $this; }

    /** @return Collection<int, PhotoCalendarPhoto> */
    public function getPhotos(): Collection { return $this->photos; }

    public function addPhoto(PhotoCalendarPhoto $photo): static
    {
        if (!$this->photos->contains($photo)) {
            $this->photos->add($photo);
            $photo->setMonth($this);
        }

        return $this;
    }

    public function removePhoto(PhotoCalendarPhoto $photo): static
    {
        $this->photos->removeElement($photo);

        return $this;
    }

    /**
     * Alle Bilder der Detailseite: zuerst das Titelbild, dann die Fotos.
     *
     * @return list<array{image: string, caption: ?string}>
     */
    public function getGallery(): array
    {
        $items = null !== $this->cover ? [['image' => $this->cover, 'caption' => $this->motto]] : [];
        foreach ($this->photos as $photo) {
            if (null !== $photo->getImage()) {
                $items[] = ['image' => $photo->getImage(), 'caption' => $photo->getCaption()];
            }
        }

        return $items;
    }

    public function __toString(): string
    {
        return \sprintf('%s %d – %s', $this->getName(), $this->calendar?->getYear() ?? 0, $this->motto);
    }
}
