<?php

namespace Eckinox\Library\Application;

trait log {

    public function log($message, $action, $data = [], $objectId = null, $module = null, $user = null) {
        $em = $this->getEntityManagerInstance($this);
        $log = new \Eckinox\Entity\Application\Log;

        $log->setMessage($message);
        $log->setAction($action);
        $log->setData($data);
        $log->setObjectId($objectId);
        $log->setModule($module);
        $log->setUser($user);

        $em->persist($log);

        return $log;
    }

    public function logBuildAction($function) {
        static $class = null;
        $class || ( $class = get_called_class() );

        return $class."::$function";
    }
}
