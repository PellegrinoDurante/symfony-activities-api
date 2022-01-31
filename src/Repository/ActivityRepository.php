<?php

namespace App\Repository;

use App\Entity\Activity;
use App\Entity\User;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Activity|null find($id, $lockMode = null, $lockVersion = null)
 * @method Activity|null findOneBy(array $criteria, array $orderBy = null)
 * @method Activity[]    findAll()
 * @method Activity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method findByMedia($null)
 */
class ActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Activity::class);
    }

    /**
     * Searches and returns all activities matching the given filters.
     *
     * @param string|null $name
     * @param DateTime|null $day
     * @param bool|null $availableOnly
     * @return Activity[]
     */
    public function findAllByFilter(?string $name = null, ?DateTime $day = null, bool $availableOnly = false): array
    {
        $qb = $this->createQueryBuilder('a');

        // Apply name filter
        if ($name != null) {
            $qb = $qb
                ->andWhere('a.name LIKE :name')
                ->setParameter('name', '%' . $name . '%');
        }

        // Apply day filter
        if ($day != null) {
            $from = $day->format('Y-m-d 00:00:00');
            $to = $day->modify('+1 day')->format('Y-m-d 00:00:00');

            $qb = $qb
                ->andWhere('a.startAt < :to AND a.endAt >= :from')
                ->setParameter('from', $from)
                ->setParameter('to', $to);
        }

        // Apply available filter
        if ($availableOnly != null) {
            $qb = $this->andWhereIsAvailable($qb);
        }

        // Get results
        return $qb->getQuery()->getResult();
    }

    /**
     * @param int $id
     * @return Activity|null
     * @throws NonUniqueResultException
     */
    public function findAvailable(int $id): ?Activity
    {
        try {
            $qb = $this->createQueryBuilder('a')
                ->where('a.id = :id')
                ->setParameter('id', $id);

            return $this->andWhereIsAvailable($qb)
                ->getQuery()
                ->getSingleResult();

        } catch (NoResultException $e) {
            return null;
        }
    }

    public function findActivitiesByUser(User $user)
    {
        return $this->createQueryBuilder('a')
            ->innerJoin('a.users', 'u')
            ->where('u = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    private function andWhereIsAvailable(QueryBuilder $qb): QueryBuilder
    {
        $now = new DateTime();

        return $qb
            ->andWhere('a.availableSeats > a.occupiedSeats')
            ->andWhere('a.endAt > :now')
            ->setParameter('now', $now);

    }
}
