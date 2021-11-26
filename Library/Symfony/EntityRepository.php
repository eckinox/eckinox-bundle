<?php

namespace Eckinox\Library\Symfony;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Eckinox\Library\Symfony\Doctrine\LocaleFilter;
use Doctrine\Persistence\ManagerRegistry;

class EntityRepository extends ServiceEntityRepository
{
    use \Eckinox\Library\Symfony\repository;

    protected static $entityClass = null;
    protected static $defaultSortBy = 'name';

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, static::$entityClass);
    }

    # The find() method is redefined to add locale handling
    public function find($id, $lockMode = null, $lockVersion = null) {
        $reenableFilter = LocaleFilter::isEnabled();
        LocaleFilter::disable();
        $result = parent::find($id, $lockMode, $lockVersion);
        $resultLocale = property_exists($result, 'locale') ? $result->getLocale() : null;

        if ($reenableFilter) {
            LocaleFilter::enable();
        }

        # Check if the current locale is wrong for the specified ID
        if ($resultLocale && $resultLocale != LocaleFilter::getLocale()) {
            # If the target entity is found, but in a different locale, redirect to the translation
            $translation = $result->getTranslation(LocaleFilter::getLocale(), true);

            # @TODO: Improve this URL building to use the current route's configurations instead of a shot-in-the-dark preg_replace
            $newUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $newUrl = preg_replace("#/" . $id . "([/?]?(.*))#", "/" . $translation->getId() . "$1", $newUrl);

            header('Location:' . $newUrl);
            die();
        }

        return $result;
    }

    public function getList($page, $maxResults = 20, $search = []) {
        $firstResult = ($page - 1) * $maxResults;

        $query = $this->createQueryBuilder('e')
            ->orderBy('e.' . static::$defaultSortBy, 'ASC')
            ->setFirstResult($firstResult)
            ->setMaxResults($maxResults);

        # If a "preSearchFilter" method is defined in the entity, it will run before applying the search itself.
        if (method_exists($this, 'preSearchFilter')) {
            $this->preSearchFilter($query, $search, 'e');
        }

        $this->search($query, $search, 'e');

        return $query->getQuery()->getResult();
    }

    public function getCount($search = []) {
        $query = $this->createQueryBuilder('e')
            ->select('count(e)');

        # If a "preSearchFilter" method is defined in the entity, it will run before applying the search itself.
        if (method_exists($this, 'preSearchFilter')) {
            $this->preSearchFilter($query, $search, 'e');
        }

        $this->search($query, $search, 'e');

        return $query->getQuery()
            ->getSingleScalarResult();
    }
}
