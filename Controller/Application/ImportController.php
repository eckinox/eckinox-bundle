<?php

namespace Eckinox\Controller\Application;

use Eckinox\Library\Symfony\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Cache\Simple\FilesystemCache;
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
    protected $isDryRun;
    public $em;

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
            'autoload_data' => $this->getAutoloadData(),
        ), $request);
    }

    protected function getAutoloadData() {
        if (!($this->settings['autoload'] ?? false)) {
            return null;
        }

        $data = null;
        $cacheKey = $this->get('session')->get('ajax_json2excel_last_cache_key');

        if ($cacheKey) {
            $cache = new FilesystemCache();
            $data = $cache->get($cacheKey, null);
        }

        return $data;
    }

    public function isDryRun() {
        return $this->isDryRun;
    }

    /**
     * @Route("/import/{importType}/preview", name="preview_import")
     * This returns a summary of changes that would occur if the user was to proceed to the import
     */
    public function preview(Request $request, $importType) {
        $response = [
            'success' => true,
            'data' => [
                'deleted' => [],
                'archived' => [],
                'created' => [],
                'updated' => [],
            ]
        ];

        # Run the "process" method in dry run mode to fill the entity manager with the import data
        $error = $this->process($request, $importType, true);
        $response['success'] = !$error;

        if ($response['success']) {
            $unitOfWork = $this->em->getUnitOfWork();
            $unitOfWork->computeChangeSets();

            # Process new entities
            $newEntities = $unitOfWork->getScheduledEntityInsertions();
            foreach ($newEntities as $entity) {
                $class = explode('\\', get_class($entity));
                $class = end($class);
                $response['data']['created'][$class] = ($response['data']['created'][$class] ?? 0) + 1;
            }

            # Process deleted entities
            $deletedEntities = $unitOfWork->getScheduledEntityDeletions();
            foreach ($deletedEntities as $entity) {
                $class = explode('\\', get_class($entity));
                $class = end($class);
                $response['data']['deleted'][$class] = ($response['data']['deleted'][$class] ?? 0) + 1;
            }

            # Process updated entities
            $updatedEntities = $unitOfWork->getScheduledEntityUpdates();
            foreach ($updatedEntities as $entity) {
                $class = explode('\\', get_class($entity));
                $class = end($class);
                $changes = $unitOfWork->getEntityChangeSet($entity);

                foreach ($changes as $property => $values) {
                    if ($values[0] != $values[1]) {
                        if ($property == 'isArchived') {
                            $response['data']['archived'][$class] = ($response['data']['archived'][$class] ?? 0) + 1;
                        } else {
                            $response['data']['updated'][$class] = ($response['data']['updated'][$class] ?? 0) + 1;
                        }

                        break;
                    }
                }
            }
        } else {
            $response['error'] = $error;
        }

        return $this->renderModView('@Eckinox/application/import/results_preview.html.twig', array(
            'importType' => $importType,
            'response' => $response,
            'settings' => $this->settings
        ), $request);
    }

    /**
     * @Route("/import/{importType}/process", name="process_import")
     * This wraps the entire backend import processing, calling events at different stages
     */
    public function process(Request $request, $importType, $isDryRun = false)
    {
        set_time_limit(0);

        $this->isDryRun = $isDryRun;

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

        # Before any of the heavy processing starts, save the session to allow smooth navigating on other pages for the user
        $this->get('session')->isStarted() && $this->get('session')->save();

        try {
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

                    if (!$this->handleEntityArchiving($entity, $row)) {
                        $this->dispatchEvent('container_post_processing', $entity, $this);
                    } else {
                        $this->dispatchEvent('container_post_processing', $entity, $this);
                        $entity = null;
                    }

                    if ($entity) {
                        $containers[] = $entity;
                    }
                } else {
                    $container = $row;
                    $this->dispatchEvent('container_pre_processing', $container, $this);
                    $containers[] = $container;
                }
            }

            $this->dispatchEvent('containers_post_processing', $containers, $this);

            # Persist and save entities
            if ($useEntity) {
                foreach ($containers as $entity) {
                    $this->em->persist($entity);
                }

                if (!$this->isDryRun()) {
                    $this->em->flush();
                }
            }

            $this->dispatchEvent('containers_post_flush', $containers, $this);

            if ($this->isDryRun()) {
                return false;
            }
        } catch (\Exception $e) {
            if ($this->isDryRun()) {
                return $e->getMessage();
            }

            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('index_import', ['importType' => $importType]);
        }

        $this->addFlash('success', $this->trans('import.success', [], 'application'));

        return $this->redirectToRoute('index_import', ['importType' => $importType]);
    }

    /*
     * Checks whether the entity should be archived
     * Any non-empty value that isn't "no" or "non" will be considered as a yes.
     * Returns true if is archived
     */
    protected function handleEntityArchiving(&$entity, $row) {
        $useEntity = $this->settings['entity'] ?? false;

        if (($this->settings['allowArchive'] ?? false) && in_array('_archive_', $this->assignations)) {
            if (isset($this->assignations['_archive_'])) {
                $value = trim($row[$this->assignations['_archive_']] ?? '');

                if (strlen($value) && !in_array(strtolower($value), ['non', 'no'])) {
                    if ($useEntity) {
                        $entity->archive();
                    }

                    return true;
                }
            }
        }

        return false;
    }

    /*
     * Loops over the entity's properties and updates from the specified row's data
     */
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

    /*
     * Loads or creates the import-type entity for the specified row and returns it.
     * The loading of existing entities is based on the loadFrom configurations.
     */
    protected function getEntityFromRow($row) {
        $loadingFields = $this->settings['loadFrom'] ?? false;
        $entity = new $this->settings['entity']();

        # If no loading fields are defined, return a new entity everytime
        if (!$loadingFields) {
            return $entity;
        }

        $this->preloadEssentialRelations($entity, $row);

        $queryEntityName = str_replace('Entity:', '', str_replace('\\', ':', $this->settings['entity']));
        $queryString = 'SELECT e FROM ' . $queryEntityName . ' e WHERE 1 = 1 ';
        $parameters = [];

        foreach ($loadingFields as $field) {
            if (isset($this->assignations[$field]) && isset($row[$this->assignations[$field]])) {
                $queryString .= ' AND e.' . $field . ' = :' . $field;
                $parameters[$field] = $row[$this->assignations[$field]];
            } else if ($this->settings['properties'][$field]['relation'] ?? null) {
                $queryString .= ' AND e.' . $field . ' = :' . $field;
                $parameters[$field] = $entity->get($field) && $entity->get($field)->getId() ? $entity->get($field) : null;
            } else if (array_key_exists('value', $this->settings['properties'][$field])) {
                $queryString .= ' AND e.' . $field . ' = :' . $field;
                $parameters[$field] = $this->settings['properties'][$field]['value'];
            }
        }

        $query = $this->em->createQuery($queryString);
        $query->setParameters($parameters);
        $query->useQueryCache(true);

        try {
            $entity = $query->getOneOrNullResult() ?: $entity;
        } catch (NonUniqueResultException $e) {
            if ($_SERVER['APP_DEBUG'] ?? false) {
                $parametersString = "";
                foreach ($parameters as $key => $value) {
                    $parametersString .= $key . ' => "' . ((is_array($value) || is_object($value)) ? json_encode($value) : $value) . "\"\n";
                }
                throw new \Exception(sprintf("The loadingFrom fields specified in the import settings don't always result in a single unique record.\n\nHere's the query: %s \n\nHere are the parameters: %s, %s", $queryString, $parametersString, implode(', ', $loadingFields)));
            } else {
                throw new \Exception($this->trans('import.errors.genericServerSide'));
            }
        }

        return $entity;
    }

    /*
     * Attempts to load any relation that is mentionned in the loadFrom fields for this entity
     */
    protected function preloadEssentialRelations(&$entity, $row) {
        $loadingFields = $this->settings['loadFrom'] ?? false;
        $properties = $this->settings['properties'] ?? [];
        $entity = new $this->settings['entity']();

        # If no loading fields are defined, return a new entity everytime
        if (!$loadingFields) {
            return $entity;
        }

        foreach ($loadingFields as $field) {
            if (isset($properties[$field]['relation'])) {
                foreach ($properties[$field]['loadFrom'] as $subField) {
                    if (strpos($subField, 'this.') === 0) {
                        $sourceField = str_replace('this.', '', $subField);
                        if (!$entity->get($sourceField)) {
                            $this->updateRelationFromRow($entity, $sourceField, $properties[$sourceField], $row);
                        }
                    }
                }

                $this->updateRelationFromRow($entity, $field, $properties[$field], $row);
            }
        }
    }

    /*
     * Loops over relation properties and calls another method to load/create the relation entity and update it based on the given row.
     */
    protected function updateEntityRelationsFromRow(&$entity, $row) {
        foreach ($this->settings['properties'] as $property => $infos) {
            $relationClass = $this->settings['properties'][$property]['relation'] ?? null;

            if (!$relationClass || ($this->settings['properties'][$property]['disabled'] ?? false)) {
                continue;
            }

            if ($this->settings['properties'][$property]['repeatable'] ?? false) {
                for ($i = 0; $this->repeatableRelationIndexExists($property, $i); $i++) {
                    $this->updateRelationFromRow($entity, $property, $infos, $row, $i);
                }
            } else {
                $this->updateRelationFromRow($entity, $property, $infos, $row);
            }
        }
    }

    /*
     * Checks whether the specified index exists for the specified repeatable relation property
     * This does so by looking inside the assignation POST data to see if anything is assigned to this index
     */
    protected function repeatableRelationIndexExists($property, $index) {
        foreach (($_POST['assignation'] ?? []) as $column => $assignedProperty) {
            if (strpos($assignedProperty, $property . '.' . $index . '.') === 0) {
                return true;
            }
        }

        return false;
    }

    /*
     * Loops over the relation's alllowed properties from the configuration file and updates them based on the given row.
     * If an index is provided, the relation is treated as repeatable, and therefore the index is used to fetch the data inside the row.
     */
    protected function updateRelationFromRow(&$entity, $property, $infos, $row, $index = null) {
        $relation = $this->getRelationFromEntityProperty($entity, $property, $row, $index);

        if (!$relation) {
            return;
        }

        $updated = false;

        $this->dispatchEvent('container_relation_pre_processing', $entity, $relation, $this);

        # Loop over allowed properties defined in the configs to update them
        foreach ($infos['allowedProperties'] as $relationProperty) {
            $relationPropertyKey = $property . '.' . ($index !== null ? $index . '.' : '') . $relationProperty;
            try {
                if (isset($this->settings['properties'][$property]['values'][$relationProperty])) {
                    if ($relation->get($relationProperty) != $this->settings['properties'][$property]['values'][$relationProperty]) {
                        $relation->set($relationProperty, $this->settings['properties'][$property]['values'][$relationProperty]);
                        $updated = true;
                    }
                } else if (isset($this->assignations[$relationPropertyKey]) && $relation->get($relationProperty) != $row[$this->assignations[$relationPropertyKey]]) {
                    $relation->set($relationProperty, $row[$this->assignations[$relationPropertyKey]]);
                    $updated = true;
                }
            } catch (\TypeError $e) {
                # If the relation isn't marked as optional in the configuration, throw the error as it might be a source of data corruption for the system.
                if (!($this->settings['properties'][$property]['optional'] ?? false)) {
                    if ($_SERVER['APP_DEBUG'] ?? false) {
                        throw $e;
                    } else {
                        throw new \Exception($this->trans('import.errors.typeError',
                            ['%property%' => $this->trans(array_reverse(explode('\\', $this->settings['entity']))[0] . '.fields.' . $property, [], 'application')],
                            'application'));
                    }
                }
            }
        }

        $this->dispatchEvent('container_relation_post_processing', $entity, $relation, $updated, $this);

        # Only persist the relation entity if it's been updated and/or contains values other than the association fields
        if ($updated || !$relation->getId()) {
            $this->em->persist($relation);
        }
    }

    /*
     * Loads or creates the relation entity for the specified row and returns it.
     * The loading of existing entities is based on the loadFrom from the relation property's configurations.
     * If an index is provided, the relation is treated as repeatable, and therefore the index is used to fetch the data within the POST.
     */
    protected function getRelationFromEntityProperty(&$entity, $property, $row, $index = null) {
        static $createdRelations = [];
        $relation = null;
        $newRelation = false;

        if ($entity->get($property) instanceof \Doctrine\Common\Collections\ArrayCollection || $entity->get($property) instanceof \Doctrine\ORM\PersistentCollection || count($createdRelations[$property] ?? [])) {
            # Check if one of the collection's items is a match for our current entity
            $itemsToCheck = $createdRelations[$property] ?? [];

            if ($entity->get($property) instanceof \Doctrine\Common\Collections\ArrayCollection || $entity->get($property) instanceof \Doctrine\ORM\PersistentCollection) {
                $itemsToCheck = array_merge($entity->get($property)->toArray(), $itemsToCheck);
            }

            foreach ($itemsToCheck as $collectionItem) {
                $matching = true;

                foreach ($this->settings['properties'][$property]['loadFrom'] as $relationKey => $originKey) {
                    $caseSensitive = $this->settings['properties'][$property]['caseSensitive'] ?? false;

                    $valueB = $collectionItem->get($relationKey);
                    $valueB = !$caseSensitive && is_string($valueB) ? strtoupper(trim($valueB)) : $valueB;

                    if ($originKey == 'this') {
                        # format: "this"
                        # this refers to the current entity, not the relation itself.
                        if ($entity != $collectionItem->get($relationKey)) {
                            $matching = false;
                        }
                    } else if (strpos($originKey, 'this.') === 0) {
                        # format: "this.property"
                        # this refers to the current entity, not the relation itself.
                        $valueA = $entity->get(str_replace('this.', '', $originKey));
                        $valueA = !$caseSensitive && is_string($valueA) ? strtoupper(trim($valueA)) : $valueA;

                        if ($valueA != $valueB) {
                            $matching = false;
                        }
                    } else if (strpos($originKey, 'custom:') === 0) {
                        # format: "custom:fieldName"
                        $key = explode(':', $originKey)[1];
                        $representsEntity = isset($this->settings['customFields'][$key]['entity']);

                        $valueA = $_POST[$key] ?? null;
                        $valueA = !$caseSensitive && is_string($valueA) ? strtoupper(trim($valueA)) : $valueA;

                        if ($representsEntity) {
                            if ((($valueB && !$valueA) || (!$valueB && $valueA)) || ($valueB && $valueB->getId() != $valueA)) {
                                $matching = false;
                            }
                        } else if ($valueA != $valueB) {
                            $matching = false;
                        }
                    } else if (strpos($originKey, 'value:') === 0) {
                        # format: "value:hardcoded value"
                        $valueA = substr($originKey, 6);
                        $valueA = !$caseSensitive && is_string($valueA) ? strtoupper(trim($valueA)) : $valueA;

                        if ($valueA != $valueB) {
                            $matching = false;
                        }
                    } else {
                        $relationPropertyKey = $property . '.' . ($index !== null ? $index . '.' : '') . $originKey;

                        $valueA = $row[$this->assignations[$relationPropertyKey] ?? null] ?? null;
                        $valueA = !$caseSensitive && is_string($valueA) ? strtoupper(trim($valueA)) : $valueA;

                        # format: "property"
                        if (!isset($this->assignations[$relationPropertyKey]) || !isset($row[$this->assignations[$relationPropertyKey]]) || !is_string($valueA) || !is_string($valueB) || $valueB != $valueA) {
                            $matching = false;
                        }
                    }
                }

                if ($matching) {
                    $relation = $collectionItem;
                    if (!$collectionItem->getId()) {
                        $newRelation = true;
                    }
                    break;
                }
            }
        }

        if (!$relation && $entity->get($property) instanceof $this->settings['properties'][$property]['relation']) {
            $relation = $entity->get($property);
        }

        # If no relation entity could be fetched from the current entity, see if it can be loaded from the database
        # It might already exist even if it's not linked to the current $entity (mostly for ManyToMany relations)
        if (!$relation) {
            $relation = $this->loadRelationEntityFromDatabase($entity, $property, $row, $index);
            if ($relation) {
                $newRelation = true;
            }
        }

        # If no relation entity was found still, create a new one
        if (!$relation) {
            $newRelation = true;
            $relation = new $this->settings['properties'][$property]['relation']();
            foreach ($this->settings['properties'][$property]['loadFrom'] as $relationKey => $originKey) {
                if ($originKey == 'this') {
                    # format: "this"
                    # this refers to the current entity, not the relation itself.
                    if ($relation->get($relationKey) instanceof \Doctrine\Common\Collections\ArrayCollection || $relation->get($relationKey) instanceof \Doctrine\ORM\PersistentCollection) {
                        # Add to the collection for ManyToMany or OneToMany relations
                        if (!$relation->get($relationKey)->contains($entity)) {
                            $relation->get($relationKey)->add($entity);
                        }
                    } else {
                        # Assign the current entity as "parent" for OneToOne of ManyToOne relations
                        $relation->set($relationKey, $entity);
                    }
                } else if (strpos($originKey, 'this.') === 0) {
                    # format: "this.property"
                    # this refers to the current entity, not the relation itself.
                    $value = $entity->get(str_replace('this.', '', $originKey));
                    if ($relation->get($relationKey) instanceof \Doctrine\Common\Collections\ArrayCollection || $relation->get($relationKey) instanceof \Doctrine\ORM\PersistentCollection) {
                        if (!$relation->get($relationKey)->contains($value)) {
                            $relation->get($relationKey)->add($value);
                        }
                    } else {
                        $relation->set($relationKey, $value);
                    }
                } else if (strpos($originKey, 'custom:') === 0) {
                    # format: "custom:fieldName"
                    $key = explode(':', $originKey)[1];
                    $value = $_POST[$key] ?? null;

                    if (isset($this->settings['customFields'][$key]['entity'])) {
                        $relation->set($relationKey, $value ? $this->getDoctrine()->getRepository($this->settings['customFields'][$key]['entity'])->find($value) : null);
                    } else {
                        $relation->set($relationKey, $value);
                    }
                } else if (strpos($originKey, 'value:') === 0) {
                    # format: "value:hardcoded value"
                    $relation->set($relationKey, substr($originKey, 6));
                } else {
                    # format: "property"
                    $relationPropertyKey = $property . '.' . ($index !== null ? $index . '.' : '') . $originKey;
                    if (isset($this->assignations[$relationPropertyKey]) && isset($row[$this->assignations[$relationPropertyKey]])) {
                        $relation->set($relationKey, $row[$this->assignations[$relationPropertyKey]]);
                    }
                }
            }

            # If all of the loading fields are null, skip this relation.
            $newRelationIsInvalid = true;
            if ($this->settings['properties'][$property]['optional'] ?? false) {
                foreach ($this->settings['properties'][$property]['loadFrom'] as $relationKey => $originKey) {
                    if ($relation->get($relationKey) !== null) {
                        $newRelationIsInvalid = false;
                        break;
                    }
                }
            } else {
                $newRelationIsInvalid = false;
            }

            if ($newRelationIsInvalid) {
                $relation = null;
                $newRelation = false;
            } else {
                $createdRelations[$property][] = $relation;
            }

        }


        # New relations have to be linked to the entity
        # For ManyToMany or OneToMany, it's important that $relationProperty is the owning side of the relation, otherwise entities won't be persisted correctly.
        if ($newRelation) {
            # Add the new relation to the entity's relation property
            $relationProperty = $entity->get($property);
            if ($relationProperty instanceof \Doctrine\Common\Collections\ArrayCollection || $relationProperty instanceof \Doctrine\ORM\PersistentCollection) {
                if (!$relationProperty->contains($relation)) {
                    $relationProperty->add($relation);
                }
            } else {
                $entity->set($property, $relation);
            }
        }

        return $relation;
    }

    /*
     * Attempts to load an existing entity that satisfies the relation's loadingFrom requirements
     * This is mostly for ManyToMany relations, where entities can exist on their own prior to being linked to other entities.
     */
    protected function loadRelationEntityFromDatabase($entity, $property, $row, $index = null) {
        $loadingFields = $this->settings['properties'][$property]['loadFrom'] ?? false;
        $relation = null;
        $validQuery = false;

        # If no loading fields are defined, return a null value - a relation
        if (!$loadingFields) {
            return $relation;
        }

        $queryEntityName = str_replace('Entity:', '', str_replace('\\', ':', $this->settings['properties'][$property]['relation']));
        $queryString = 'SELECT e FROM ' . $queryEntityName . ' e WHERE 1 = 1 ';
        $parameters = [];

        foreach ($loadingFields as $relationProperty => $field) {
            if ($field == 'this') {
                # format: "this"
                # this refers to the current entity, not the relation itself.
                $queryString .= ' AND e.' . $relationProperty . ' = :' . $field;
                $parameters[$field] = $entity->getId() ? $entity : null;
                $validQuery = true;
            } else if (strpos($field, 'this.') === 0) {
                # format: "this.property"
                # this refers to the current entity, not the relation itself.
                $queryString .= ' AND e.' . $relationProperty . ' = :' . str_replace('.', '_property_', $field);
                $value = $entity->get(str_replace('this.', '', $field));
                if (is_object($value) && property_exists($value, 'id') && !$value->getId()) {
                    $value = null;
                }
                $parameters[str_replace('.', '_property_', $field)] = $value;
                $validQuery = true;
            } else if (strpos($field, 'custom:') === 0) {
                # format: "custom:fieldName"
                $key = explode(':', $field)[1];
                $value = $_POST[$key] ?? null;
                if (isset($this->settings['customFields'][$key]['entity'])) {
                    $value = $value ? $this->getDoctrine()->getRepository($this->settings['customFields'][$key]['entity'])->find($value) : null;
                }
                $queryString .= ' AND e.' . $relationProperty . ' = :' . str_replace(':', '_field_', $field);
                $parameters[str_replace(':', '_field_', $field)] = $value;
                $validQuery = true;
            } else if (strpos($field, 'value:') === 0) {
                # format: "value:hardcoded value"
                $queryString .= ' AND e.' . $relationProperty . ' = :' . str_replace(':', '_value_', $field);
                $parameters[str_replace(':', '_value_', $field)] = substr($field, 6);
                $validQuery = true;
            } else {
                # format: "property"
                $relationPropertyKey = $property . '.' . ($index !== null ? $index . '.' : '') . $field;
                if (isset($this->assignations[$relationPropertyKey]) && array_key_exists($this->assignations[$relationPropertyKey], $row)) {
                    $queryString .= ' AND e.' . $relationProperty . ' = :' . $field;
                    $parameters[$field] = $row[$this->assignations[$relationPropertyKey]];
                    $validQuery = true;
                }
            }
        }

        # In some cases, the query might be invalid if relation fields are empty in the row.
        if ($validQuery) {
            try {
                $query = $this->em->createQuery($queryString);
                $query->setParameters($parameters);
                $query->useQueryCache(true);
                $relation = $query->getOneOrNullResult();
            } catch (NonUniqueResultException $e) {
                $parametersString = "";
                foreach ($parameters as $key => $value) {
                    $parametersString .= $key . ' => "' . ((is_array($value) || is_object($value)) ? json_encode($value) : $value) . "\"\n";
                }

                if ($_SERVER['APP_DEBUG'] ?? false) {
                    throw new \Exception(sprintf(
                        "The '%s' relation's loadingFrom fields specified in the import settings don't always result in a single unique record.\nHere is the faulty query: \n%s\nHere are the parameters:\n%s", $property, $query->getSql(), $parametersString));
                } else {
                    throw new \Exception($this->trans('import.errors.genericServerSide', [], 'application'));
                }
            }
        }

        return $relation;
    }

    /*
     * Verifies that all of the submitted information seems valid and cleans it up.
     * Returns the clean data that's ready for processing.
     */
    protected function getProcessingData() {
        if (empty($_POST)) {
            throw new \Exception($this->trans('import.errors.noData', [], 'application'));
        }

        $data = json_decode($_POST['data'] ?? '[]');
        $assignations = array_filter($_POST['assignation'] ?? []);
        $startingLine = $_POST['starting_line'] ?? 1;

        if (!count($data)) {
            throw new \Exception($this->trans('import.errors.noData', [], 'application'));
        }

        if (!count($assignations)) {
            throw new \Exception($this->trans('import.errors.noAssignations', [], 'application'));
        }

        if ($startingLine > count($data)) {
            throw new \Exception($this->trans('import.errors.startingLineTooHigh', [], 'application'));
        }

        $data = array_slice($data, $startingLine - 1);
        # Remove empty lines from the import data
        $data = array_filter($data, function($row){ return count(array_filter($row)); });

        $this->assignations = array_flip($assignations);

        return $data;
    }

    /*
     * Loads the import settings for the provided import type, and assigns it to the settings property of this controller instance.
     */
    protected function loadImportSettings($importType) {
        $settings = $this->data('import.' . $importType);
        $error = null;

        # Check if this import type is defined in the import.json data file
        if (!$settings) {
            $error = $this->trans(
                'import.errors.settings.undefinedType',
                ['%importType%' => $importType],
                'application'
            );
        }

        # If an entity is defined, check if it exists
        if (isset($settings['entity']) && !class_exists($settings['entity'])) {
            $error = $this->trans(
                'import.errors.settings.undefinedEntity',
                ['%entity%' => $settings['entity']],
                'application'
            );
        }

        # Throw an exception with the error message if there is one
        if ($error) {
            throw new \Exception($error);
        }

        # Shift default columns by one to add the archive column (if the setting is on)
        if ($settings['allowArchive'] ?? false && isset($settings['defaults'])) {
            $newDefaults = ['A' => '_archive_'];

            foreach ($settings['defaults']['columns'] ?? [] as $key => $value) {
                $columnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($key);
                $columnIndex++;
                $newKey = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
                $newDefaults[$newKey] = $value;
            }

            $settings['defaults']['columns'] = $newDefaults;
        }

        $this->settings = $settings;
    }

    /*
     * Checks whether the current user has the required security privileges to access the specified import type.
     * If they do not, throw an error, which is catched by the wrapping method in order to trigger a redirection.
     */
    protected function checkImportPrivilege($importType) {
        if (!$this->getUser()->hasPrivilege('IMPORT_' . strtoupper(StringEdit::camelToSnakeCase($importType)))) {
            $error = $this->trans('import.errors.privilege', [], 'application');
            throw new \Exception($error);
        }
    }

    /*
     * Calls all registered listeners for the specified event with the provided parameters
     * Listeners can accept parameters by reference in order to affect them during or after the processing.
     */
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
