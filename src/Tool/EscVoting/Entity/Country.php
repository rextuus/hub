<?php

namespace App\Tool\EscVoting\Entity;

use App\Tool\EscVoting\Repository\CountryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CountryRepository::class)]
#[ORM\Table(name: 'esc_voting_country')]
class Country
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    public function __construct(
        #[ORM\Column(length: 255)]
        public string $name,

        #[ORM\Column(length: 10)]
        public string $countryCode,

        #[ORM\Column]
        public int $startOrder,
    ) {
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
