<?php

namespace App\Tool\BetAI\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'bet_ai_setting')]
class BetAISetting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(name: 'setting_key', length: 255, unique: true)]
    public string $key;

    #[ORM\Column(name: 'setting_value', type: 'text', nullable: true)]
    public ?string $value = null;

    public function __construct(string $key, ?string $value = null)
    {
        $this->key = $key;
        $this->value = $value;
    }
}
