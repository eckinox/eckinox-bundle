<?php

namespace Eckinox\Controller\Application;

use Eckinox\Entity\Application\PasswordResetRequest;
use Eckinox\Library\Symfony\Controller;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;

class SecurityController extends Controller
{
    use \Eckinox\Library\Application\email;

    /**
     * @Route("/login", name="login")
     */
    public function login(Request $request, AuthenticationUtils $authUtils, AuthorizationCheckerInterface $authChecker)
    {
        /*
         * Redirect the user if he is currently logged in
         */
        if($authChecker->isGranted('IS_AUTHENTICATED_FULLY') || $authChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirectToRoute('home');
        }

        /*
         * Get the login error if there is one
         */
        $error = $authUtils->getLastAuthenticationError();

        if($error) {
            $this->addFlash('error', $this->trans(
                $error->getMessageKey(),
                $error->getMessageData(),
                'security'
            ));
        }

        /*
         * Last username entered by the user
         */
        $lastUsername = $authUtils->getLastUsername();

        return $this->render('@Eckinox/application/security/login.html.twig', array(
            'last_username' => $lastUsername,
            'error'         => $error,
        ));
    }

    /**
     * @Route("/json/password-reset", name="password_reset_json")
     */
    public function passwordResetJson(Request $request)
    {
        $error = null;
        $email = trim($request->query->get('email'));

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'login.reset.message.invalidEmail';
        } else {
            $em = $this->getDoctrine()->getManager();
            $user = $em->getRepository($this->getParameter('user_class'))->findOneBy(array('email' => $email));

            if ($user) {
                if ($user->getIsActive()) {
                    $newRequest = new PasswordResetRequest($user);

                    $em->persist($newRequest);
                    $em->flush();

                    $this->email([
                        'to' => $email,
                        'subject' => $this->trans('login.reset.email.subject', [], 'application'),
                        'html' => $this->renderView('@Eckinox/email/password_reset.html.twig', [
                            'request' => $newRequest,
                            'website' => $_SERVER['SERVER_NAME'],
                        ])
                    ]);
                } else {
                    $error = 'login.reset.message.inactiveUser';
                }
            } else {
                $error = 'login.reset.message.userNotFound';
            }
        }

        $message = $error ?: 'login.reset.message.emailSent';
        return new JsonResponse(['success' => !$error, 'message' => $this->trans($message, [], 'application')]);
    }

    /**
     * @Route("/password-reset/{code}", name="password_reset")
     */
    public function passwordReset(Request $request, UserPasswordHasherInterface $passwordHasher, $code)
    {
        $userId = PasswordResetRequest::getUserIdFromCode($code);

        try {
            if (!$userId) {
                throw $this->createNotFoundException($this->trans('login.reset.change.message.invalidLink', [], 'application'));
            }

            $em = $this->getDoctrine()->getManager();
            $user = $em->getRepository($this->getParameter('user_class'))->findOneBy(array('id' => $userId));

            if (!$user) {
                throw $this->createNotFoundException($this->trans('login.reset.change.message.invalidLink', [], 'application'));
            }

            $passwordResetRequest = $em->getRepository('Eckinox\Entity\Application\PasswordResetRequest')->findOneBy(array('user' => $user, 'code' => $code, 'used' => false));

            if (!$passwordResetRequest) {
                throw $this->createNotFoundException($this->trans('login.reset.change.message.invalidLink', [], 'application'));
            }

            if ($passwordResetRequest->getDate()->modify('+7 days') <= (new \Datetime())) {
                throw $this->createNotFoundException($this->trans('login.reset.change.message.invalidLink', [], 'application'));
            }

            if ($passwordResetRequest->getUsed()) {
                throw $this->createNotFoundException($this->trans('login.reset.change.message.alreadyUsedLink', [], 'application'));
            }

            $form = $this->createFormBuilder($user)
                ->add('password', RepeatedType::class, array(
                    'type' => PasswordType::class,
                    'invalid_message' => $this->trans('login.reset.change.message.passwordsDontMatch', [], 'application'),
                    'options' => array('attr' => array('class' => 'password-field', 'placeholder' => $this->trans('login.reset.change.newPassword:placeholder', [], 'application'))),
                    'first_options'  => array('label' => $this->trans('login.reset.change.newPassword', [], 'application')),
                    'second_options' => array('label' => $this->trans('login.reset.change.newPasswordConfirmation', [], 'application'), 'attr' => ['placeholder' => $this->trans('login.reset.change.newPasswordConfirmation:placeholder', [], 'application')]),
                    'constraints' => [
                        new Length([
                            'min' => 16,
                            'minMessage' => $this->trans('login.reset.change.message.passwordTooShort', [], 'application'),
                            'max' => 4096,
                        ]),
                        new NotCompromisedPassword(),
                    ]
                ))
                ->add('save', SubmitType::class, array('label' => $this->trans('login.reset.change.submit', [], 'application'), 'attr' => ['class' => 'button']));

            $form->setAction($this->generateUrl('password_reset', ['code' => $code]));
            $form = $form->getForm();

            $originalPassword = $user->getPassword();
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                if ($user->getPassword()) {
                    $user->setPassword($passwordHasher->hashPassword($user, $user->getPassword()));
                } else {
                    $user->setPassword($originalPassword);
                }

                $resetRequests = $user->getPasswordResetRequests();
                foreach ($resetRequests as $resetRequest) {
                    if ($resetRequest->getCode() == $code) {
                        $resetRequest->setUsed(true);
                        $em->persist($resetRequest);
                        break;
                    }
                }

                $em->persist($user);
                $em->flush();

                $this->addFlash('success', $this->trans('login.reset.change.message.success', [], 'application'));

                return $this->redirectToRoute('login');
            }
        } catch (HttpException $e) {
            # All of the exceptions that are manually triggered above are 404 - catch them and output their message as an error flash message on the login screen.
            if ($e->getStatusCode() == 404) {
                $this->addFlash('error', $e->getMessage());
                return $this->redirectToRoute('login');
            }
        }


        return $this->render('@Eckinox/application/security/password_reset.html.twig', [
            'code' => $code,
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }
}
