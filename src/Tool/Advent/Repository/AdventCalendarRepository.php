<?php

declare(strict_types=1);

namespace App\Tool\Advent\Repository;

use App\Tool\Advent\Entity\AdventCalendar;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdventCalendar>
 */
class AdventCalendarRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdventCalendar::class);
    }

    public function findOneByHost(string $host): ?AdventCalendar
    {
        return $this->findOneBy(['host' => strtolower($host)]);
    }

    /** @return list<string> alle eigenen Domains von Kalendern */
    public function findHosts(): array
    {
        return array_column($this->createQueryBuilder('c')->select('c.host')->andWhere('c.host IS NOT NULL')->getQuery()->getScalarResult(), 'host');
    }
}
