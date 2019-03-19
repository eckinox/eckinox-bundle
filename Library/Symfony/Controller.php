<?php

namespace Eckinox\Library\Symfony;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Contracts\Translation\TranslatorInterface;

use Eckinox\Library\{
    Application\log,
    General\appData,
    Symfony\Annotation\Breadcrumb
};

class Controller extends AbstractController {
    use log, appData;

    protected $securityRedirect = 'home';
    public $translator = null;

    public function __construct(TranslatorInterface $translator) {
        $this->translator = $translator;
    }

    public function getSecurityRedirect() {
        return $this->securityRedirect;
    }

    protected function renderModView(string $view, array $parameters = [], Request $request, Response $response = null): Response {
        $parameters['breadcrumb'] ?? $parameters['breadcrumb'] = $this->generate_breadcrumb( $request, $parameters['breadcrumbVariables'] ?? [] );

        if($request->isXmlHttpRequest()) {
            $response = new Response();
            $result = array_merge($parameters['result'] ?? [], ["result" => "OK"]);

            if($parameters['redirect'] ?? false){
               $result = array_merge($result, ["redirectUrl" => $parameters['redirect']]);
           } else {
               foreach($this->get('session')->getFlashBag()->all() as $type => $messages) {
                   $result["messages"][$type] = $messages;
               }

               $result['html'] = $this->renderView($view, $parameters, $response);
           }

            $response->setContent(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }

        return $this->render($view, $parameters, $response);
    }

    public function renderText($content) {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/plain');
        $response->setContent($content);

        return $response;
    }

    protected function generate_breadcrumb($request, $vars) {
        foreach( (array) $request->attributes->get('annotation') as $item) {
            # We generate the first Breadcrumb() annotation we find
            if ($item instanceof Breadcrumb) {
                return $item->generate($this, $request, $vars);
            }
        }

        return [];
    }

    public function getDoctrineInstance() {
        return $this->getDoctrine();
    }

    protected static function getEntityManagerInstance($controller) {
        static $em = null;

        if($em === null) {

            $em = $controller->getDoctrine()->getManager();

            register_shutdown_function(function() use ($em) {
                $em->flush();
            });
        }

        return $em;
    }

    public function getFormValues($form, $additionalFields = []) {
        $values = $additionalFields;

        foreach($form as $sections) {
            foreach($sections as $field) {

                $value = $field->getViewData();
                $field_name = $field->getName();

                $values[$field_name] = $value;
            }
        }

        return $values;
    }

    public function getFormErrors($form, $errors = null) {
        $errors = $errors ?: [];

        foreach ($form as $child) {
             foreach ($child->getErrors() as $error) {
                 $field_name = $this->translator->trans(
                     $child->getConfig()->getOption('label') ?: $child->getName(),
                     [],
                     $this->lang_get_domain()
                 );

                 $errors[] = $field_name . ' : ' . $error->getMessage();
             }

             if(count($child)) {
                 $errors = $this->getFormErrors($child, $errors);
             }
         }

         return $errors;
    }

    public function prepareSearch($request, $listing) {
        $search = [];
        $params = $request->query->get('search') ? $request->query->all() : $request->request->get('search');

        if($params && $params = array_filter($params)) {
            array_filter($listing['fields'], function($field) use ($params, &$search) {
                if(array_key_exists($field['name'], $params)) {
                    $terms = (array)$params[$field['name']];

                    foreach($terms as &$term) {
                        $term = strtolower($term);
                    }

                    $field['terms'] = $terms;

                    $search[] = $field;
                }
            });
        }

        return $search;
    }

    public function trans(...$args) {
        return $this->translator->trans(...$args);
    }

    public function transChoice(...$args) {
        return $this->translator->transChoice(...$args);
    }

    public function getLocalities($type, $key = null, $return_response = false) {
        $controller = $this->get('AjaxController');
        $function = "get".ucfirst($type);

        if(!method_exists($controller, $function)) throw new Exception("Method AjaxController::".$function." doesn't exist");

        return $controller->$function($key, $return_response);
    }
}
