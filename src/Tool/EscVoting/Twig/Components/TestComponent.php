<?php

namespace App\Tool\EscVoting\Twig\Components;

use App\Tool\EscVoting\Entity\TestLiveEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('TestComponent', template: 'tool/esc_voting/components/test_component.html.twig')]
final class TestComponent
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public string $name = '';

    #[LiveProp(writable: true)]
    public int $rating = 0;

    #[LiveProp]
    public string $status = '';

    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function mount(): void
    {
        $entity = $this->entityManager->getRepository(TestLiveEntity::class)->findOneBy([]);
        if ($entity) {
            $this->name = $entity->getName();
            $this->rating = $entity->getRating();
        } else {
            $this->name = 'Default Artist';
            $this->rating = 0;
        }
    }

    #[LiveAction]
    public function setRating(#[LiveArg('rating')] int $rating): void
    {
        if ($this->rating === $rating) {
            $this->rating = 0;
        } else {
            $this->rating = $rating;
        }

        // Auto-save on rating change
        $this->save();
    }

    #[LiveAction]
    public function save(): void
    {
        $entity = $this->entityManager->getRepository(TestLiveEntity::class)->findOneBy([]);
        if (!$entity) {
            $entity = new TestLiveEntity();
            $this->entityManager->persist($entity);
        }

        $entity->setName($this->name);
        $entity->setRating($this->rating);
        $this->entityManager->flush();

        $this->status = 'Saved to database at ' . date('H:i:s');
    }
}
