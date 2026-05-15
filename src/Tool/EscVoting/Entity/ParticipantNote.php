<?php

namespace App\Tool\EscVoting\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'esc_voting_participant_note')]
class ParticipantNote
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Voter::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Voter $voter = null;

    #[ORM\ManyToOne(targetEntity: Participant::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Participant $participant = null;

    #[ORM\Column(nullable: true)]
    private ?int $ratingSong = null;

    #[ORM\Column(nullable: true)]
    private ?int $ratingPerformance = null;

    #[ORM\Column(nullable: true)]
    private ?int $ratingVoice = null;

    #[ORM\Column(nullable: true)]
    private ?int $ratingOutfit = null;

    #[ORM\Column(nullable: true)]
    private ?int $ratingOverall = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $note = null;

    #[ORM\Column]
    private bool $isMissed = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVoter(): ?Voter
    {
        return $this->voter;
    }

    public function setVoter(?Voter $voter): self
    {
        $this->voter = $voter;
        return $this;
    }

    public function getParticipant(): ?Participant
    {
        return $this->participant;
    }

    public function setParticipant(?Participant $participant): self
    {
        $this->participant = $participant;
        return $this;
    }

    public function getRatingSong(): ?int
    {
        return $this->ratingSong;
    }

    public function setRatingSong(?int $ratingSong): self
    {
        $this->ratingSong = $ratingSong;
        return $this;
    }

    public function getRatingPerformance(): ?int
    {
        return $this->ratingPerformance;
    }

    public function setRatingPerformance(?int $ratingPerformance): self
    {
        $this->ratingPerformance = $ratingPerformance;
        return $this;
    }

    public function getRatingVoice(): ?int
    {
        return $this->ratingVoice;
    }

    public function setRatingVoice(?int $ratingVoice): self
    {
        $this->ratingVoice = $ratingVoice;
        return $this;
    }

    public function getRatingOutfit(): ?int
    {
        return $this->ratingOutfit;
    }

    public function setRatingOutfit(?int $ratingOutfit): self
    {
        $this->ratingOutfit = $ratingOutfit;
        return $this;
    }

    public function getRatingOverall(): ?int
    {
        return $this->ratingOverall;
    }

    public function setRatingOverall(?int $ratingOverall): self
    {
        $this->ratingOverall = $ratingOverall;
        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): self
    {
        $this->note = $note;
        return $this;
    }

    public function isMissed(): bool
    {
        return $this->isMissed;
    }

    public function setIsMissed(bool $isMissed): self
    {
        $this->isMissed = $isMissed;
        return $this;
    }
}
