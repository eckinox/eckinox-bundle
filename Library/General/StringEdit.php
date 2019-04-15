<?php

namespace Eckinox\Library\General;

class StringEdit {
    public static function camelToSnakeCase($string) {
        return ltrim(strtolower(preg_replace('/[A-Z]([A-Z](?![a-z]))*/', '_$0', $string)), '_');
    }

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

    public static function wbr($string) {
        $characters = ['/', '_', '-'];

        foreach ($characters as $character) {
            $string = str_replace($character, $character . '<wbr>', $string);
        }

        return $string;
    }

    public static function formatMoney($amount, $decimals = 2) {
        if ($amount === null || $amount === '') {
            return $amount;
        }

        $amount = floatval(preg_replace('/[^0-9.-]/', '', $amount));

        return number_format($amount, $decimals, '.', ',') . ' $';
    }
}
