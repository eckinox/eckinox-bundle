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
        $modules = [];
        $activeModules = $request->request->get('modules') ?: $request->query->get('modules');
        $terms = $request->request->get('terms') ?: $request->query->get('terms');

        # If there's an error in the import settings, redirect to the dashboard with an error message
        try {
            $settings = $this->loadImportSettings($importType);
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('home');
        }

        $entity = new $settings['entity']();

        return $this->renderModView('@Eckinox/application/import/index.html.twig', array(
            'modules' => $modules,
            'settings' => $settings,
            'entity' => $entity,
            'title' => $this->lang('title.'.$request->get('_route')),
        ), $request);
    }

    protected function loadImportSettings($importType) {
        $settings = $this->data('import.' . $importType);
        $error = null;

        # Check if this import type is defined in the import.json data file
        if (!$settings) {
            $error = $this->get('translator')->trans(
                'import.errors.settings.undefinedType',
                ['%importType%' => $importType],
                'application'
            );
        }

        # If an entity is defined, check if it exists
        if (isset($settings['entity']) && !class_exists($settings['entity']))     {
            $error = $this->get('translator')->trans(
                'import.errors.settings.undefinedEntity',
                ['%entity%' => $settings['entity']],
                'application'
            );
        }

        # Throw an exception with the error message if there is one
        if ($error) {
            throw new \Exception($error);
        }

        return $settings;
    }
}
