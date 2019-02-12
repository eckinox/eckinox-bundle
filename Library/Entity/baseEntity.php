<?php

namespace Eckinox\Library\Entity;

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

    public function getRawObject($return_entity_id = false, $date_format = null) {
        $data = [];

        foreach (static::getClassProperties() as $property) {
            $value = $this->get($property);

            if(is_object($value)) {
                if($return_entity_id && method_exists($value, 'getId')) {
                    $value =  $value->getId();
                } else if($date_format && method_exists($value, 'format')) {
                    $value = $value->format($date_format);
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

}
