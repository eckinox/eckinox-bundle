<?php

namespace Eckinox\Controller\Application;

use Eckinox\Library\Symfony\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Eckinox\Library\General\Arrays;
use Eckinox\Library\General\Serializer;
use Eckinox\Library\General\Git;
use Eckinox\Library\Symfony\Annotation\Security;
use Eckinox\Library\Symfony\Annotation\Lang;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 *  @Lang(domain="application", default_key="software")
 */
class SoftwareController extends Controller
{
    use \Eckinox\Library\Application\log;
    use \Eckinox\Library\General\appData;

    protected $securityRedirect = 'home';

    /**
     * @Route("/software/update", name="index_software")
     * @Security(privilege="SOFTWARE_UPDATE")
     */
    public function update(Request $request) {
        $filechanged = Git::getFileChanged();
        $version = Git::getCommitDate('Y-m-d H:i:s');

        if ($action = $request->request->get('action')) {
            switch($action) {
                case "update":
                    if ( ! $filechanged ) {
                        $shell = Git::pull("Eckidev/origin");
                    }

                    break;
            }
        }

        if ( $filechanged ) {
            $shell = "-- Files changed --\n$filechanged";
        }

        return $this->render('@Eckinox/application/software/update.html.twig', [
            'shell' => $shell ?? "",
            'version' => $version,
            'updatable' => empty($filechanged),
            'update_available' => Git::getUpdateCount("Eckidev/origin"),
            'title' => $this->lang('title.'.$request->get('_route')),
        ]);
    }
}
