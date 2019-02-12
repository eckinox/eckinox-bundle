<?php

namespace Eckinox\Library\Symfony\EventListener;

use Eckinox\Library\Symfony\Annotation\Security;
use Doctrine\Common\Annotations\Reader;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Eckinox\Library\Symfony\Controller;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;

class SecurityListener {

    protected $reader;
    protected $user;
    protected $router;
    protected $session;
    protected $translator;

    public function __construct(Session $session, TranslatorInterface $translator, Reader $reader, TokenStorageInterface $tokenStorage, $router)
    {
        $this->reader = $reader;
        $this->router = $router;
        $this->session = $session;
        $this->translator = $translator;

        if($token = $tokenStorage->getToken()) {
            $this->user = $token->getUser();
        }

    }

    public function onKernelController(FilterControllerEvent $event)
    {
        /*
         * If the user is inactive but still logged in, redirect to logout url
         */
        if(is_object($this->user) && !$this->user->isEnabled()) {
            $logoutUrl = $this->router->generate('logout');

            $event->setController(function () use ($logoutUrl) {
                return new RedirectResponse($logoutUrl);
            });
        }

        if (!is_array($controllerArray = $event->getController()) || !$this->user) {
            return;
        }

        $request = $event->getRequest();

        list($controller, $methodName) = $controllerArray;

        $reflectionClass = new \ReflectionClass($controller);
        $classAnnotation = $this->reader->getClassAnnotation($reflectionClass, CheckRequest::class);

        $reflectionObject = new \ReflectionObject($controller);
        $reflectionMethod = $reflectionObject->getMethod($methodName);
        $security = $this->reader->getMethodAnnotation($reflectionMethod, Security::class);

        if (!($classAnnotation || $security)) {
            return;
        }

        $privilege = $security->getPrivilege();

        /*
         * We redirect the user if he doesn't have the privilege
         */
        if(!$this->user->hasPrivilege($privilege)) {
            $redirectRoute = $security->getRedirect() ?? $controller->getSecurityRedirect();
            $currentRoute = $request->attributes->get('_route');

            if($currentRoute === $redirectRoute) {
                $redirectRoute = (new Controller($this->translator))->getSecurityRedirect();
            }

            /*
             * Get privileges messages
             */
            $privileges_messages = array();

            foreach($controller->data('privileges.privileges') as $moduleName => $items) {
                foreach($items as $privilegeId) {
                    $messageKey = implode('.', ['privileges', 'messages', $moduleName, $privilegeId]);
                    $translation = $this->translator->trans($messageKey, [], 'application');
                    $privileges_messages[$privilegeId] = $translation != $messageKey ? $messageKey : null;
                }
            }

            /*
             * Add flash message
             */
            $this->session->getFlashBag()->add(
                'error',
                $this->translator->trans(
                    $privileges_messages[$privilege] ?? 'privileges.default_message',
                    [],
                    'application'
                )
            );

            $redirectUrl = $this->router->generate($redirectRoute);

            $event->setController(function () use ($redirectUrl) {
                return new RedirectResponse($redirectUrl);
            });
        }

    }
}
