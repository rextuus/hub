<?php

namespace App\Tool\BetAI\Entity;

use App\Tool\BetAI\Repository\AiResponseRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AiResponseRepository::class)]
#[ORM\Table(name: 'bet_ai_ai_response')]
class AiResponse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: GameWeek::class)]
    #[ORM\JoinColumn(nullable: false)]
    public GameWeek $gameWeek;

    #[ORM\Column(type: 'text')]
    public string $rawResponse;

    #[ORM\Column]
    public bool $hasValidData = false;

    #[ORM\Column(type: 'datetime')]
    public \DateTime $createdAt;

    public function __construct(GameWeek $gameWeek, string $rawResponse, bool $hasValidData = false)
    {
        $this->gameWeek = $gameWeek;
        $this->rawResponse = $rawResponse;
        $this->hasValidData = $hasValidData;
        $this->createdAt = new \DateTime();
    }
}
