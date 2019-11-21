<?php

namespace Eckinox\Library\Symfony\Doctrine;

use Eckinox\Library\Symfony\EventListener\TranslationsListener;;
use Doctrine\ORM\Mapping\ClassMetaData;
use Doctrine\ORM\Query\Filter\SQLFilter;

class LocaleFilter extends SQLFilter
{
    protected static $em = null;
    protected static $locale = null;

    public static function setEntityManager($em) {
        static::$em = $em;
    }

    protected static function getEntityManager() {
        return static::$em;
    }

    public static function setLocale($locale) {
        static::$locale = $locale;
    }

    public static function getLocale() {
        return static::$locale;
    }

    public static function disable() {
        static::$em->getFilters()->disable('localeFilter');
    }

    public static function enable() {
        static::$em->getFilters()->enable('localeFilter');
    }

    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        # If the target entity isn't translatable, skip the locale filtering
        if (!TranslationsListener::isClassTranslatableEntity($targetEntity->getName())) {
            return '';
        }

        $locale = $this->getParameter('locale');

        return sprintf('%1$s.locale = %2$s OR %1$s.locale = "" OR %1$s.locale IS NULL', $targetTableAlias, $locale);
    }
}
