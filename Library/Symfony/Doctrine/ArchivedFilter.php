<?php

namespace Eckinox\Library\Symfony\Doctrine;

use Doctrine\ORM\Mapping\ClassMetaData;
use Doctrine\ORM\Query\Filter\SQLFilter;

class ArchivedFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        $isArchivable = false;
        $isDeletable = false;
        $isStatusDeletable = false;
        $statusPropertyKey = 'status';
        $queryString = '';

        foreach ($targetEntity->reflClass->getProperties() as $property) {
            switch ($property->getName()) {
                case 'isArchived':
                    $isArchivable = true;
                    break;
                case 'isDeleted':
                    $isDeletable = true;
                    break;
                case 'status':
                    $isStatusDeletable = true;

                    if ($property->getDocComment() && strpos($property->getDocComment(), 'name=') !== false) {
                        preg_match('/name="([^"]+)"/', $property->getDocComment(), $matches);
                        $statusPropertyKey = $matches[1] ?? 'status';
                    }
                    break;
            }
        }

        # Don't load archived entities...
        if ($isArchivable) {
            $queryString .= $targetTableAlias . '.is_archived = 0';
        }

        # Or deleted ones...
        if ($isDeletable) {
            $queryString .= ($queryString ? ' AND ' : '') . $targetTableAlias . '.is_deleted = 0';
        }

        # Or deleted ones, using statuses...
        if ($isStatusDeletable) {
            $queryString .= ($queryString ? ' AND ' : '') . $targetTableAlias . '.' . $statusPropertyKey . ' != \'deleted\' or ' . $targetTableAlias . '.' . $statusPropertyKey . ' is NULL';
        }

        return $queryString;
    }
}
