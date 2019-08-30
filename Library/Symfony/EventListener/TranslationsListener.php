<?php

namespace Eckinox\Library\Symfony\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;

class TranslationsListener
{
    private $container;
    private $request;
    private $em;

    public function __construct(ContainerInterface $container, RequestStack $requestStack, EntityManager $em) {
        $this->container = $container;
        $this->request = $requestStack->getCurrentRequest();
        $this->em = $em;
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs) {
        $metadata = $eventArgs->getClassMetadata();

        if (!static::isClassTranslatableEntity($metadata->getName())) {
            return;
        }

        $metadata->mapManyToMany([
            'targetEntity' => $metadata->getName(),
            'fieldName' => 'translations',
            'cascade' => ['persist', 'remove'],
            'joinTable' => ['name' => 'translations' . ucfirst($metadata->getTableName())]
        ]);
    }

    public function shouldProcess($entity, $type) {
        static $handledIds = [
            'save' => [],
            'remove' => [],
        ];

        $entityClass = get_class($entity);

        # Check if the entity is a translatable entity
        if (!static::isClassTranslatableEntity($entityClass)) {
            return false;
        }

        # Check for "manual" creation/deletion types
        if (($type == 'remove' && $entity->getTranslationDeletionType() != 'automatic') ||
            ($type == 'save' && $entity->getTranslationCreationType() != 'automatic')) {
            return false;
        }

        # Create the handledId caching array for this entity's class if it doesn't exist
        if (!isset($handledIds[$type][$entityClass])) {
            $handledIds[$type][$entityClass] = [];
        }

        # Don't process entities that have already been dealt with (to avoid looping eternally)
        if (in_array($entity->getId(), $handledIds[$type][$entityClass])) {
            return false;
        }

        # Add the entity to the cache for the next time - it should only be processed once
        $handledIds[$type][$entityClass][] = $entity->getId();

        # Add the entity's translations as well, as they will be handled right away
        foreach ($entity->getTranslations() as $translation) {
            $handledIds[$type][$entityClass][] = $translation->getId();
        }

        return true;
    }

    public function prePersist(LifecycleEventArgs $args) {
        $this->handleEntitySaving($args);
    }

    public function preUpdate(LifecycleEventArgs $args) {
        $this->handleEntitySaving($args);
    }

    public function preRemove(LifecycleEventArgs $args) {
        $entity = $args->getEntity();

        # Check if this entity should be processed (ensures both entity type and single processing)
        if (!$this->shouldProcess($entity, 'remove')) {
            return;
        }

        # Remove all relations
        foreach ($this->getTranslations() as $translation) {
            $this->em->remove($entity);
        }
    }

    protected function handleEntitySaving(LifecycleEventArgs $args) {
        $entity = $args->getEntity();

        # Check if this entity should be processed (ensures both entity type and single processing)
        if (!$this->shouldProcess($entity, 'save')) {
            return;
        }

        # Autofill locale if empty, based on current locale
        if (property_exists($entity, 'locale') && !$entity->getLocale()) {
            $entity->setLocale($this->request->getLocale());
        }

        # The entity is being saved on its own, so it is considered as translated.
        $entity->setTranslated(true);

        # Create the missing translations automatically - unless archived
        if (!method_exists($entity, 'archive') || !$entity->isArchived()) {
            $entity->translateTo($this->getAvailableLocales());
        }

        # Automatically archive translations if the entity is archived
        if (method_exists($entity, 'archive') && $entity->isArchived() && $this->shouldProcess($entity, 'remove')) {
            $entity->archiveTranslations();
        }
    }

    public static function isClassTranslatableEntity($class) {
        return property_exists($class, 'locale') && property_exists($class, 'translated');
    }

    protected function getAvailableLocales() {
        return $this->container->getParameter('available_locales') ?: [$this->container->getParameter('locale') ?: 'fr_CA'];
    }
}
