<?php

namespace Eckinox\Controller\Application;

use Symfony\{
    Component\HttpFoundation\Request,
    Component\Routing\Annotation\Route,
    Component\Finder\Finder
};

use Eckinox\{
    Entity,
    Library\General\Arrays,
    Library\General\Data,
    Library\Symfony\Controller,
    Library\Symfony\Annotation\Security,
    Library\Symfony\Annotation\Breadcrumb,
    Library\Symfony\Annotation\Lang
};

/**
 *  @Lang(domain="app_data", default_key="data")
 */
class DataController extends Controller
{
    use \Eckinox\Library\Application\log;

    /**
     * @Route("/software/data", name="index_software_data")
     * @Security(privilege="SOFTWARE_UPDATE")
     * @Breadcrumb(parent="home")
     */
    public function index(Request $request)
    {
        return $this->renderModView('@Eckinox/application/data/index.html.twig', [
            'data_list' => $this->data('app_data')
        ], $request);
    }

    /**
     * @Route("/software/data/edit", name="edit_software_data")
     * @Security(privilege="SOFTWARE_UPDATE")
     * @Breadcrumb(parent="index_software_data")
     */
    public function edit(Request $request)
    {
        if(!$data_name = $request->query->get('data_name')) {
            $this->addFlash('warning', $this->trans('messages.warning.empty_path', [], 'app_data'));
            return $this->redirectToRoute('index_software_data');
        }

        if(!$data = $this->data('app_data.'.$data_name)) {
            $this->addFlash('error', $this->trans('messages.error.inexistant_path', [], 'app_data'));
            return $this->redirectToRoute('index_software_data');
        }

        if($request->isMethod('POST')) {
            $this->handleData($request, $data);
            $this->handleTranslations($request, $data);

            $this->addFlash('success', $this->trans('messages.success.data_saved', [], 'app_data'));
        }

        $all_options = $this->getAllOptions($data);
        $rows = $this->getRows($data);

        return $this->renderModView('@Eckinox/application/data/edit.html.twig', [
            'data_name' => $data_name,
            'data' => $data,
            'rows' => $rows,
            'all_options' => $all_options,
            'breadcrumbVariables' => [
                'edit_software_data' => [ "%name%" => $this->trans('data.'.$data_name.'.title', [], 'app_data') ],
                'parent' => 'index_software_data',
            ],
        ], $request);
    }

    public function handleData($request, $data) {
        $post_data = $request->request->get('data');

        $custom_json_path = $this->getParameter('app.data.path_custom').$data['json'];
        $custom_json_data = file_exists($custom_json_path) ? json_decode(file_get_contents($custom_json_path), true) : [];

        $array = &$custom_json_data;
        foreach(explode('.', $data['path']) as $index => $key) {
            if($index === 0) { continue; } // it's the name of the json file

            if(!isset($array[$key])) { $array[$key] = []; }
            $array = &$array[$key];
        }

        $current_data = $array;
        $array = $post_data;
        $data_differences = Arrays::diff($post_data, $current_data);

        if($data_differences) {
            $this->log(
                $this->trans(
                    'logs.data_updated',
                    ["%name%" => $data['json']],
                    'app_data'
                ),
                $this->logBuildAction(__FUNCTION__),
                ['data_path' => $data['path'], 'old_data' => $current_data, 'differences' => $data_differences],
                null,
                'Application',
                $this->getUser()
            );
        }

        file_put_contents($custom_json_path, json_encode($custom_json_data, JSON_PRETTY_PRINT) );
    }

    public function handleTranslations($request, $data) {
        $post_translations = $request->request->get('translations');

        if(!$post_translations) {
            return;
        }

        $domain = $data['translation']['domain'];
        $path = $data['translation']['path'];
        $locale = $request->getLocale();
        $custom_json_path = sprintf('%s%s.%s.json', $this->getParameter('app.translations.custom'), $domain, $locale);

        $custom_json_translations = file_exists($custom_json_path) ? json_decode(file_get_contents($custom_json_path), true) : [];

        $array = &$custom_json_translations;
        foreach(explode('.', $path) as $index => $key) {
            if(!isset($array[$key])) { $array[$key] = []; }
            $array = &$array[$key];
        }

        $current_data = $array;
        $array = $post_translations;
        $data_differences = Arrays::diff($post_translations, $current_data);

        if($data_differences) {
            $this->log(
                $this->trans(
                    'logs.translations_updated',
                    ["%name%" => sprintf('%s.%s.json', $domain, $locale)],
                    'app_data'
                ),
                $this->logBuildAction(__FUNCTION__),
                ['translation_path' => $path, 'old_data' => $current_data, 'differences' => $data_differences],
                null,
                'Application',
                $this->getUser()
            );
        }

        file_put_contents($custom_json_path, json_encode($custom_json_translations, JSON_PRETTY_PRINT) );
        $this->_purge_cache(); // temporary
    }

    public function getAllOptions($data) {
        $all_options = [];

        if(!isset($data['fields'])) {
            return $all_options;
        }

        foreach($data['fields'] as $field) {
            if($field['type'] === 'select') {
                $options = [];

                if(isset($field['options_path']) && $options_path = $field['options_path']) {
                    $options = $this->data($options_path);
                } elseif(isset($field['options'])) {
                    $options = isset($field['options']);
                }

                foreach($options as $value => $label) {
                    if(isset($field['translation'])) {
                        $label = $this->trans($label, [], $field['translation']['domain']);
                    }

                    $all_options[$field['name']][$value] = $label;
                }

                asort($all_options[$field['name']]);
            }
        }

        return $all_options;
    }

    public function getRows($data) {
        $rows = $this->data($data['path']);

        if(!isset($data['fields'])) {
            return $rows;
        }

        // Set unset value to prevent errors
        foreach($rows as &$row) {
            foreach($data['fields'] as $f) {
                if(!isset($row[$f['name']])) {
                    $row[$f['name']] = null;
                }
            }
        }


        if(isset($data['group']) && $field_name = $data['group']) {
            $field = null;

            foreach($data['fields'] as $f) {
                if($f['name'] === $field_name) {
                    $field = $f;
                    break;
                }
            }

            if($field) {
                $translation = isset($field['translation']) ? $field['translation'] : false;

                usort($rows, function($a, $b) use ($field_name, $translation) {
                    $value_a = $a[$field_name];
                    $value_b = $b[$field_name];

                    if($translation) {
                        $path = isset($translation['path']) ? sprintf('%s.', $translation['path']) : '';

                        $value_a = $this->trans($path . $value_a, [], $translation['domain']);
                        $value_b = $this->trans($path . $value_b, [], $translation['domain']);
                    }

                    return $value_a > $value_b;
                });
            }
        }

        return $rows;
    }

    /**
     * @Route("/software/data/logs/{id}", name="logs_json")
     * @Security(privilege="SOFTWARE_UPDATE")
     */
    public function logs(Request $request, $id = 0) {
        $log = $this->getDoctrine()
            ->getRepository( Entity\Application\Log::class )
            ->find($id);

        return $this->view($request, $log->getData(), "Données du journal d'activité #$id");
    }
}
