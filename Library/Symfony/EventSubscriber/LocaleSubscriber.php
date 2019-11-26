<?php
namespace Eckinox\Library\Symfony\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Doctrine\ORM\EntityManagerInterface;
use Eckinox\Library\Symfony\Doctrine\LocaleFilter;

class LocaleSubscriber implements EventSubscriberInterface
{
    private $defaultLocale;
    private $em;

    public function __construct(EntityManagerInterface $em, $defaultLocale = 'fr_CA')
    {
        $this->em = $em;
        $this->defaultLocale = $defaultLocale;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if (!$request->hasPreviousSession()) {
            return;
        }

        # Try to see if the locale has been set as a _locale routing parameter
        if ($locale = $request->query->get('_locale')) {
            $request->getSession()->set('_locale', $locale);
            $request->setLocale($locale);
        } else {
            # If no explicit locale has been set on this request, use one from the session
            $request->setLocale($request->getSession()->get('_locale', $this->defaultLocale));
        }

        # This is a fairly dirty workaround to injecting permanent data to the filter
        LocaleFilter::setLocale($request->getLocale());
        LocaleFilter::setEntityManager($this->em);

        # Enable the Doctrine SQL filter for entity locales
        if (!LocaleFilter::isEnabled()) {
            $filter = $this->em->getFilters()->enable('localeFilter');
            $filter->setParameter('locale', $request->getLocale());
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            // must be registered before (i.e. with a higher priority than) the default Locale listener
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}
