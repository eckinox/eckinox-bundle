<?php

namespace Eckinox\Library\Application;

trait alert {

    public function alert($type, $message, $module = null, $objectId = null, $data = []) {
        $em = $this->getEntityManagerInstance($this);
        $alert = new \Eckinox\Entity\Application\Alert;

        $alert->setType($type);
        $alert->setMessage($message);
        $alert->setModule($module);
        $alert->setObjectId($objectId);
        $alert->setData($data);

        $em->persist($alert);
    }
}
