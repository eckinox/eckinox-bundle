<?php

namespace Eckinox\Controller\Application;

use Eckinox\Library\Symfony\Controller;
use Eckinox\Library\Symfony\Annotation\JsEntityProperty;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\JsonResponse;

class JsEntityController extends Controller
{
    /**
     * @Route("/json/entity", name="json_entity")
     */
    public function getEntityJson(Request $request, $entityName)
    {
        // TODO: load only one entity by id

        $json = [];
        $className = strtr('App\Entity\%entity%', [ '%entity%' => $entityName ]);

        list($properties, $relations) = $this->getRequestParameters($className);

        $rows = $this->getDoctrine()
            ->getRepository($className)
            ->findAllRows($properties, $relations);

        return new JsonResponse($rows);
    }

    private function getRequestParameters($className) {
        $reader = new AnnotationReader();
        $reflectionClass = new \ReflectionClass($className);

        $properties = [];
        $relations = [];

        foreach($reflectionClass->getProperties() as $property){
            $annotation = $reader->getPropertyAnnotation($property, JsEntityProperty::class);

            if($annotation) {
                $isEntity = property_exists($reader->getPropertyAnnotation($property, ORM\Annotation::class), 'targetEntity');
                $protertyName = $annotation->getName() ?: $property->name;

                $properties[] = $protertyName;

                if($isEntity) {
                    $relations[] = $protertyName;
                }
            }
        }

        return [ $properties, $relations ];
    }
}
