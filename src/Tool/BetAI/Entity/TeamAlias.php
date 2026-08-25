<?php

namespace App\Tool\BetAI\Entity;

use App\Tool\BetAI\Repository\TeamAliasRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeamAliasRepository::class)]
#[ORM\Table(name: 'bet_ai_dictionary')]
class TeamAlias
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private string $rawName;

    #[ORM\ManyToOne(targetEntity: Team::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Team $team;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRawName(): string
    {
        return $this->rawName;
    }

    public function setRawName(string $rawName): self
    {
        $this->rawName = $rawName;
        return $this;
    }

    public function getTeam(): Team
    {
        return $this->team;
    }

    public function setTeam(Team $team): self
    {
        $this->team = $team;
        return $this;
    }
}
