<?php

namespace Eckinox\Library\Symfony\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Doctrine\ORM\EntityManagerInterface;

class FormListener implements EventSubscriberInterface
{
    protected $em;
    protected $event;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SUBMIT => [
                ['onPreSubmit', 10],
            ],
        ];
    }

    public function onPreSubmit(FormEvent &$event)
    {
        $this->event = $event;
        $this->inflateAutocompletes($event->getForm());
    }

    public function inflateAutocompletes(Form $form, $keys = [])
    {
        $config = $form->getConfig();
        $blockPrefix = $config->getType()->getBlockPrefix();

        # Inflate if need be
        if ($blockPrefix == 'autocomplete') {
            $accessKeys = array_merge($keys, [$config->getName()]);
            array_shift($accessKeys);

            $newData = $this->event->getData();
            $value = &$newData;
            while (count($accessKeys)) {
                $value = &$value[array_shift($accessKeys)];
            }

            # If a numeric value is found (ID), inflate it into the desired entity
            if ($value && is_numeric($value)) {
                $entity = $this->em->getRepository(current($config->getAttributes())['class'])->find($value);
                $value = $entity;
                $this->event->setData($newData);
            }
        }

        # Check to inflate children
        if ($form->count()) {
            foreach ($form->all() as $child) {
                if ($child instanceof \Symfony\Component\Form\Form) {
                    $this->inflateAutocompletes($child, array_merge($keys, [$config->getName()]));
                }
            }
        }
    }
}
