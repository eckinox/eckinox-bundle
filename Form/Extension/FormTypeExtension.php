<?php

namespace Eckinox\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Eckinox\Library\Symfony\EventSubscriber\FormListener;

class FormTypeExtension extends AbstractTypeExtension
{
    protected $formListener;

    public function __construct(FormListener $formListener) {
        $this->formListener = $formListener;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber($this->formListener);
    }

    static public function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }

    public function getExtendedType()
    {
        return FormType::class;
    }
}
