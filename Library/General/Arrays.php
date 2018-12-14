<?php

namespace Eckinox\Library\General;

abstract class Arrays {

    /*
     * Equivalent of array_diff
     * Supports multidimensional arrays
     */
    public static function diff($array1, $array2) {

        return Arrays::diff_assoc($array1, $array2);

        /*
         * Old method
         *
         *   return array_map('json_decode', array_diff(array_map('json_encode', $array1), array_map('json_encode', $array2)));
         */
    }


    public static function diff_assoc($array1, $array2)
    {
        $difference = [];

        foreach($array1 as $key => $value) {
            if(is_array($value)) {
                if(!array_key_exists($key, $array2)) {
                    $difference[$key] = $value;
                } else if(!is_array($array2[$key])) {
                    $difference[$key] = $value;
                } else {
                    $new_diff = Arrays::diff_assoc($value, $array2[$key]);

                    if($new_diff){
                        $difference[$key] = $new_diff;
                    }
                }
            } else if(!array_key_exists($key, $array2) || $array2[$key] != $value) {
                $difference[$key] = $value;
            }
        }

        return $difference;
    }


    /*
     * Group an array by a given key
     */
    public static function groupBy($array, $key) {
        $return = array();

        foreach($array as $k => $val) {
            $return[$val[$key]][$k] = $val;
        }

        return $return;
    }

    public static function reorderKeys($array) {
        $array = (array)$array;

        foreach($array as &$value) {
            if(is_array($value)) {
                $value = Arrays::reorderKeys($value);
            }
        }

        ksort($array);

        return $array;
    }
}
