<?php

namespace Eckinox\Library\Symfony;

trait repository {
    private function search(&$query, $search, $entity = 'e') {
        $num = 0;

        foreach ($search as $field) {

            $terms = (array)$field['terms'];
            $field = isset($field['search']) ? array_merge($field, $field['search']) : $field;
            $clause = $field['clause'] ?? 'andWhere';
            $expr = $field['expr'] ?? 'andX';
            $type = $field['type'] ?? 'text';
            $exact = $field['exact'] ?? false;
            $not = $field['not'] ?? false;
            $column = $entity.'.'.$field['name'];
            $num++;

            if (isset($field['entity'])) {
                # Split the entity field on the dots:
                # The last item is the targetted property
                # Every other item is a relation property from the previous one
                $entityParts = explode('.', $field['entity']);

                # Loop through the relations to join the required tables
                $previousRelationAs = $entity;
                while (count($entityParts) >= 2) {
                    $relationName = array_shift($entityParts);
                    $relationAs = $relationName[0].$num;

                    $query->join($previousRelationAs . '.' . $relationName, $relationAs);

                    $previousRelationAs = $relationAs;
                    $num++;
                }
                # Once the loop is over, the only "part" remaining is the targetted property
                # The previousRelationAs contains the alias of the targetted property's parent entity, which has been joined above
                $joined_column = $previousRelationAs . '.' . array_shift($entityParts);

                # Below is the classic filtering (where clause, generally) based on the targetted property's value
                if($type === 'json') {
                    $query->$clause($this->setJsonTerms($query, $entity, $field, $joined_column, $terms, $expr));

                } else {
                    $query->$clause($this->setTerms($query, $joined_column, $terms, $expr, $exact, $not));
                }

            } else {
                $query->$clause($this->setTerms($query, $column, $terms, $expr, $exact, $not));
            }
        }

        return $query;
    }

    private function filter($query, $parameters, $entity = 'e') {
        foreach ($parameters as $key => $value) {
            if ( is_array($value) ) {
                $uid = uniqid("param_");
                $query->andWhere("$entity.$value[0] $value[1] :$uid")
                  ->setParameter($uid, $value[2]);
            }
            else {
                $query->andWhere("$entity.$key = :$key")
                  ->setParameter($key, $value);
            }
        }

        return $query;
    }

    # https://stackoverflow.com/a/23006164
    public function fillFromArray($data) {
        $class = $this->getClassName();
        $object = new $class();
        $meta = $this->getClassMetadata();

        foreach ($data as $property => $value) {
            if( $value && $meta->hasAssociation($property) ) {
                $value = $this->_em->getRepository($meta->getAssociationMapping($property)['targetEntity'])->find($value);

                if ( empty($v) ){
                    throw new \Exception('An unknown/unsettable key was defined into '.json_encode($data));
                }
            }

            $method = "set".str_replace('_', '', $property);
            $object->$method($value);
        }

        return $object;
    }

    public function updateFromArray($data, $unique_key) {
        if ( strpos($unique_key, '_') !== false ) {
            $keys = explode('_', $unique_key);
            $orm_key = array_shift($keys) . implode('', array_map('ucfirst', $keys));
        }
        else {
            $orm_key = $unique_key;
        }

        $query = $this->createQueryBuilder('o')->select("o.$orm_key");
        $skip_list = [];
        if (array_column($data, $unique_key)) {
            $skip = $query->where( $query->expr()->in("o.$orm_key", array_column($data, $unique_key)) )->getQuery()->getResult();
            $skip_list = array_column($skip, $orm_key);
        }

        $savable = [];

        foreach($data as $item) {
            if ( ! in_array($item[$unique_key], $skip_list) ) {
                $savable[] = $this->fillFromArray( $item );
            }
        }

        return $savable;
    }

    private function setTerms(&$query, $column, $terms, $expr = 'andX', $exact = false, $not = false) {
        $expr = $query->expr()->$expr();

        foreach($terms as $term) {
            $param = ':param'.uniqid();

            if ($term !== null) {
                if ($not) {
                    $expr->add($query->expr()->notLike($column, $param));
                } else {
                    $expr->add($query->expr()->like($column, $param));
                }
                $query->setParameter($param, ($exact ? $term : '%'.$term.'%'));
            } else {
                if ($not) {
                    $expr->add($query->expr()->isNotNull($column));
                } else {
                    $expr->add($query->expr()->isNull($column));
                }
            }
        }

        return $expr;
    }

    private function setJsonTerms(&$query, $entity, $field, $column, $terms, $expr = 'andX') {
        $relation = $entity.'.'.$field['relation'];
        $expr = $query->expr()->$expr();

        foreach($terms as $term) {
            $param = ':param'.uniqid();

            $expr->add($query->expr()->like('LOWER(JSON_EXTRACT('.$column.', concat(\'$."\', '.$relation.', \'".'.$field['field'].'\')))', $param));
            $query->setParameter($param, '%'.$term.'%');
        }

        return $expr;
    }
}
