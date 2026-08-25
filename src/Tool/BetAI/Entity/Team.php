<?php

namespace App\Tool\BetAI\Entity;

use App\Tool\BetAI\Repository\TeamRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeamRepository::class)]
#[ORM\Table(name: 'bet_ai_team')]
class Team
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function __construct(
        #[ORM\Column(length: 255)]
        private string $name,

        #[ORM\ManyToOne(targetEntity: League::class)]
        #[ORM\JoinColumn(nullable: true)]
        private ?League $league = null,

        #[ORM\Column]
        private bool $isActive = true,

        #[ORM\Column(length: 255, nullable: true)]
        private ?string $profileImgUrl = null,

        #[ORM\Column(length: 255, nullable: true)]
        private ?string $wikipediaUrl = null,

        #[ORM\Column(length: 255, nullable: true)]
        private ?string $logoSearchUrl = null,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getLeague(): ?League
    {
        return $this->league;
    }

    public function setLeague(?League $league): self
    {
        $this->league = $league;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getProfileImgUrl(): ?string
    {
        return $this->profileImgUrl;
    }

    public function setProfileImgUrl(?string $profileImgUrl): self
    {
        $this->profileImgUrl = $profileImgUrl;
        return $this;
    }

    public function getWikipediaUrl(): ?string
    {
        return $this->wikipediaUrl;
    }

    public function setWikipediaUrl(?string $wikipediaUrl): self
    {
        $this->wikipediaUrl = $wikipediaUrl;
        return $this;
    }

    public function getLogoSearchUrl(): ?string
    {
        return $this->logoSearchUrl;
    }

    public function setLogoSearchUrl(?string $logoSearchUrl): self
    {
        $this->logoSearchUrl = $logoSearchUrl;
        return $this;
    }
}
