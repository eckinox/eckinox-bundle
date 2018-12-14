<?php

namespace Eckinox\Library\Symfony\EventListener;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Eckinox\Library\Symfony\Controller;
use Eckinox\Library\Symfony\Annotation\Lang;

class LangListener {

    protected $reader;
    protected $user;
    protected $router;

    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        if ( !is_array($controllerArray = $event->getController()) ) {
            return;
        }

        list($controller, $methodName) = $controllerArray;

        $reflectionClass = new \ReflectionClass($controller);
        $obj = $this->reader->getClassAnnotation($reflectionClass, Lang::class);
    }
}
