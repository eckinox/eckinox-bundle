<?php

namespace Eckinox\Library\Entity;

use Doctrine\Common\Collections\Collection;

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
    public function verifyTranslatableRelations() {
        $traits = class_uses(static::class);
        $traitName = 'Eckinox\\Library\\Entity\\translatableEntity';
        $properties = array_keys(get_object_vars($this));

        # If this is a translatable entity, the relations are kept as is.
        if (in_array('Eckinox\\Library\\Entity\\translatableEntity', $traits)) {
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
            if (!in_array('Eckinox\\Library\\Entity\\translatableEntity', $relationTraits)) {
                continue;
            }

            # Fetch every translation of the entities contained in this relation
            $translations = [];
            foreach ($this->$property as $entity) {
                foreach ($entity->getTranslations() as $translation) {
                    $translations[] = $translation;
                }
            }

            # Add every translation to the relation
            foreach ($translations as $translation) {
                if (!$this->$property->contains($translation)) {
                    $this->$property->add($translation);
                }
            }

        }
    }

}
