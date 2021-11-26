<?php

namespace Eckinox\Controller\General;

use Eckinox\Library\Symfony\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Eckinox\Library\Symfony\Annotation\Cron as CronAnnotation;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Common\Annotations\AnnotationReader;
use Eckinox\Library\General\Cron;


class CronController extends Controller
{
    use \Eckinox\Library\Application\log;
    use \Eckinox\Library\General\appData;

    private $controllers = [];
    private $cronMethods = [];

    /**
     * @Route("/cron", name="cron")
     */
    public function cron(Request $request) {
        $cron = new Cron();
        $controllersPath = $this->getParameter('app.controllers.path');

        /*
         * Get all controllers
         */
        foreach($controllersPath as $namespace => $path) {
            $this->controllers = array_merge($this->controllers, $this->getControllers($path, $namespace));
        }

        /*
         * Get the methods that have the annotation @Cron
         */
        $this->cronMethods = $this->getCronMethods($this->controllers);

        /*
         * Call methods
         */

        foreach($this->cronMethods as $c) {
            $crontab = $c['annotation']->getTab();

            if ( ( strpos($crontab, '(') !== false) &&
                 ( strpos($crontab, ')') !== false ) ) {

                list($func, $arg) = explode('(', $crontab);
                $arg = rtrim($arg, ')');

                $cron = $cron->$func( $arg );
            }
            else {
                $cron->setTab($crontab);
            }

            $cron->run(function() use ($c, $request){
                $this->forward($c['method'], [$request]);
            });
        }

        return new Response('');
    }

    public function getControllers($controllersPath, $namespace = null, $domain = null) {
        $controllers = [];
        $scan = scandir($controllersPath);

        foreach($scan as $controller) {
            if(!in_array($controller, ['.', '..', '.gitignore'])) {
                $path = $controllersPath . '/' . $controller;

                if(is_dir($path)) {
                    $controllers = array_merge($controllers, $this->getControllers($path, $namespace, $controller));
                } else {
                    list($controllerName, $controllerExt) = explode('.', $controller);
                    if ($controllerExt != 'php') continue;

                    $class = ( $namespace ?: 'App\\Controller' ) . '\\';
                    $class .= $domain ? $domain . '\\' . $controllerName : $controllerName;

                    $controllers[] = $class;
                }
            }
        }

        return $controllers;
    }

    public function getCronMethods($controllers) {
        $cronMethods = [];
        $annotationReader = new AnnotationReader();

        foreach($controllers as $class) {
            $reflectedClass = new \ReflectionClass($class);

            foreach($reflectedClass->getMethods() as $reflectedMethod) {
                if($annotation = $annotationReader->getMethodAnnotation($reflectedMethod, CronAnnotation::class)) {
                    $cronMethods[] = [
                        "method" => $class . '::' . $reflectedMethod->getName(),
                        "annotation" => $annotation,
                    ];
                }
            }
        }

        return $cronMethods;
    }
}
