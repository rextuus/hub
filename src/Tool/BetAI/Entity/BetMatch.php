<?php

namespace App\Tool\BetAI\Entity;

use App\Tool\BetAI\Repository\BetMatchRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BetMatchRepository::class)]
#[ORM\Table(name: 'bet_ai_match')]
class BetMatch
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: GameWeek::class)]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private GameWeek $gameWeek,

        #[ORM\ManyToOne(targetEntity: Team::class)]
        #[ORM\JoinColumn(nullable: true)]
        private ?Team $homeTeam = null,

        #[ORM\ManyToOne(targetEntity: Team::class)]
        #[ORM\JoinColumn(nullable: true)]
        private ?Team $awayTeam = null,

        #[ORM\Column(length: 255)]
        private string $rawHomeTeamName,

        #[ORM\Column(length: 255)]
        private string $rawAwayTeamName,

        #[ORM\Column(type: 'datetime')]
        private \DateTimeInterface $matchDate,

        #[ORM\Column(length: 20)]
        private string $status = 'SCHEDULED',

        #[ORM\Column(nullable: true)]
        private ?int $resultHome = null,

        #[ORM\Column(nullable: true)]
        private ?int $resultAway = null,

        #[ORM\Column(nullable: true)]
        private ?int $resultHomeHt = null,

        #[ORM\Column(nullable: true)]
        private ?int $resultAwayHt = null,
    ) {
    }

    public function getGameWeek(): GameWeek
    {
        return $this->gameWeek;
    }

    public function setGameWeek(GameWeek $gameWeek): self
    {
        $this->gameWeek = $gameWeek;
        return $this;
    }

    public function getHomeTeam(): ?Team
    {
        return $this->homeTeam;
    }

    public function setHomeTeam(?Team $homeTeam): self
    {
        $this->homeTeam = $homeTeam;
        return $this;
    }

    public function getAwayTeam(): ?Team
    {
        return $this->awayTeam;
    }

    public function setAwayTeam(?Team $awayTeam): self
    {
        $this->awayTeam = $awayTeam;
        return $this;
    }

    public function getRawHomeTeamName(): string
    {
        return $this->rawHomeTeamName;
    }

    public function setRawHomeTeamName(string $rawHomeTeamName): self
    {
        $this->rawHomeTeamName = $rawHomeTeamName;
        return $this;
    }

    public function getRawAwayTeamName(): string
    {
        return $this->rawAwayTeamName;
    }

    public function setRawAwayTeamName(string $rawAwayTeamName): self
    {
        $this->rawAwayTeamName = $rawAwayTeamName;
        return $this;
    }

    public function getMatchDate(): \DateTimeInterface
    {
        return $this->matchDate;
    }

    public function setMatchDate(\DateTimeInterface $matchDate): self
    {
        $this->matchDate = $matchDate;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getResultHome(): ?int
    {
        return $this->resultHome;
    }

    public function setResultHome(?int $resultHome): self
    {
        $this->resultHome = $resultHome;
        return $this;
    }

    public function getResultAway(): ?int
    {
        return $this->resultAway;
    }

    public function setResultAway(?int $resultAway): self
    {
        $this->resultAway = $resultAway;
        return $this;
    }

    public function getResultHomeHt(): ?int
    {
        return $this->resultHomeHt;
    }

    public function setResultHomeHt(?int $resultHomeHt): self
    {
        $this->resultHomeHt = $resultHomeHt;
        return $this;
    }

    public function getResultAwayHt(): ?int
    {
        return $this->resultAwayHt;
    }

    public function setResultAwayHt(?int $resultAwayHt): self
    {
        $this->resultAwayHt = $resultAwayHt;
        return $this;
    }
}
