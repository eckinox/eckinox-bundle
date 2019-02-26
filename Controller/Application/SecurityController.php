<?php

namespace Eckinox\Controller\Application;

use Eckinox\Library\Symfony\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Eckinox\Entity\Application\User;

class SecurityController extends Controller
{
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

        return $this->render('@Eckinox/application/login.html.twig', array(
            'last_username' => $lastUsername,
            'error'         => $error,
        ));
    }
}
