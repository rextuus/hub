<?php

namespace App\Tool\BetAI\Entity;

use App\Tool\BetAI\Repository\GameWeekRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameWeekRepository::class)]
#[ORM\Table(name: 'bet_ai_gameweek')]
class GameWeek
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $startDate;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $endDate;

    #[ORM\Column(length: 20)]
    private string $status = 'PLANNED';

    public function __construct(string $name, \DateTime $startDate, \DateTime $endDate, string $status = 'PLANNED')
    {
        $this->name = $name;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->status = $status;
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

    public function getStartDate(): \DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTime $startDate): self
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): \DateTime
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTime $endDate): self
    {
        $this->endDate = $endDate;
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

    public function __toString(): string
    {
        return $this->name;
    }
}
