<?php

namespace Eckinox\Form\Field;

use Eckinox\Entity\Application\User;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class AutocompleteType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'class' => null,
            'autocomplete_label' => 'id',
            'autocomplete_key' => 'id',
            'where' => [],
            'search' => [],
            'visual_validation' => true,
            'value_only' => false
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $attributes = $options['attr'] ?? [];
        $attributes['name'] = $view->vars['full_name'];
        $attributes['value'] = $form->getData();

        if ($options['visual_validation'] ?? true) {
            $attributes['visual-validation'] = '';
        }

        if ($options['required'] ?? false) {
            $attributes['required'] = 'required';
        }

        $settings = [
            'attributes' => $attributes,
            'entity' => $options['class'] ?? null,
            'search' => $options['search'] ?? [],
            'label' => $options['autocomplete_label'] ?? 'id',
            'where' => $options['where'] ?? [],
            'use_hidden_id' => !($options['value_only'] ?? false),
            'unique_label' => $options['value_only'] ?? false,
            'allow_empty' => $options['allow_empty'] ?? true,
        ];

        if ($options['autocomplete_key'] ?? false) {
            $settings['key'] = $options['autocomplete_key'];
        }

        $view->vars['settings'] = $settings;
   }

    public function getParent()
    {
        return TextType::class;
    }
}
