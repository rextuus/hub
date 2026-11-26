<?php

namespace App\Entity;

use App\Repository\ProjectRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $icon = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $route = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $url = null;

    /** Bild im Medienordner (projekte/…), angezeigt über imgproxy. */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    /** Optionales kurzes Video im Medienordner (projekte/…), ersetzt das Bild auf der Startseite (Standbild = image). */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $video = null;

    /** CSS object-position für den Bildausschnitt, z. B. „30% 50%“. */
    #[ORM\Column(length: 32, nullable: true)]
    private ?string $imagePosition = null;

    /** Groß auf der Startseite (sonst nur in der Liste aller Projekte). */
    #[ORM\Column(options: ['default' => false])]
    private bool $featured = false;

    #[ORM\Column(options: ['default' => 0])]
    private int $position = 0;

    /** Nur für angemeldete Benutzer sichtbar (z. B. BetAI). */
    #[ORM\Column(options: ['default' => false])]
    private bool $requiresLogin = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getRoute(): ?string
    {
        return $this->route;
    }

    public function setRoute(?string $route): static
    {
        $this->route = $route;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getVideo(): ?string
    {
        return $this->video;
    }

    public function setVideo(?string $video): static
    {
        $this->video = $video;

        return $this;
    }

    public function getImagePosition(): ?string
    {
        return $this->imagePosition;
    }

    public function setImagePosition(?string $imagePosition): static
    {
        $this->imagePosition = null !== $imagePosition && preg_match('/^[0-9 .%a-z-]{1,32}$/i', $imagePosition) ? $imagePosition : null;

        return $this;
    }

    public function isFeatured(): bool
    {
        return $this->featured;
    }

    public function setFeatured(bool $featured): static
    {
        $this->featured = $featured;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function isRequiresLogin(): bool
    {
        return $this->requiresLogin;
    }

    public function setRequiresLogin(bool $requiresLogin): static
    {
        $this->requiresLogin = $requiresLogin;

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->name;
    }
}
