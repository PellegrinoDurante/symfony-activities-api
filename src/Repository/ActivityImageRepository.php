<?php

namespace App\Repository;

use App\Entity\ActivityMedia;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ActivityMedia|null find($id, $lockMode = null, $lockVersion = null)
 * @method ActivityMedia|null findOneBy(array $criteria, array $orderBy = null)
 * @method ActivityMedia[]    findAll()
 * @method ActivityMedia[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ActivityImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityMedia::class);
    }
}
