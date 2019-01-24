<?php

namespace Eckinox\Controller\Application;

use Eckinox\Library\Symfony\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Eckinox\Library\Symfony\Annotation\Lang;
use Eckinox\Library\General\StringEdit;
use Doctrine\ORM\NonUniqueResultException;

/**
 *  @Lang(domain="application", default_key="import")
 */
class ImportController extends Controller
{
    use \Eckinox\Library\Application\log;
    use \Eckinox\Library\General\appData;

    protected $settings;
    protected $em;

    /**
     * @Route("/import/{importType}", name="index_import")
     */
    public function index(Request $request, $importType)
    {
        $modules = [];
        $activeModules = $request->request->get('modules') ?: $request->query->get('modules');
        $terms = $request->request->get('terms') ?: $request->query->get('terms');

        # If there's an error in the import settings or privileges, redirect to the dashboard with an error message
        try {
            $this->checkImportPrivilege($importType);
            $this->loadImportSettings($importType);
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('home');
        }

        $entity = new $this->settings['entity']();

        return $this->renderModView('@Eckinox/application/import/index.html.twig', array(
            'importType' => $importType,
            'modules' => $modules,
            'settings' => $this->settings,
            'entity' => $entity,
            'title' => $this->lang('title.'.$request->get('_route')),
        ), $request);
    }

    /**
     * @Route("/import/{importType}/process", name="process_import")
     */
    public function process(Request $request, $importType)
    {
        $modules = [];
        $activeModules = $request->request->get('modules') ?: $request->query->get('modules');
        $terms = $request->request->get('terms') ?: $request->query->get('terms');

        # If there's an error in the import settings or privileges, redirect to the dashboard with an error message
        try {
            $this->checkImportPrivilege($importType);
            $this->loadImportSettings($importType);
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('home');
        }

        # Validate and pre-process the POST data
        $data = $this->getProcessingData();
        $useEntity = $this->settings['entity'] ?? false;
        $this->em = $this->getDoctrine()->getManager();
        $containers = [];

        $this->dispatchEvent('data_pre_processing', $data, $this->assignations, $this);
        $this->dispatchEvent('containers_pre_processing', $containers, $this);

        foreach ($data as $rowIndex => $row) {
            if ($useEntity) {
                $entity = $this->getEntityFromRow($row);

                $this->dispatchEvent('container_pre_processing', $entity, $this);
                $this->updateEntityFromRow($entity, $row);
                $this->updateEntityRelationsFromRow($entity, $row);
                $this->dispatchEvent('container_post_processing', $entity, $this);

                $containers[] = $entity;
            } else {
                $container = $row;
                $this->dispatchEvent('container_pre_processing', $container, $this);
                $containers[] = $container;
            }
        }

        $this->dispatchEvent('containers_post_processing', $containers, $this);

        # Persist and save entities
        if ($useEntity) {
            try {
                foreach ($containers as $entity) {
                    $this->em->persist($entity);
                }
                $this->em->flush();
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
                return $this->redirectToRoute('index_import', ['importType' => $importType]);
            }
        }

        $this->addFlash('success', $this->get('translator')->trans('import.success', [], 'application'));

        return $this->redirectToRoute('index_import', ['importType' => $importType]);
    }

    protected function updateEntityFromRow(&$entity, $row) {
        foreach ($entity->getClassProperties() as $property) {
            if ($this->settings['properties'][$property]['disabled'] ?? false) {
                continue;
            }

            if (isset($this->settings['properties'][$property]['value'])) {
                $value = $this->settings['properties'][$property]['value'];

                # Allow for values using custom fields by prepending custom: before the input's name
                if (strpos($value, 'custom:') === 0) {
                    if (($_POST[explode(':', $value)[1]] ?? null) != $entity->get($property)) {
                        $entity->set($property, ($_POST[explode(':', $value)[1]] ?? null));
                    }
                } else {
                    $entity->set($property, $value);
                }
            } else if (isset($this->assignations[$property])) {
                $entity->set($property, $row[$this->assignations[$property]]);
            }
        }
    }

    protected function getEntityFromRow($row) {
        $loadingFields = $this->settings['loadFrom'] ?? false;
        $entity = new $this->settings['entity']();

        # If no loading fields are defined, return a new entity everytime
        if (!$loadingFields) {
            return $entity;
        }

        $queryEntityName = str_replace('Entity:', '', str_replace('\\', ':', $this->settings['entity']));
        $queryString = 'SELECT e FROM ' . $queryEntityName . ' e WHERE 1 = 1 ';
        $parameters = [];

        # Don't load archived entities...
        if (property_exists($entity, 'isArchived')) {
            $queryString .= ' AND e.isArchived = false';
        }
        # Or deleted ones...
        if (property_exists($entity, 'isDeleted')) {
            $queryString .= ' AND e.isDeleted = false';
        }
        # Or deleted ones, using statuses...
        if (property_exists($entity, 'status')) {
            $queryString .= ' AND e.status != "deleted"';
        }

        foreach ($loadingFields as $field) {
            if (isset($this->assignations[$field]) && isset($row[$this->assignations[$field]])) {
                $queryString .= ' AND e.' . $field . ' = :' . $field;
                $parameters[$field] = $row[$this->assignations[$field]];
            }
        }

        $query = $this->em->createQuery($queryString);
        $query->setParameters($parameters);
        $query->useQueryCache(true);

        try {
            $entity = $query->getOneOrNullResult() ?: $entity;
        } catch (NonUniqueResultException $e) {
            throw new \Exception("The loadingFrom fields specified in the import settings don't always result in a single unique record.");
        }

        return $entity;
    }

