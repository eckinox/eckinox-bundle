<?php

namespace Eckinox\Controller\Application;

use Eckinox\Library\Symfony\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\Routing\Annotation\Route,
    Eckinox\Library\Symfony\Annotation\Lang;

/**
 *  @Lang(domain="application", default_key="import")
 */
class ImportController extends Controller
{
    use \Eckinox\Library\Application\log;
    use \Eckinox\Library\General\appData;

    /**
     * @Route("/import/{importType}", name="import_index")
     */
    public function index(Request $request, $importType)
    {
        $formsPath = $this->getParameter('app.forms.path');
        $modules = [];
        $activeModules = $request->request->get('modules') ?: $request->query->get('modules');
        $terms = $request->request->get('terms') ?: $request->query->get('terms');

        return $this->renderModView('@Eckinox/application/import/index.html.twig', array(
            'modules' => $modules,
            'title' => $this->lang('title.'.$request->get('_route')),
        ), $request);
    }
}
