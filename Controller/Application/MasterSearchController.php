<?php

namespace Eckinox\Controller\Application;

use Eckinox\Library\Symfony\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\Routing\Annotation\Route,
    Eckinox\Library\Symfony\Annotation\Lang;

/**
 *  @Lang(domain="application", default_key="mastersearch")
 */
class MasterSearchController extends Controller
{
    use \Eckinox\Library\Application\log;
    use \Eckinox\Library\General\appData;

    private $forms = [];

    /**
     * @Route("/search", name="search_index")
     */
    public function index(Request $request)
    {
        $formsPath = $this->getParameter('app.forms.path');
        $modules = [];
        $activeModules = $request->request->get('modules') ?: $request->query->get('modules');
        $terms = $request->request->get('terms') ?: $request->query->get('terms');

        /*
         * Get all forms
         */
        foreach ($formsPath as $namespace => $path) {
            $this->forms = array_merge($this->forms, $this->getFormTypes($path, $namespace));
        }

        /*
         * Set modules
         */
        foreach ($this->forms as $className) {
            $methodName = 'getListing';

            if(method_exists($className, $methodName)) {
                $listing = call_user_func($className.'::'.$methodName, $this);

                if ($listing['mastersearch']['types'] ?? false) {
                    foreach ($listing['mastersearch']['types'] as $type) {
                        $module = $this->addModule($listing, $type['fields'], $type['name']);
                        $modules[$type['name']] = $module;
                    }
                } else if ($listing['mastersearch']['fields'] ?? false) {
                    $module = $this->addModule($listing, $listing['mastersearch']['fields']);

                    if (!isset($modules[$listing['module']]) || ($module['redefines'] ?? null)) {
                        $modules[$listing['module']] = $module;
                    }
                }
            }
        }

        if ($terms) {
            $terms = $this->normalizeTerms($terms);

            $entityPaths = [
                'Eckinox\\Entity\\__DOMAIN__\    \__MODULE__',
                'App\\Entity\\__DOMAIN__\\__MODULE__',
                'App\\Entity\\__MODULE__',
            ];

            foreach ($modules as &$module) {
                foreach ($module['fields'] ?? [] as &$field) {
                    $field['terms'] = $terms;
                }
            }

            # Fetch the list of redefined entities
            $redefinedClasses = [];
            foreach ($modules as &$module) {
                $redefinedClass = $module['redefines'] ?? null;

                if ($redefinedClass) {
                    $redefinedClasses[] = $redefinedClass;
                }
            }

            /*
             * Search in selected modules
             */
            foreach ($modules as &$module) {
                $entityClass = null;
                $className = ucfirst($module['module_class'] ?? $module['module']);

                foreach ($entityPaths as $possiblePath) {
                    $class = str_replace(['__DOMAIN__', '__MODULE__'], [ucfirst($module['domain']), $className], $possiblePath);

                    if (class_exists($class)) {
                        $entityClass = $class;
                        break;
                    }
                }

                if ($entityClass && class_exists($entityClass) && !in_array($entityClass, $redefinedClasses)) {
                    $repository = $this->getDoctrine()->getRepository($entityClass);

                    if (method_exists($repository, 'getList')) {
                        /*
                         * According to his privileges, restrict the user to see only his own objects
                         */
                        $user = null;
                        if (!$this->getUser()->hasPrivilege(strtoupper($module['module']).'_ALL')) {
                           $user = $this->getUser();
                        }

                        $module['result'] = $repository->getList(1, $this->data('application.mastersearch.config.list.items_shown'), $module['fields'] ?? [], $module['type'] ?? null, $user);
                    } else {
                        $module['result'] = [];
                    }

                } else {
                }
            }
        }

        $modules = array_filter($modules, function($module) {
            return array_key_exists('result', $module);
        });

        uasort($modules, function($a, $b) {
            return count($b['result']) <=> count($a['result']);
        });

        return $this->renderModView('@Eckinox/application/mastersearch/index.html.twig', array(
            'user' => $this->getUser(),
            'modules' => $modules,
            'activeModules' => $activeModules ?: array_keys($modules),
            'terms' => $request->request->get('terms') ?: $request->query->get('terms'),
            'title' => $this->lang('title.'.$request->get('_route')),
        ), $request);
    }


    public function getFormTypes($formsPath, $namespace = null, $domain = null) {
        $forms = [];
        $scan = file_exists($formsPath) && is_dir($formsPath) ? scandir($formsPath) : [];

        foreach($scan as $form) {
            if(!in_array($form, ['.', '..', '.gitignore'])) {
                $path = $formsPath . '/' . $form;

                if(is_dir($path)) {
                    $forms = array_merge($forms, $this->getFormTypes($path, $namespace, $form));
                } else {
                    list($formName, $formExt) = explode('.', $form);
                    if ($formExt != 'php') continue;

                    $class = ( $namespace ?: 'App\\Form' ) . '\\';
                    $class .= $domain ? $domain . '\\' . $formName : $formName;

                    $forms[] = $class;
                }
            }
        }

        return $forms;
    }

    public function addModule($listing, $fields, $type = null) {
        $module = [];
        $module['module'] = $listing['module'];
        $module['domain'] = $listing['domain'];
        $module['module_class'] = $listing['module_class'] ?? null;
        $module['redefines'] = $listing['redefines'] ?? null;

        $type ? $module['type'] = $type : false;

        foreach ($fields as $field) {
            if (($id = array_search($field, array_column($listing['fields'], 'name'))) !== false) {
                $module['fields'][] = $listing['fields'][$id] + ["search" => [
                    "clause" => "orWhere"
                ]];
            }
        }

        return $module;
    }

    public function normalizeTerms($terms) {
        $terms = explode(' ', $terms);
        $arr = [];

        if(count($terms) > 1) {
            foreach($terms as $key => $term) {
                if(strlen($term) > 2) {
                    $arr[] = strtolower($term);
                }
            }
        } else {
            $arr = strtolower($terms[0]);
        }

        return $arr;
    }
}
