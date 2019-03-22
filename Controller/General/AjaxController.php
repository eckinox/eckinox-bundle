<?php

namespace Eckinox\Controller\General;

use Eckinox\Library\Symfony\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Eckinox\Library\General\Arrays;
use Eckinox\Library\General\Convert;
use Eckinox\Library\General\Serializer;
use Eckinox\Library\Symfony\Annotation\Security;
use Eckinox\Library\Symfony\Service\Converter;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;

class AjaxController extends Controller
{
    use \Eckinox\Library\Application\log;
    use \Eckinox\Library\General\appData;

    protected $securityRedirect = 'home';
    protected $converter;

    /**
    * @param ContainerInterface $container
    */
    public function __construct(ContainerInterface $container, Converter $converter)
    {
        $this->container = $container;
        $this->converter = $converter;
    }

    /**
     * @Route("/ajax/get/cities/{region_key}", name="ajax_get_cities")
     */
    public function getCities($region_key, $return_response = true)
    {
        $array = [];
        $cities = $region_key ? Arrays::groupBy($this->data('localities.cities'), 'region_key') : $this->data('localities.cities');

        if($region_key) {
            $cities = isset($cities[$region_key]) ? $cities[$region_key] : array();
        }

        foreach($cities as $key => $city) {
            $array[$key] = $city['name'];
        }

        asort($array);

        if($return_response) {
            $response = new Response();

            $response->setContent(json_encode($array));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        } else {
            return $array;
        }
    }

    /**
     * @Route("/ajax/get/boroughts/{city_key}", name="ajax_get_boroughts")
     */
    public function getBoroughts($city_key = null, $return_response = true)
    {
        $array = array();
        $boroughts = $city_key ? Arrays::groupBy($this->data('localities.boroughts'), 'city_key') : $this->data('localities.boroughts');

        if($city_key) {
            $boroughts = isset($boroughts[$city_key]) ? $boroughts[$city_key] : array();
        }

        foreach($boroughts as $key => $borought) {
            $array[$key] = $borought['name'];
        }

        asort($array);

        if($return_response) {
            $response = new Response();

            $response->setContent(json_encode($array));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        } else {
            return $array;
        }
    }

    /**
     * @Route("/ajax/get/regions/{province_key}", name="ajax_get_regions")
     */
    public function getRegions($province_key = null, $return_response = true)
    {
        $array = [];
        $regions = $province_key ? Arrays::groupBy($this->data('localities.regions'), 'province_key') : $this->data('localities.regions');

        if($province_key) {
            $regions = isset($regions[$province_key]) ? $regions[$province_key] : array();
        }

        foreach($regions as $key => $region) {
            $array[$key] = $region['name'];
        }

        asort($array);

        if($return_response) {
            $response = new Response();

            $response->setContent(json_encode($array));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        } else {
            return $array;
        }
    }

    /**
     * @Route("/ajax/listing/edit", name="ajax_listing_edit")
     */
    public function editVariable(Request $request)
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $result = [];

        /*
         * Get domain and module
         */
        $domain = ucfirst($request->request->get('domain'));
        $module = ucfirst($request->request->get('module'));
        $field = $request->request->get('field');
        $id = $request->request->get('id');
        $value = $request->request->get('value');

        $entity = sprintf("\App\Entity\%s\%s", $domain, $module);
        $formType = sprintf("\App\Form\%s\%sType", $domain, $module);

