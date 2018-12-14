<?php

namespace Eckinox\Library\General;

abstract class Data {
    
    private static $data = [];
    
    public static function get($path = null) { return $path == null ? static::$data : static::array_get(static::$data, $path); }
    public static function set($path, $value) { return static::array_set(static::$data, $path, $value); }
    
    /**
     * Set value to array with a given path using recursion
     * @param array			$array - array to set passed by reference !
     * @param string		$path - path like 'person.name.first'
     * @param string		$value - value to set
     */
    public static function array_set(& $array, $path, $value = '', $delimiter = '.') {
        $path_arr = explode($delimiter, $path);

        // Go to next node
        if ( isset($path_arr[1])) {
            $arr = array_shift($path_arr);
            static::array_set($array[$arr], implode($delimiter, $path_arr), $value);
        }
        // We are at the end of the path, set value
        else {
            if ( isset($array[ $path_arr[0] ]) && is_array($array[ $path_arr[0] ]) ) {
                $array[ $path_arr[0] ] = array_replace_recursive($array[ $path_arr[0] ], $value);
            }
            else {
                $array[ $path_arr[0] ] = $value;
            }
        }
    }

    public static function array_get($array, $path, $delimiter = '.') {
        $path_arr = explode($delimiter, $path);
        
        if (isset($array[$path_arr[0]])) {
            if (isset($path_arr[1])) {
                return static::array_get($array[array_shift($path_arr)], implode($delimiter, $path_arr));
            } else {
                return $array[$path_arr[0]];
            }
        }
        else {
            return null;
        }
    }
}