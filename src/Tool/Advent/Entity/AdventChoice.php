<?php

declare(strict_types=1);

namespace App\Tool\Advent\Entity;

use App\Tool\Advent\Repository\AdventChoiceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/** Eine Antwortmöglichkeit mit der Reaktion, die nach dem Antworten erscheint (Text, Bild oder YouTube-Video). */
#[ORM\Entity(repositoryClass: AdventChoiceRepository::class)]
#[ORM\Table(name: 'advent_choice')]
class AdventChoice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'choices')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?AdventDoor $door = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $position = 0;

    #[ORM\Column(length: 500)]
    #[Assert\NotBlank]
    private string $text = '';

    #[ORM\Column(options: ['default' => false])]
    private bool $correct = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $response = null;

    /** Bild zur Reaktion im Medienordner (advent/…). */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $responseImage = null;

    /** Optional ein YouTube-Video (Link), eingebettet über youtube-nocookie.com. */
    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Regex(pattern: '#^https://(www\.)?(youtube\.com/(watch\?v=|shorts/|embed/)|youtu\.be/)[\w-]{11}#', message: 'Bitte einen YouTube-Link (youtube.com/watch?v=…, youtu.be/…, …/shorts/…).')]
    private ?string $responseVideoUrl = null;

    public function getId(): ?int { return $this->id; }

    public function getDoor(): ?AdventDoor { return $this->door; }

    public function setDoor(?AdventDoor $door): static { $this->door = $door; return $this; }

    public function getPosition(): int { return $this->position; }

    public function setPosition(int $position): static { $this->position = $position; return $this; }

    public function getText(): string { return $this->text; }

    public function setText(?string $text): static { $this->text = (string) $text; return $this; }

    public function isCorrect(): bool { return $this->correct; }

    public function setCorrect(bool $correct): static { $this->correct = $correct; return $this; }

    public function getResponse(): ?string { return $this->response; }

    public function setResponse(?string $response): static { $this->response = $response; return $this; }

    public function getResponseImage(): ?string { return $this->responseImage; }

    public function setResponseImage(?string $responseImage): static { $this->responseImage = $responseImage; return $this; }

    public function getResponseVideoUrl(): ?string { return $this->responseVideoUrl; }

    public function setResponseVideoUrl(?string $url): static { $this->responseVideoUrl = '' === trim((string) $url) ? null : trim((string) $url); return $this; }

    /** YouTube-Video-ID aus dem Link, sonst null. */
    public function getYoutubeId(): ?string
    {
        if (null !== $this->responseVideoUrl
            && preg_match('#(?:youtube\.com/(?:watch\?(?:.*&)?v=|shorts/|embed/)|youtu\.be/)([\w-]{11})#', $this->responseVideoUrl, $m)) {
            return $m[1];
        }

        return null;
    }

    public function __toString(): string
    {
        return $this->text;
    }
}
