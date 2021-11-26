<?php

namespace Eckinox\Library\Symfony\EventListener;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use Eckinox\Library\Symfony\Annotation\Breadcrumb;
use Eckinox\Library\Symfony\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class BreadcrumbListener {

    protected $reader;
    protected $user;
    protected $router;

    public function __construct(Reader $reader, TokenStorageInterface $tokenStorage, $router)
    {
        $this->reader = $reader;
        $this->router = $router;

        if($token = $tokenStorage->getToken()) {
            $this->user = $token->getUser();
        }
    }

    public function onKernelController(ControllerEvent $event)
    {
        if ( !is_array($controllerArray = $event->getController()) ) {
            return;
        }

        $request = $event->getRequest();

        list($controller, $methodName) = $controllerArray;

        $reflectionObject = new \ReflectionObject($controller);
        $reflectionMethod = $reflectionObject->getMethod($methodName);

        $obj = $this->reader->getMethodAnnotations(new \ReflectionMethod($controller, $methodName), Breadcrumb::class);

        $request->attributes->set('annotation', $obj ?: []);
        $request->attributes->set('router', $this->router);
        $request->attributes->set('reader', $this->reader);
    }
}
