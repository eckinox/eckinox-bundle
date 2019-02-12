<?php

namespace Eckinox\Library\Symfony\Annotation;

use Eckinox\Library\Symfony\AnnotationHelper;


/**
 * Annotation class for @Breacrumb().
 *
 * @Annotation
 * @Target({"METHOD", "CLASS"})
 */
class Breadcrumb
{
    protected static $list = [];

    private $parent = false;

    private $langKey = "";

    public function __construct(array $parameters)
    {
        $this->setParent($parameters['parent'] ?? false);
        $this->setLangKey($parameters['lang_key'] ?? "");
    }

    public function generate($controller, $request, $vars) {
        $breadcrumb = [];
        $routes = $request->attributes->get('router')->getRouteCollection()->all();

        # This page
        $parent = $vars['parent'] ?? $this->getParent();
        $i = 0;

        $current_route = $request->attributes->get('_route');

        $breadcrumb[$current_route] = [
            'name' => $this->_lang_from($current_route, $controller),
            'vars' => $vars[$current_route] ?? [],
            'link' => false
        ];

        do {
            if ( $annotation = $this->routeAnnotation($parent, $routes, $request->attributes->get('reader')) ) {

                $key = key($annotation);
                $breadcrumb_obj = $annotation[$key];

                $ctrl = new $key($controller->translator);

                $breadcrumb[$parent] = [
                    'name' => $controller->lang($ctrl->lang_get_default_key().".breadcrumb.$parent", [], $ctrl->lang_get_domain(), false),
                    'vars' => $vars[$parent] ?? [],
                    'link' => true
                ];
            }
        } while( $parent = $breadcrumb_obj->getParent() );


        return array_reverse($breadcrumb);
    }

    protected function routeAnnotation($path, $list, $reader) {

        if ( $ctrl = $list[$path] ?? false ) {
            list($controller, $methodName) = explode('::', $ctrl->getDefaults()["_controller"] ?? []);

            $obj = $reader->getMethodAnnotations(new \ReflectionMethod($controller, $methodName), Breadcrumb::class);

            foreach($obj as $item) {
                if ($item instanceof Breadcrumb) {
                    return [ $controller => $item ];
                }
            }
        }

    }

    protected function getParent() {
        return $this->parent;
    }

    protected function setParent($set = null) {
         $this->parent = $set;
    }

    protected function getLangKey() {
        return $this->langKey;
    }

    protected function setLangKey($set = null) {
         $this->langKey = $set;
    }

    protected function _lang_from($key, $obj) {
        return $obj->lang("breadcrumb.$key", [], null, true);
    }

}
