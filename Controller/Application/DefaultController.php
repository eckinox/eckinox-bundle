<?php

namespace Eckinox\Controller\Application;

use Eckinox\Library\Symfony\Controller,
    Eckinox\Library\Symfony\Annotation\Breadcrumb,
    Eckinox\Library\Symfony\Annotation\Lang;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\Routing\Annotation\Route;

use Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse;

/**
 *  @Lang(domain="application", default_key="dashboard")
 */
class DefaultController extends Controller
{
    /**
     * @Route("/", name="home")
     * @Breadcrumb(parent=false)
     */
    public function home(Request $request)
    {
        $user = $this->getUser();

        return $this->render('@Eckinox/application/home.html.twig', array(
            'user' => $this->getUser(),
        ));
    }

}
