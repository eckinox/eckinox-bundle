<?php

namespace Eckinox\Library\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Eckinox\Library\Symfony\Doctrine\LocaleFilter;

trait baseEntity {

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     */
    private $updatedAt;

    /*
     * Getters & setters
     */

    public function setId($id){
		$this->id = $id;
	}

    public function getId() {
        return $this->id;
    }

    public function setCreatedAt($datetime = null) {
        $this->createdAt = $this->_datetime($datetime);
    }

    public function getCreatedAt() {
        return $this->createdAt;
    }

    public function setUpdatedAt($datetime = null) {
        $this->updatedAt = $this->_datetime($datetime);
    }

    public function getUpdatedAt() {
        return $this->updatedAt;
    }

    public function assign($data) {
        foreach($data as $key => $value) {
            $this->$key = $value;
        }

        return $this;
    }

    public function set($property, $value) {
        $methodName = 'set' . ucfirst($property);

        if (method_exists($this, $methodName)) {
            $this->$methodName($value);
        } else if (property_exists($this, $property)) {
            $this->$property = $value;
        } else {
            throw new \Exception("There is no corresponding property or method for \"" . $property . "\" in " . static::class);
        }

        return $this;
    }

    public function get($property) {
        $methodName = 'get' . ucfirst($property);

        if (method_exists($this, $methodName)) {
            return $this->$methodName();
        } else if (property_exists($this, $property)) {
            try {
                return $this->$property;
            } catch(\Exception $e) {
                return $this::$$property;
            }
        } else {
            throw new \Exception("There is no corresponding property or method for \"" . $property . "\" in " . static::class);
        }

        return null;
    }

    public function getRecursively($property) {
        if (!$property) {
            return null;
        }

        $parts = explode('.', $property);
        $key = array_shift($parts);

        $value = $this->get($key);

        while (count($parts)) {
            if (method_exists($value, 'getRecursively')) {
                return $value->getRecursively(implode('.', $parts));
            } else {
                $key = array_shift($parts);

                if (is_array($value) && array_key_exists($key, $value)) {
                    $value = $value[$key];
                } else if ($value instanceof \ArrayAccess && $value->offsetExists($key)) {
                    $value = $value[$key];
                } else if (is_object($value) && property_exists($value, $key)) {
                    $value = $value->$key;
                } else {
                    return null;
                }
            }
        }

        return $value;
    }

