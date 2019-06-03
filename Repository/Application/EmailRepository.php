<?php

namespace Eckinox\Repository\Application;

use Eckinox\Entity\Application\Email;
use Eckinox\Entity\Application\Connection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class EmailRepository extends ServiceEntityRepository
{
    use \Eckinox\Library\Symfony\repository;

    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Email::class);
    }

    public function getList($page, $maxResults = 20, $search = []) {
        $firstResult = ($page - 1) * $maxResults;

        $query = $this->createQueryBuilder('e')
            ->orderBy('e.createdAt', 'DESC')
            ->andWhere("e.sentAt != :sent or e.sentAt is null")
            ->setParameter('sent', '2000-01-01 00:00:00')
            ->setFirstResult($firstResult)
            ->setMaxResults($maxResults);

        $this->search($query, $search, 'e');

        $query->andWhere("e.status != 'deleted'");

        return $query->getQuery()->getResult();
    }

    public function getTemplates($page, $maxResults = 20) {
        $firstResult = ($page - 1) * $maxResults;

        return $this->createQueryBuilder('e')
            ->orderBy('e.createdAt', 'DESC')
            ->setFirstResult($firstResult)
            ->setMaxResults($maxResults)
            ->andWhere("e.templateName != ''")
            ->getQuery()->getResult();
    }

    public function getUnsent($maxResults = 5, $olderThan = null) {
        $query = $this->createQueryBuilder('e')
            ->where('e.sentAt is null')
            ->andWhere('e.draft != 1 or e.draft is null');

        if ($olderThan) {
            $query->andWhere('e.updatedAt <= :date')
                ->setParameter('date', $olderThan);
        }

        // TODO: causing bugs ... emails aren't loaded
        //->andWhere("( ( SELECT count(c.id) FROM App\Entity\Application\Connection c WHERE c.url LIKE CONCAT('%/email/edit/', e.id, '%') ) = 0 )")

        $query->orderBy('e.createdAt', 'ASC')
            ->setMaxResults($maxResults)
            ->getQuery()->getResult();

        return $query->getQuery()->getResult();
    }

    public function getCount($search = []) {
        $query = $this->createQueryBuilder('e')
            ->select('count(e)')
            ->andWhere("e.status <> 'deleted'");

        $this->search($query, $search, 'e');

        return $query->getQuery()
            ->getSingleScalarResult();
    }

    public function getCountTemplates($search = []) {
        $query = $this->createQueryBuilder('e')
            ->select('count(e)')
            ->andWhere("e.sentAt like '2000-01-01 00:00:00'");

      $this->search($query, $search, 'e');

        return $query->getQuery()
            ->getSingleScalarResult();
    }

    public function getTemplateByName($templateName){
        return $this->createQueryBuilder('e')
            ->orderBy('e.createdAt', 'ASC')
            ->andWhere('e.templateName = :templateName')
            ->setParameter('templateName', $templateName)
            ->setMaxResults(1)
            ->getQuery()->getSingleResult();
    }
}
