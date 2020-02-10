<?php

namespace Eckinox\Library\Symfony\Doctrine;

use Eckinox\Library\Symfony\EventListener\TranslationsListener;
use Eckinox\Library\Symfony\EventSubscriber\LocaleSubscriber;
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
        if (static::isEnabled()) {
            static::$em->getFilters()->disable('localeFilter');
        }
    }

    public static function enable() {
        $filter = static::$em->getFilters()->enable('localeFilter');

        # When the filter is disabled, all of its parameters are cleared, so we need to set them again.
        $filter->setParameter('locale', static::getLocale());
    }

    public static function isEnabled() {
        return isset(static::$em->getFilters()->getEnabledFilters()['localeFilter']);
    }

    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        # If the target entity isn't translatable, skip the locale filtering
        if (!TranslationsListener::isClassTranslatableEntity($targetEntity->getName())) {
            return '';
        }

        # The only way to pass data dynamically to this method is the via the setParameter() and getParameter() methods
        # Anything else (static properties and such) will be rendered useless, as the method is cached (except for the parameters) in the production environment.
        $locale = $this->getParameter('locale');

        return sprintf('%1$s.locale = %2$s OR %1$s.locale = "" OR %1$s.locale IS NULL', $targetTableAlias, $locale);
    }
}
