<?php

namespace Eckinox\Library\General;

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer as SymfonySerializer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

abstract class Serializer {
    /*
     * Serialize an entity to JSON
     */
    public static function serializeEntity($entity, $ignoredAttributes = array(), $nornalizeReferences = true) {
        $encoder = new JsonEncoder();
        $getSetNormalizer = new GetSetMethodNormalizer();
        $dateTimeNormalizer = new DateTimeNormalizer('Y-m-d h:i:s');
        
        $getSetNormalizer->setIgnoredAttributes($ignoredAttributes); 
        
        $getSetNormalizer->setCircularReferenceHandler(function ($object) {
            return $object->getId();
        });
        
        $serializer = new SymfonySerializer(array($dateTimeNormalizer, $getSetNormalizer), array($encoder));
        
        if($nornalizeReferences) {
            $entity = Serializer::normalizeReferences($serializer->normalize($entity));
        }
    
        return $serializer->serialize($entity, 'json');
    }
    
    
    /*
     * Return relations id
     */
    protected static function normalizeReferences($object) {
        foreach($object as $key => $value) {
            if(is_array($value)) {
                if(isset($value['id'])) {
                    $object[$key] = $value['id'];
                } else {
                    $object[$key] = Serializer::normalizeReferences($object[$key]);
                }
            }
        }
        
        return $object;
    }
    
    
}