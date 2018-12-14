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
use Eckinox\Library\Symfony\Annotation\Cron;
use Symfony\Component\DependencyInjection\ContainerInterface;

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

    public function onKernelController(FilterControllerEvent $event)
    {
        /*
         * Nothing for now...
         */
    }
}
