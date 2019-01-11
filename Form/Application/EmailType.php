<?php

namespace Eckinox\Form\Application;

use Eckinox\Entity\Application\Email;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class EmailType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /*
         * Check if the object is "new"
         * If no data is passed to the form, the data is "null".
         * This should be considered a new object.
         */
        $email = $builder->getData();
        $isNew = (!$email || null === $email->getId());

        $builder->add(
            $builder->create('left', FormType::class, array('inherit_data' => true))
                ->add('from', ChoiceType::class, array(
                    'choices' => array_flip($options['users']),
                    'data'  => $options['from'],
                    'label' => 'email.fields.from',
                    'attr' => array('class' => 'from', 'data-validate' => $options['required'], 'autocomplete' => 'off', 'disabled' => $options['disabled'])
                ))
                ->add('to', CollectionType::class, array(
                    'entry_type' => ChoiceType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'delete_empty' => true,
                    'prototype' => true,
                    'prototype_data' => '',
                    'data'  => $options['to'],
                    'label' => 'email.fields.to',
                    'entry_options' => [
                        'placeholder' => 'email.placeholder.contact',
                        'choices' => $options['contacts'],
                        'attr' => array('class' => 'to', 'data-validate' => $options['required'], 'autocomplete' => 'off', 'disabled' => $options['disabled'])
                    ]
                ))
                ->add('cc', CollectionType::class, array(
                    'entry_type' => ChoiceType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'delete_empty' => true,
                    'prototype' => true,
                    'prototype_data' => '',
                    'data'  => $options['cc'],
                    'required' => false,
                    'label' => 'email.fields.cc',
                    'entry_options' => [
                        'placeholder' => 'email.placeholder.contact',
                        'choices' => $options['contacts'],
                        'attr' => array('class' => 'cc', 'autocomplete' => 'off', 'disabled' => $options['disabled'])
                    ]
                ))
                ->add('bcc', CollectionType::class, array(
                    'entry_type' => ChoiceType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'delete_empty' => true,
                    'prototype' => true,
                    'prototype_data' => '',
                    'data'  => $options['bcc'],
                    'required' => false,
                    'label' => 'email.fields.bcc',
                    'entry_options' => [
                        'placeholder' => 'email.placeholder.contact',
                        'choices' => $options['contacts'],
                        'attr' => array('class' => 'bcc', 'autocomplete' => 'off', 'disabled' => $options['disabled'])
                    ]
                ))
                ->add('subject', TextType::class, array(
                    'data'  => $options['subject'],
                    'label' => 'email.fields.subject',
                    'attr' => array('class' => 'subject', 'data-validate' => 'required', 'autocomplete' => 'off', 'disabled' => $options['disabled'])
                ))
                ->add('draft', CheckboxType::class, array(
                    'label' => 'email.fields.draft',
                    'required' => false,
                    'attr' => array('class' => 'draft')
                ))
                ->add('html', TextareaType::class, array(
                    'label' => 'email.fields.text',
                    'data' => $options['html'] ?: '<p><br></p>',
                    'attr' => array('class' => 'text', 'disabled' => $options['disabled'])
                ))
        );

        $builder->add(
            $builder->create('right', FormType::class, array('inherit_data' => true))

        );
    }

    static public function getListing($controller = null) {
        return [
            "module" => "email",
            "domain" => "application",
            "fields" => [
                [
                    "name" => "status",
                    "class" => "",
                    "visible" => true,
                    "filters" => [
                        [
                            "name" => "icon",
                            "arguments" => [],
                        ],
                    ],
                    "search" => [
                        "expr" => "orX"
                    ]
                ],
                [
                    "name" => "from",
                    "class" => "",
                    "visible" => true,
                    "filters" => null,
                ],
                [
                    "name" => "to",
                    "class" => "",
                    "visible" => true,
                    "filters" => [
                        [
                            "name" => "join",
                            "arguments" => ["; "],
                        ]
                    ]
                ],
                [
                    "name" => "cc",
                    "class" => "",
                    "visible" => false,
                    "filters" => [
                        [
                            "name" => "join",
                            "arguments" => ["; "],
                        ]
                    ]
                ],
                [
                    "name" => "bcc",
                    "class" => "",
                    "visible" => false,
                    "filters" => [
                        [
                            "name" => "join",
                            "arguments" => ["; "],
                        ]
                    ]
                ],
                [
                    "name" => "subject",
                    "class" => "",
                    "visible" => true,
                    "filters" => null,
                ],
                [
                    "name" => "module",
                    "class" => "",
                    "visible" => false,
                    "filters" => null,
                ],
                [
                    "name" => "user",
                    "class" => "",
                    "visible" => false,
                    "filters" => null,
                ],
                [
                    "name" => "createdAt",
                    "class" => "date",
                    "visible" => false,
                    "filters" => [
                        [
                            "name" => "date",
                            "arguments" => ["Y-m-d H:i:s"],
                        ],
                    ],
                ],
                [
                    "name" => "updatedAt",
                    "class" => "date",
                    "visible" => false,
                    "filters" => [
                        [
                            "name" => "date",
                            "arguments" => ["Y-m-d H:i:s"],
                        ],
                    ],
                ],
            ],
        ];
    }

     static public function getTemplatesListing($controller = null) {
        return [
            "module" => "email",
            "domain" => "application",
            "fields" => [
                [
                    "name" => "subject",
                    "class" => "",
                    "visible" => true,
                    "filters" => null,
                ],
                [
                    "name" => "createdAt",
                    "class" => "date",
                    "visible" => true,
                    "filters" => [
                        [
                            "name" => "date",
                            "arguments" => ["Y-m-d H:i:s"],
                        ],
                    ],
                ],
                [
                    "name" => "updatedAt",
                    "class" => "date",
                    "visible" => true,
                    "filters" => [
                        [
                            "name" => "date",
                            "arguments" => ["Y-m-d H:i:s"],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Email::class,
            'translation_domain' => 'application',
            'users' => [],
            'contacts' => [],
            'required' => null,
            'from' => [''],
            'to' => [''],
            'cc' => [''],
            'bcc' => [''],
            'subject' => '',
            'html' => '',
            'disabled' => true,
            'csrf_protection' => false,
        ]);
    }
}
