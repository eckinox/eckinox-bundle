<?php

namespace Eckinox\Library\Entity;


/*
 * Manage ignored attributes when the entity is serialized
 */
trait loggableEntity {
    
    private $ignoredAttributes = array('createdAt', 'updatedAt');
    
    public function setIgnoredAttributes($ignoredAttributes) {
        $this->ignoredAttributes = (array) $ignoredAttributes;
    }
    
    public function addIgnoredAttributes($ignoredAttributes) {
        $this->ignoredAttributes = array_merge($this->getIgnoredAttributes(), (array) $ignoredAttributes);
    }
    
    public function removeIgnoredAttributes($ignoredAttributes) {
        foreach((array) $ignoredAttributes as $attr) {
            if(in_array($attr, $this->ignoredAttributes)) {
                $key = array_search($attr, $this->ignoredAttributes);
                
                unset($this->ignoredAttributes[$key]);
            }
        }
    }
    
    public function getIgnoredAttributes() {
        return $this->ignoredAttributes;
    }
    
}