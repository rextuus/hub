<?php

namespace App\Tool\EscVoting\Entity;

use App\Tool\EscVoting\Repository\ParticipantRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParticipantRepository::class)]
#[ORM\Table(name: 'esc_voting_participant')]
class Participant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Country::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Country $country = null;

    #[ORM\ManyToOne(targetEntity: EscEdition::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?EscEdition $edition = null;

    #[ORM\Column(length: 255)]
    private ?string $artist = null;

    #[ORM\Column(length: 255)]
    private ?string $song = null;

    #[ORM\Column]
    private int $startOrder = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(?Country $country): self
    {
        $this->country = $country;
        return $this;
    }

    public function getEdition(): ?EscEdition
    {
        return $this->edition;
    }

    public function setEdition(?EscEdition $edition): self
    {
        $this->edition = $edition;
        return $this;
    }

    public function getArtist(): ?string
    {
        return $this->artist;
    }

    public function setArtist(string $artist): self
    {
        $this->artist = $artist;
        return $this;
    }

    public function getSong(): ?string
    {
        return $this->song;
    }

    public function setSong(string $song): self
    {
        $this->song = $song;
        return $this;
    }

    public function getStartOrder(): int
    {
        return $this->startOrder;
    }

    public function setStartOrder(int $startOrder): self
    {
        $this->startOrder = $startOrder;
        return $this;
    }

    public function __toString(): string
    {
        return sprintf('%s: %s - %s', $this->country?->name ?? '?', $this->artist, $this->song);
    }
}
