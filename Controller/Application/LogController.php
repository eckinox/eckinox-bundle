<?php

namespace Eckinox\Controller\Application;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Eckinox\Entity\Application\Log;
use Eckinox\Library\General\Arrays;

class LogController extends AbstractController
{
    use \Eckinox\Library\Application\log;
    use \Eckinox\Library\General\appData;

    /**
     * @Route("/log/{page}", name="index_log", requirements={"page"="\d+"})
     */
    public function index(Request $request, $page = 1)
    {
        $log_repository = $this->getDoctrine()->getRepository(Log::class);
        $maxResults = $this->data('application.log.config.list.items_shown');

        $logs = $log_repository->getList($page, $maxResults);
        $count = $log_repository->getCount();
        $nbPages = intval(ceil($count / $maxResults));

        return $this->render('@Eckinox/application/log/index.html.twig', array(
            'logs' => $logs,
            'currentPage' => $page,
            'count' => $count,
            'nbPages' => $nbPages,
        ));
    }

    /**
     * @Route("/log/view/{log_id}", name="view_log", requirements={"log_id"="\d+"})
     */
    public function view(Request $request, $log_id = null)
    {
        $log = $this->getDoctrine()
            ->getRepository(Log::class)
            ->find($log_id);

        return $this->render('@Eckinox/application/log/view.html.twig', array(
            'log' => $log,
        ));
    }
}
