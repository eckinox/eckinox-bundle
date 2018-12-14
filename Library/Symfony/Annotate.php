<?php 

namespace Eckinox\Library\Symfony;

use Doctrine\Common\Annotations\AnnotationReader;

class Annotate {
    
    public static function getMethod($name, $obj, $type, $single_result = true) {     
        $list = ( new AnnotationReader() )->getMethodAnnotations( new \ReflectionMethod($obj, $name), $type );
        return static::_filter_annotation($list, $type, $single_result);
    }
    
    public static function getClass($obj, $type, $single_result = true) {
        $list = ( new AnnotationReader() )->getClassAnnotation( new \ReflectionClass($obj), $type );
        return static::_filter_annotation($list, $type, $single_result);
    }
    
    protected static function _filter_annotation($result, $obj, $single_result = true) {
        if (!$result) {
            return false;
        }
        
        if (is_object($result)) {
            return $result;
        }

        $retval = [];
        foreach($result as $item) {
            if ($item instanceof $type) {
                if ( $single_result ) {
                    return $item;
                }
                else {
                    $retval[] = $item;
                }
            }
        }
        
        return $retval;
    }
    
}