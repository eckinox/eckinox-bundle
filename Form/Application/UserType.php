<?php

namespace Eckinox\Form\Application;

use Eckinox\Entity\Application\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\FormType;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /*
         * Check if the object is "new"
         * If no data is passed to the form, the data is "null".
         * This should be considered a new object.
         */
        $user = $builder->getData();
        $isNew = (!$user || null === $user->getId());

        $builder->add(
            $builder->create('left', FormType::class, array('inherit_data' => true))
                ->add('fullName', TextType::class, array(
                    'label' => 'user.fields.fullName',
                    'attr' => array('class' => 'full-name', 'data-validate' => 'required')
                ))
                ->add('email', TextType::class, array(
                    'label' => 'user.fields.email',
                    'attr' => array('class' => 'email' . ($options['emailIsValid'] ? '' : ' invalid'), 'data-validate' => 'required email', 'autocomplete' => 'off')
                ))
                ->add('homePhone', TelType::class, array(
                    'label' => 'user.fields.homePhone',
                    'required' => false,
                    'attr' => array('class' => 'home-phone half')
                ))
                ->add('mobilePhone', TelType::class, array(
                    'label' => 'user.fields.mobilePhone',
                    'required' => false,
                    'attr' => array('class' => 'mobile-phone half')
                ))
                ->add('function', TextType::class, array(
                    'label' => 'user.fields.function',
                    'required' => false,
                    'attr' => array('class' => 'function half')
                ))
                ->add('department', TextType::class, array(
                    'label' => 'user.fields.department',
                    'required' => false,
                    'attr' => array('class' => 'department half')
                ))
                ->add('password', RepeatedType::class, array(
                    'type' => PasswordType::class,
                    'invalid_message' => 'user.errors.password.mustBeIdentical',
                    'required' => $isNew,
                    'first_options'  => array('label' => 'user.fields.password', 'attr' => ['autocomplete' => 'off', 'class' => 'half', 'data-validate-identical' => 'user_left_password_second', 'data-validate' => $isNew ? 'required identical' : 'identical', 'placeholder' => $isNew ? '' : "user.placeholders.password"]),
                    'second_options' => array('label' => 'user.fields.confirmPassword', 'attr' => ['autocomplete' => 'off', 'class' => 'half', 'data-validate' => $isNew ? 'required' : '', 'placeholder' => $isNew ? '' : "user.placeholders.password"]),
                ))
                ->add('isActive', CheckboxType::class, array(
                    'label' => 'user.fields.isActive',
                    'required' => false,
                    'attr' => array('class' => 'is-active')
                ))
        );


        $right = $builder->create('right', FormType::class, array('inherit_data' => true));

        if($options['privileges']) {
            $right->add('privilegesGroup', ChoiceType::class, array(
                'label' => 'user.fields.privilegesGroup',
                'choices' => $options['privilegesGroup'],
                'attr' => array('class' => 'privilegesGroup'),
            ))->add('privileges', ChoiceType::class, array(
                'label' => 'user.fields.privileges',
                'multiple' => true,
                'expanded' => true,
                'choices' => $options['privileges'],
                'attr' => array('class' => 'privileges'),
            ));
        }

        $builder->add($right);
    }

    static public function getListing($controller = null) {
        return [
            "module" => "user",
            "domain" => "application",
            "mastersearch" => [
                "fields" => ["fullName", "email"]
            ],
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
                    "name" => "fullName",
                    "class" => "",
                    "visible" => true,
                    "filters" => null,
                ],
                [
                    "name" => "email",
                    "class" => "",
                    "visible" => true,
                    "filters" => null,
                ],
                [
                    "name" => "function",
                    "class" => "",
                    "visible" => false,
                    "filters" => null,
                ],
                [
                    "name" => "department",
                    "class" => "",
                    "visible" => false,
                    "filters" => null,
                ],
                [
                    "name" => "homePhone",
                    "class" => "",
                    "visible" => false,
                    "filters" => null,
                ],
                [
                    "name" => "mobilePhone",
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

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'privilegesGroup' => [],
            'privileges' => [],
            'emailIsValid' => true,
            'translation_domain' => 'application',
            'csrf_protection' => false,
            "attr" => [
                "ei-widget-ajax" => "",
            ]
        ]);
    }
}
