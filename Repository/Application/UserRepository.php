<?php

namespace Eckinox\Repository\Application;

use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository implements UserLoaderInterface
{
    use \Eckinox\Library\Symfony\repository;

    public function loadUserByIdentifier($identifier): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.username = :username OR u.email = :email')
            ->setParameter('username', $identifier)
            ->setParameter('email', $identifier)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function loadUserByUsername($username)
    {
        return $this->createQueryBuilder('u')
            ->where('u.username = :username OR u.email = :email')
            ->setParameter('username', $username)
            ->setParameter('email', $username)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getList($page, $maxResults = 20, $search = []) {
        $firstResult = ($page - 1) * $maxResults;

        $query = $this->createQueryBuilder('u')
            ->orderBy('u.fullName', 'ASC')
            ->setFirstResult($firstResult)
            ->setMaxResults($maxResults);

        $this->search($query, $search, 'u');

        $query->andWhere("u.status <> 'deleted'");

        return $query->getQuery()->getResult();
    }

    public function getCount($search = []) {
        $query = $this->createQueryBuilder('u')
            ->select('count(u)')
            ->andWhere("u.status <> 'deleted'");

        $this->search($query, $search, 'u');

        return $query->getQuery()
            ->getSingleScalarResult();
    }

    public function getSelectable() {
        $users_list = [];

        $users = $this->createQueryBuilder('u')
            ->orderBy('u.fullName', 'ASC')
            /*->where("u.status <> 'deleted'")*/
            ->getQuery()->getResult();

        foreach($users as $user) {
            $users_list[$user->getId()] = $user->getFullName();
        }

        return $users_list;
    }

    public function getSelectableEmail() {
        $users_list = [];

        $users = $this->createQueryBuilder('u')
            ->orderBy('u.fullName', 'ASC')
            ->where("u.status <> 'deleted'")
            ->getQuery()->getResult();

        foreach($users as $user) {
            $users_list[$user->getEmail()] = $user->getFullName() . ' <' . $user->getEmail() . '>';
        }

        return $users_list;
    }
}
