<?php

namespace Eckinox\Library\General;

use Eckinox\Library\General\Data;

setlocale(LC_COLLATE, 'fr_CA.UTF-8'); // mettre la langue par defaut pour la comparaison de string à fr_CA.UTF8

trait appData {
    use lang;

    public function data($path) {
        /*
         * Get file name
         */
        $keys = explode('.', $path);
        $dataFile = array_shift($keys);

        if( ! Data::get($dataFile) ) {
            foreach([ $this->getParameter('app.data.path_bundle'),
                      $this->getParameter('app.data.path'),
                      $this->getParameter('app.data.path_custom') ] as $filepath) {
                $dataFilePath = $filepath . $dataFile . '.json';

                if( file_exists($dataFilePath) ) {
                    $content = file_get_contents($dataFilePath);

                    if ( $array = json_decode($content, true) ) {
                        Data::set($dataFile, $array);
                    }
                }
            }
        }

        return Data::get($path);
    }

    public function data_to_lang($path, $domain = null, $field = "name") {
        $retval = [];

        if ( !is_array($langdata = $this->data($path)) ) {
            trigger_error("Given lang key '$path' returned no data, maybe there was an error in the path?", E_USER_ERROR);
        }
        else {
            foreach($langdata as $key => $value) {
                $retval[$key] = $this->lang(is_array($value) ? $value[$field] : $value, [], $domain);
            }
        }
        asort($retval, SORT_LOCALE_STRING);
        return $retval;
    }

    public function data_to_lang_push($domain, $data_path, $value = "", $lang_path = null) {
        if ( ! is_array($value) ) {
            $org_value = $value;
            $value = [ static::slug($value, true) => $value ];
        }

        $filepath = $this->getParameter('app.translations.custom') . $domain . '.fr.json';

        $content = json_decode(is_file($filepath) && ( $c = file_get_contents($filepath) ) ? $c : "[]", true);

        Data::array_set($content, $lang_path ?: $data_path, $value);

        file_put_contents($filepath, json_encode($content));

        $this->push_data($domain, "$data_path." . ( $slug = static::slug($org_value ?? "") ), ( $lang_path ?: $data_path ) . "." . key($value));

        return $slug;
    }

    public function push_data($domain, $path, $value) {
        $filepath = $this->getParameter('app.data.path_custom') . $domain . ".json";
        $custom_data = is_file($filepath) ? file_get_contents( $filepath ) : "[]";
        $custom_data = json_decode($custom_data, true);
        Data::array_set($custom_data, $path, $value);

        file_put_contents($filepath, json_encode($custom_data));

        Data::set("$domain.$path", $value);
    }

    public static function slug($str, $accept = '', $camel_case = false) {
        $str = static::normalize($str);
        $str = strtolower($str);
        $str = explode(' ', str_replace(['-', '_'], ' ', $str));

        if ( $camel_case ) {
            $str = lcfirst(implode('', array_map('ucfirst', $str)));
        }
        else {
            $str = implode('_', array_map('strtolower', $str));
        }

        return $str;
    }

    /**
     * Normalize a string by removing accents and maj
     * @param string $str
     * @param string $filter @deprecated
     */
    public static function normalize($str, $filter = null) {
        $str = trim($str);
        $str = $filter ? $filter($str) : $str;

        $search = array('À', 'Á', 'Â', 'Å', 'Ã', 'Ä', 'à', 'á', 'â', 'ã', 'ä', 'å',
            'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø',
            'È', 'É', 'Ê', 'Ë', 'è', 'é', 'ê', 'ë', 'Ç', 'ç',
            'Ì', 'Í', 'Î', 'Ï', 'ì', 'í', 'î', 'ï',
            'Ù', 'Ú', 'Û', 'Ü', 'ù', 'ú', 'û', 'ü',
            'Ÿ', 'ÿ', 'Ñ', 'ñ');
        $replace = array('A', 'A', 'A', 'A', 'A', 'A', 'a', 'a', 'a', 'a', 'a', 'a',
            'O', 'O', 'O', 'O', 'O', 'O', 'o', 'o', 'o', 'o', 'o', 'o',
            'E', 'E', 'E', 'E', 'e', 'e', 'e', 'e', 'C', 'c',
            'I', 'I', 'I', 'I', 'i', 'i', 'i', 'i',
            'U', 'U', 'U', 'U', 'u', 'u', 'u', 'u',
            'Y', 'y', 'N', 'n');

        $str = str_replace($search, $replace, $str);

        return $str;
    }

}