        try {
            /*
             * Check if classes exist
             */
            if(!class_exists($entity)) throw new Exception("Entity ".$entity." doesn't exist");
            if(!class_exists($formType)) throw new Exception("FormType ".$formType." doesn't exist");
            if(!$id) throw new Exception("Id is empty");

            /*
             * Get editable fields from FormType::getListing()
             */
            if(method_exists($formType, 'getListing')) {
                $listing = $formType::getListing($this, $this->getDoctrine());

                $fields = array_reduce($listing['fields'], function ($result, $item) {
                    if(isset($item['editable']) && $item['editable']) $result[$item['name']] = $item['editable'];
                    return $result;
                }, []);

                /*
                 * Check if field is editable
                 */
                if(!isset($fields[$field])) throw new Exception("Field ".$entity."::".$field." is not editable or doesn't exist");

                /*
                 * No error, we can proceed !
                 */
                $object = $this->getDoctrine()
                    ->getRepository($entity)
                    ->find($id);
                $function = "set".ucfirst($field);

                if(!$object) throw new Exception("Empty object ".$entity." with id ".$id);
                if(!method_exists($entity, $function)) throw new Exception("Method ".$entity."::".$function." doesn't exist");

                $em = $this->getDoctrine()->getManager();

                /*
                 * Check if we have to load an entity
                 */
                if(isset($fields[$field]['entity']) && $fields[$field]['entity']) {
                    if(!is_numeric($value)) throw new Exception("The value isn't numeric, we can't load " . $fields[$field]['entity']);

                    $value = $this->getDoctrine()
                        ->getRepository($fields[$field]['entity'])
                        ->find($value);
                }

                $object->$function($value);
                $object->setUpdatedAt();

                $em->persist($object);
                $em->flush();

                $result = ["result" => "OK"];

            } else {
                throw new Exception("Method ".$formType."::getListing doesn't exist");
            }
        } catch (Exception $e) {
            $result = [
                "result" => "error",
                "message" => $e->getMessage(),
            ];
        }