    public function getRawObject($returnEntityId = false, $dateFormat = null, $includeRelations = false) {
        $data = [];

        foreach (static::getClassProperties() as $property) {
            if ($property == 'translations') {
                continue;
            }

            $value = $this->get($property);

            if ($value instanceof Collection) {
                $relationData = [];
                foreach ($value as $entity) {
                    # @TODO: Inlcude sub-relations by managing recursion correctly
                    if (method_exists($entity, 'getRawObject')) {
                        $relationData[] = $entity->getRawObject($returnEntityId, $dateFormat, false);
                    }
                }
                $value = $relationData;
            } else if (is_object($value)) {
                if(method_exists($value, 'getId')) {
                    if ($returnEntityId) {
                        $value = $value->getId();
                    } else if (method_exists($value, 'getRawObject')) {
                        $value = $value->getRawObject(true, $dateFormat, false);
                    }
                } else if($dateFormat && method_exists($value, 'format')) {
                    $value = $value->format($dateFormat);
                }
            }

            $data[$property] = $value;
        }

        return $data;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function initializeDatetimes()
    {
        if (!$this->createdAt) {
            $this->createdAt = $this->_datetime();
        }

        $this->updatedAt = $this->_datetime();
    }

    protected function _datetime($datetime = null) {
        return $datetime ? ( is_string($datetime) ? new \DateTime($datetime) : $datetime ) : new \DateTime('now') ;
    }

    static public function getClassProperties() {
		return array_keys(get_class_vars(get_called_class()));
	}

    static public function getClassMethods() {
		return get_class_methods(get_called_class());
	}

    static public function getClassName() {
        return get_called_class();
    }

    static public function getTransKey() {
        $class = get_called_class();
        $key = substr($class, strrpos($class, '\\') + 1);
        return lcfirst($key);
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function verifyTranslatableRelations(LifecycleEventArgs $args = null) {
        $em = $args->getEntityManager();
        $metadata = $em->getClassMetadata(get_class($this));
        $db = $em->getConnection();

        $traits = class_uses(static::class);
        $translatabletraitName = 'Eckinox\\Library\\Entity\\translatableEntity';
        $properties = array_keys(get_object_vars($this));

        # If this is a translatable entity, the relations are kept as is.
        if (in_array($translatabletraitName, $traits)) {
            return;
        }

        # Find each relation to translatable entities, and add all translations of the selected entities to the collection
        foreach ($properties as $property) {
            # Skip properties that aren't collections
            if (!($this->$property instanceof Collection) || !count($this->$property)) {
                continue;
            }

            $relationTraits = class_uses($this->$property->first());

            # Skip relations that aren't translatable
            if (!in_array($translatabletraitName, $relationTraits)) {
                continue;
            }

            # Fetch every translation of the entities contained in this relation
            $newTranslations = [];
            $deletedRelations = [];
            foreach ($this->$property as $entity) {
                if ($args instanceof PreUpdateEventArgs) {
                    if ($this->$property->getDeleteDiff()) {
                        $deletedRelations = $this->$property->getDeleteDiff();
                    }
                }

                if (in_array($entity, $this->$property->getInsertDiff())) {
                    foreach ($entity->getTranslations() as $translation) {
                        $newTranslations[] = $translation;
                    }
                }
            }

            # Add every new translation to the relation
            foreach ($newTranslations as $translation) {
                if (!$this->$property->contains($translation)) {
                    $this->$property->add($translation);
                }
            }

            # Remove translation from the relation as well
            # This bit probably requires some explanation, so please read this before atetmpting to change anything inside this IF statement.
            # We can't simply do a $this->$property->remove($translation) to remove translations, because the locale SQL filter restricts Doctrine from loading said translations
            # We cannot simply disable the SQL filter to load the translations, as Doctrine has already cached the results of those queries with the filter turned on.
            # We cannot detach or refresh entity from the EntityManager, because we are in the middle of processing the flush(), and Doctrine very much dislikes us doing that.
            # Therefore, the solution that has been found as a counter to these issues, in order to remove all translations of a relation when removing said translation, is as follows.
            # We fetch the metadata of the relation and of that relation's translations property in order to manually build an SQL query that will remove relations to those relations' translations
            if ($deletedRelations) {
                # Get the association data between the current entity class and the relation entity class
                $associationMapping = $metadata->getAssociationMapping($property);
                $associationTable = $associationMapping['joinTable']['name'];
                $associationSourceColumn = $associationMapping['joinTable']['joinColumns'][0]['name'];
                $associationTargetColumn = $associationMapping['joinTable']['inverseJoinColumns'][0]['name'];

                # Get the target entity class translation metadata
                $targetMetadata = $em->getClassMetadata($associationMapping['targetEntity']);
                $targetTranslationsMapping = $targetMetadata->getAssociationMapping('translations');
                $targetTranslationsTable = $targetTranslationsMapping['joinTable']['name'];
                $targetTranslationSourceColumn = $targetTranslationsMapping['joinTable']['joinColumns'][0]['name'];
                $targetTranslationTargetColumn = $targetTranslationsMapping['joinTable']['inverseJoinColumns'][0]['name'];

                # Build the data array for the prepared statement
                $sqlData = [
                    'sourceId' => $this->get($associationMapping['joinTable']['joinColumns'][0]['referencedColumnName']),
                ];

                # Get the IDs of every relation that has been deleted, and add prepare the query for said IDs
                $inStatementPlaceholders = '';
                $deletedRelationIds = [];
                foreach ($deletedRelations as $i => $deletedRelation) {
                    $sqlData['relationId' . $i] = $deletedRelation->get($associationMapping['joinTable']['inverseJoinColumns'][0]['referencedColumnName']);
                    $inStatementPlaceholders .= ':relationId' . $i . ', ';
                }
                $inStatementPlaceholders = substr($inStatementPlaceholders, 0, strlen($inStatementPlaceholders) - 2);

                # Build the removal query with all of the data collected above
                $sql = strtr('
                    DELETE R FROM `%associationTable` as R
                    WHERE R.`%associationSourceColumn` = :sourceId
                    AND R.`%associationTargetColumn` IN (
                        SELECT T.`%targetTranslationTargetColumn`
                        FROM `%targetTranslationsTable` T
                        WHERE T.`%targetTranslationSourceColumn` IN (%inStatementPlaceholders)
                    )',
                    [
                        '%associationTable' => $associationTable,
                        '%associationSourceColumn' => $associationSourceColumn,
                        '%associationTargetColumn' => $associationTargetColumn,
                        '%targetTranslationsTable' => $targetTranslationsTable,
                        '%targetTranslationTargetColumn' => $targetTranslationTargetColumn,
                        '%targetTranslationSourceColumn' => $targetTranslationSourceColumn,
                        '%inStatementPlaceholders' => $inStatementPlaceholders,
                    ]
                );
                $translationsRemovalQuery = $db->prepare($sql);
                $translationsRemovalQuery->execute($sqlData);
            }
        }
    }

}
