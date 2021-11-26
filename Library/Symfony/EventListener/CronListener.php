<?php

namespace Eckinox\Library\Symfony\EventListener;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use Eckinox\Library\Symfony\Annotation\Cron;
use Eckinox\Library\Symfony\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class CronListener {

    protected $reader;
    protected $container;
    protected $user;
    protected $router;

    public function __construct(Reader $reader, ContainerInterface $container)
    {
        $this->reader = $reader;
        $this->container = $container;
    }

    public function onKernelController(ControllerEvent $event)
    {
        /*
         * Nothing for now...
         */
    }
}
