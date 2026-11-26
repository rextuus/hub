<?php

declare(strict_types=1);

namespace App\Tool\Fotokalender\Repository;

use App\Tool\Fotokalender\Entity\PhotoCalendarMonth;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PhotoCalendarMonth>
 */
class PhotoCalendarMonthRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PhotoCalendarMonth::class);
    }
}
