<?php

namespace App\Tool\EscVoting\EventSubscriber;

use App\Tool\EscVoting\Entity\EscEdition;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Doctrine\ORM\EntityManagerInterface;

class EscEditionSubscriber implements EventSubscriberInterface
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeEntityPersistedEvent::class => ['handleActiveEdition'],
            BeforeEntityUpdatedEvent::class => ['handleActiveEdition'],
        ];
    }

    public function handleActiveEdition(BeforeEntityPersistedEvent|BeforeEntityUpdatedEvent $event): void
    {
        $entity = $event->getEntityInstance();

        if (!($entity instanceof EscEdition)) {
            return;
        }

        if ($entity->isActive()) {
            $this->deactivateOtherEditions($entity);
        }
    }

    private function deactivateOtherEditions(EscEdition $currentEdition): void
    {
        $repository = $this->entityManager->getRepository(EscEdition::class);
        $activeEditions = $repository->findBy(['isActive' => true]);

        foreach ($activeEditions as $edition) {
            if ($edition->getId() !== $currentEdition->getId()) {
                $edition->setIsActive(false);
            }
        }

        // We don't call flush() here because EasyAdmin will flush after the event
    }
}