    protected function updateEntityRelationsFromRow(&$entity, $row) {
        foreach ($this->settings['properties'] as $property => $infos) {
            $relationClass = $this->settings['properties'][$property]['relation'] ?? null;

            if (!$relationClass || ($this->settings['properties'][$property]['disabled'] ?? false)) {
                continue;
            }

            $relation = $this->getRelationFromEntityProperty($entity, $property);
            $updated = false;

            # Loop over allowed properties defined in the configs to update them
            foreach ($infos['allowedProperties'] as $relationProperty) {
                $relationPropertyKey = $property . '.' . $relationProperty;
                if (isset($this->settings['properties'][$property]['values'][$relationProperty])) {
                    if ($relation->get($relationProperty) != $this->settings['properties'][$property]['values'][$relationProperty]) {
                        $relation->set($relationProperty, $this->settings['properties'][$property]['values'][$relationProperty]);
                        $updated = true;
                    }
                } else if (isset($this->assignations[$relationPropertyKey]) && $relation->get($relationProperty) != $row[$this->assignations[$relationPropertyKey]]) {
                    $relation->set($relationProperty, $row[$this->assignations[$relationPropertyKey]]);
                    $updated = true;
                }
            }

            # Only persist the relation entity if it's been updated and/or contains values other than the association fields
            if ($updated) {
                $this->em->persist($relation);
            }
        }
    }

    protected function getRelationFromEntityProperty(&$entity, $property) {
        $relation = null;

        if ($entity->get($property) instanceof \Doctrine\Common\Collections\ArrayCollection || $entity->get($property) instanceof \Doctrine\ORM\PersistentCollection) {
            # Check if one of the collection's items is a match for our current entity
            foreach ($entity->get($property) as $collectionItem) {
                $matching = true;

                foreach ($this->settings['properties'][$property]['loadFrom'] as $relationKey => $originKey) {
                    if ($originKey == 'this' && $entity != $collectionItem->get($relationKey)) {
                        $matching = false;
                    } else if (strpos($originKey, 'custom:') === 0) {
                        $key = explode(':', $originKey)[1];
                        $value = $_POST[$key] ?? null;
                        if (isset($this->settings['customFields'][$key]['entity']) && $value && ((!$collectionItem->get($relationKey) || !$value) || ($collectionItem->get($relationKey) && $collectionItem->get($relationKey)->getId() != $value))) {
                            $matching = false;
                        } else if (!isset($this->settings['customFields'][$key]['entity']) && $value != $collectionItem->get($relationKey)) {
                            $matching = false;
                        }
                    } else if ($originKey != 'this' && $entity->get($originKey) != $collectionItem->get($relationKey)) {
                        $matching = false;
                    }
                }

                if ($matching) {
                    $relation = $collectionItem;
                    break;
                }
            }
        } else if ($entity->get($property) instanceof $this->settings['properties'][$property]['relation']) {
            $relation = $entity->get($property);
        }

        # If no relation entity was found, create a new one
        if (!$relation) {
            $relation = new $this->settings['properties'][$property]['relation']();
            foreach ($this->settings['properties'][$property]['loadFrom'] as $relationKey => $originKey) {
                if ($originKey == 'this') {
                    $relation->set($relationKey, $entity);
                } else if (strpos($originKey, 'custom:') === 0) {
                    $key = explode(':', $originKey)[1];
                    $value = $_POST[$key] ?? null;

                    if (isset($this->settings['customFields'][$key]['entity'])) {
                        $relation->set($relationKey, $value ? $this->getDoctrine()->getRepository($this->settings['customFields'][$key]['entity'])->find($value) : null);
                    } else {
                        $relation->set($relationKey, $value);
                    }
                } else {
                    $relation->set($relationKey, $entity->get($originKey));
                }
            }
        }

        return $relation;
    }

    protected function getProcessingData() {
        if (empty($_POST)) {
            throw new \Exception("No POST data to process.");
        }

        $data = json_decode($_POST['data'] ?? '[]');
        $assignations = array_filter($_POST['assignation'] ?? []);
        $startingLine = $_POST['starting_line'] ?? 1;

        if (!count($data)) {
            throw new \Exception("No import data to process.");
        }

        if (!count($assignations)) {
            throw new \Exception("No column assignations were made.");
        }

        if ($startingLine > count($data)) {
            throw new \Exception("Starting line is too high for the number of data rows.");
        }

        $data = array_slice($data, $startingLine - 1);
        # Remove empty lines from the import data
        $data = array_filter($data, function($row){ return count(array_filter($row)); });

        $this->assignations = array_flip($assignations);

        return $data;
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
        if (isset($settings['entity']) && !class_exists($settings['entity'])) {
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

        $this->settings = $settings;
    }

    protected function checkImportPrivilege($importType) {
        if (!$this->getUser()->hasPrivilege('IMPORT_' . strtoupper(StringEdit::camelToSnakeCase($importType)))) {
            $error = $this->get('translator')->trans('import.errors.privilege', [], 'application');
            throw new \Exception($error);
        }
    }

    protected function dispatchEvent($event, &...$args) {
        if (!isset($this->settings['events'][$event])) {
            return;
        }

        $listeners = $this->settings['events'][$event];

        if (is_string($listeners)) {
            list($class, $method) = $this->parseListenerString($listeners);
            call_user_func_array([$class, $method], $args);
        } else if (is_array($listeners)) {
            foreach ($listeners as $listener) {
                if (is_string($listener)) {
                    list($class, $method) = $this->parseListenerString($listener);
                    call_user_func_array([$class, $method], $args);
                } else if (isset($listener['class']) && isset($listener['method'])) {
                    call_user_func_array([$listener['class'], $listener['method']], $args);
                }
            }
        }
    }

    private function parseListenerString($string) {
        return explode('::', $string);
    }
}
