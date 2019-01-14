<?php

namespace Eckinox\Library\Entity;

trait archivableEntity {

    /**
     * @ORM\Column(name="is_archived", type="boolean", options={"default": false})
     */
    private $isArchived = false;

    /*
     * Getters & setters
     */

    public function archive() {
        $this->setIsArchived(true);
    }

    public function setIsArchived($isArchived){
		$this->isArchived = $isArchived;
        return $this;
	}

    public function isArchived() {
        return $this->isArchived;
    }
}
