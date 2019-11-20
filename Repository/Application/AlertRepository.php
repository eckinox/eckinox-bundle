<?php

namespace Eckinox\Repository\Application;

use Eckinox\Entity\Application\Alert;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class AlertRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Alert::class);
    }

    public function getList($page, $maxResults = 20) {
        $firstResult = ($page - 1) * $maxResults;

        return $this->createQueryBuilder('u')
            ->orderBy('u.createdAt', 'DESC')
            ->setFirstResult($firstResult)
            ->setMaxResults($maxResults)
            ->getQuery()->getResult();
    }

    public function getCount() {
        return $this->createQueryBuilder('u')
            ->select('count(u)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
