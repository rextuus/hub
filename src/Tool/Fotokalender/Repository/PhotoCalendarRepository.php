<?php

declare(strict_types=1);

namespace App\Tool\Fotokalender\Repository;

use App\Tool\Fotokalender\Entity\PhotoCalendar;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PhotoCalendar>
 */
class PhotoCalendarRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PhotoCalendar::class);
    }

    /** @return list<PhotoCalendar> neueste zuerst */
    public function findVisible(bool $includeUnpublished = false): array
    {
        $qb = $this->createQueryBuilder('c')->orderBy('c.year', 'DESC');
        if (!$includeUnpublished) {
            $qb->andWhere('c.published = true');
        }

        return $qb->getQuery()->getResult();
    }

    /** Kalender mit Monaten und Fotos in einer Abfrage. */
    public function findOneWithPhotos(int $year, bool $includeUnpublished = false): ?PhotoCalendar
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.months', 'm')->addSelect('m')
            ->leftJoin('m.photos', 'p')->addSelect('p')
            ->andWhere('c.year = :year')->setParameter('year', $year);
        if (!$includeUnpublished) {
            $qb->andWhere('c.published = true');
        }

        return $qb->getQuery()->getOneOrNullResult();
    }
}