        $response->setContent(json_encode($result));
        return $response;
    }

    /**
     * @Route("/ajax/listing/get/form-list", name="ajax_listing_get_form_list")
     */
    public function getFormList(Request $request)
    {
        /*
         * Get domain and module
         */
        $domain = ucfirst($request->request->get('domain'));
        $module = ucfirst($request->request->get('module'));
        $name = $request->request->get('name');
        $id = $request->request->get('id');

        $entity = sprintf("\App\Entity\%s\%s", $domain, $module);
        $formType = sprintf("\App\Form\%s\%sType", $domain, $module);

        try {
            /*
             * Check if classes exist
             */
            if(!class_exists($entity)) throw new Exception("Entity ".$entity." doesn't exist");
            if(!class_exists($formType)) throw new Exception("FormType ".$formType." doesn't exist");
            if(!$id) throw new Exception("Id is empty");

            /*
             * Get editable lists from FormType::getListing()
             */
            if(method_exists($formType, 'getListing')) {
                $listing = $formType::getListing($this, $this->getDoctrine());

                $lists = array_reduce($listing['lists'], function ($result, $item) {
                    if(isset($item['editable']) && $item['editable']) $result[$item['name']] = $item['editable'];
                    return $result;
                }, []);

                /*
                 * Check if field is editable
                 */
                if(!isset($lists[$name])) throw new Exception("Field ".$entity."::".$name." is not editable or doesn't exist");

                /*
                 * No error, we can proceed !
                 */
                $object = $this->getDoctrine()
                    ->getRepository($entity)
                    ->find($id);

                if(!$object) throw new Exception("Empty object ".$entity." with id ".$id);

                $parameters = [];
                $parameters[lcfirst($module)] = $object;

                /*
                 * Set form-list variables
                 */
                if(isset($lists[$name]['vars']) && $lists[$name]['vars']) {
                    foreach($lists[$name]['vars'] as $var => $values) {
                        if(isset($values['entity']) && $values['entity']) {
                            $entity = sprintf("\App\Entity\%s",  $values['entity']);
                            $function = $values['function'];

                            $parameters[$var] = $this->getDoctrine()
                                ->getRepository($entity)
                                ->$function($object->getId());

                        } else {
                            $parameters[$var] = $values;
                        }
                    }
                }

                /*
                 * Load formlist view
                 */
                return $this->render($lists[$name]['view'], $parameters);

            } else {
                throw new Exception("Method ".$formType."::getListing doesn't exist");
            }
        } catch (Exception $e) {
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');

            $result = [
                "result" => "error",
                "message" => $e->getMessage(),
            ];

            $response->setContent(json_encode($result));
            return $response;
        }

    }

    /**
     * @Route("/ajax/listing/update/form-list", name="ajax_listing_update_form_list")
     */
    public function updateFormList(Request $request)
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');

        /*
         * Get domain and module
         */
        $domain = ucfirst($request->request->get('domain'));
        $module = ucfirst($request->request->get('module'));
        $name = $request->request->get('name');
        $id = $request->request->get('id');
        $values = urldecode($request->request->get('values'));

        $entity = sprintf("\App\Entity\%s\%s", $domain, $module);
        $formType = sprintf("\App\Form\%s\%sType", $domain, $module);

        try {
            /*
             * Parse values
             */
            parse_str($values, $values);
            if($values) {
                $values = $values[key($values)];
            }

            /*
             * Check if classes exist
             */
            if(!class_exists($entity)) throw new Exception("Entity ".$entity." doesn't exist");
            if(!class_exists($formType)) throw new Exception("FormType ".$formType." doesn't exist");
            if(!$id) throw new Exception("Id is empty");

            /*
             * Get editable lists from FormType::getListing()
             */
            if(method_exists($formType, 'getListing')) {
                $listing = $formType::getListing($this, $this->getDoctrine());

                $lists = array_reduce($listing['lists'], function ($result, $item) {
                    if(isset($item['editable']) && $item['editable']) $result[$item['name']] = $item['editable'];
                    return $result;
                }, []);

                /*
                 * Check if field is editable
                 */
                if(!isset($lists[$name])) throw new Exception("Field ".$entity."::".$name." is not editable or doesn't exist");

                /*
                 * No error, we can proceed !
                 */
                $object = $this->getDoctrine()
                    ->getRepository($entity)
                    ->find($id);
                $function = "set".ucfirst($name);

                if(!$object) throw new Exception("Empty object ".$entity." with id ".$id);

                $em = $this->getDoctrine()->getManager();

                /*
                 * Check if we have to use a service to handle the form-list
                 */
                if(isset($lists[$name]['handler']) && $lists[$name]['handler']) {
                    $request->request->set($name, $values);
                    $handler = $lists[$name]['handler'];

                    $this->get($handler)->handle($request, $object);
                } else {
                    if(!method_exists($entity, $function)) throw new Exception("Method ".$entity."::".$function." doesn't exist");

                    $object->$function($values);
                }

                $object->setUpdatedAt();

                $em->persist($object);
                $em->flush();

                $result = [
                    "result" => "success",
                    "message" => $this->trans(
                        implode('.', [lcfirst($module), 'list', $name, 'messages', 'success', "hasBeenUpdated"]),
                        [],
                        lcfirst($domain)
                    )
                ];

            } else {
                throw new Exception("Method ".$formType."::getListing doesn't exist");
            }
        } catch (Exception $e) {
            $result = [
                "result" => "error",
                "message" => $e->getMessage(),
            ];
        }

        $response->setContent(json_encode($result));
        return $response;
    }

    /**
     * @Route("/ajax/get/json-from-excel", name="ajax_get_json_from_excel")
     */
    public function getJsonFromExcel(Request $request)
    {
        set_time_limit(0);

        $domain = 'application';
        $file = $request->files->get('file');

        if (!$file) {
            throw new \Exception($this->trans('ajax.excelToJson.errors.noFile', [], $domain));
        }

        $filePath = $file->getPathname();
        $parsedData = $this->converter->excelToArray($filePath);

        return new JsonResponse($parsedData);
    }


    /**
     * @Route("/ajax/get/autocomplete-entities", name="ajax_get_autocomplete_entities")
     */
    public function getAutocompleteEntities(Request $request)
    {
        $data = [];
        $entityClass = str_replace('\\\\', '\\', $request->request->get('entity'));
        $wheres = $request->request->get('where') ? json_decode($request->request->get('where')) : [];
        $searchFields = $request->request->get('search') ? json_decode($request->request->get('search')) : null;
        $orderByFields = $request->request->get('order') ? json_decode($request->request->get('order')) : [];
        $key = $request->request->get('key') ? $request->request->get('key') : 'id';
        $label = $request->request->get('label') ? $request->request->get('label') : 'id';
        $uniqueLabel = $request->request->get('unique_label') ? (bool)$request->request->get('unique_label') : false;
        $em = $this->getDoctrine()->getManager();

        if ($entityClass && class_exists($entityClass) && $searchFields) {
            $entities = $this->getDoctrine()
                ->getRepository($entityClass)
                ->findAll();

            $queryString = 'SELECT e FROM ' . $entityClass . ' e WHERE 1 = 1';
            $parameters = [];

            $whereIndex = 0;
            foreach ($wheres as $field => $value) {
                if (property_exists($entityClass, $field)) {
                    if (is_array($value)) {
                        $queryString .= ' AND e.' . $field . ' IN (:where_term' . $whereIndex . ')';
                        $parameters['where_term' . $whereIndex] = $value;
                    } else if (is_object($value) && property_exists($value, 'entity') && property_exists($value, 'id')) {
                        $queryString .= ' AND e.' . $field . ' = :where_term' . $whereIndex;
                        $parameters['where_term' . $whereIndex] = $em->getReference($value->entity, $value->id);
                    } else if (is_numeric($value)) {
                        $queryString .= ' AND e.' . $field . ' = :where_term' . $whereIndex;
                        $parameters['where_term' . $whereIndex] = $value;
                    } else {
                        $queryString .= ' AND e.' . $field . ' LIKE :where_term' . $whereIndex;
                        $parameters['where_term' . $whereIndex] = $value;
                    }
                } else {

                }

                $whereIndex++;
            }

            $queryString .= ' AND (1 = 0';

            if ($request->request->get('mode') == 'strict') {
                foreach ($searchFields as $index => $field) {
                    if (property_exists($entityClass, $field)) {
                        $queryString .= ' OR e.' . $field . ' LIKE :term' . $index;
                        $parameters['term' . $index] = '%' . $request->request->get('query') . '%';
                    }
                }
            } else {
                $searchQuery = $request->request->get('query');
                if ($searchQuery && substr($searchQuery, -1) == ')' && strpos($searchQuery, ' (') !== false) {
                    $searchQuery = substr($searchQuery, 0, strlen($searchQuery) - 1);
                    $searchQuery = str_replace(' (', ' ', $searchQuery);
                }

                $searchQueryParts = array_filter(explode(' ', $searchQuery));
                $queryString .= ' OR ((1 = 1) ';
                foreach ($searchQueryParts as $wordIndex => $word) {
                    $queryString .= ' AND (0 = 1';
                    foreach ($searchFields as $index => $field) {
                        if (property_exists($entityClass, $field)) {
                            $queryString .= ' OR e.' . $field . ' LIKE :term' . $wordIndex . '_' . $index;
                            $parameters['term' . $wordIndex . '_' . $index] = '%' . $word . '%';
                        }
                    }
                    $queryString .= ')';
                }
                $queryString .= ')';
            }

            $queryString .= ')';

            $firstOrdering = true;
            foreach ($orderByFields as $field => $direction) {
                if (property_exists($entityClass, $field) && in_array(strtoupper($direction), ['ASC', 'DESC'])) {
                    $queryString .= ($firstOrdering ? 'ORDER BY' : ',') . ' e.' . $field . ' ' . $direction;
                    $firstOrdering = false;
                }
            }

            $query = $em->createQuery($queryString)
                    ->setParameters($parameters);
            $entities = $query->execute();

            $foundLabels = [];
            foreach ($entities as $entity) {
                if ($uniqueLabel && in_array($entity->get($label), $foundLabels)) {
                    continue;
                }

                $data[] = ['key' => $entity->get($key), 'label' => $entity->get($label), 'object' => method_exists($entity, 'getRawObject') ? $entity->getRawObject() : json_decode(json_encode($entity))];
                $foundLabels[] = $entity->get($label);
            }
            try {
            } catch (\Exception $e) {}
        }

        return new JsonResponse($data);
    }
}
