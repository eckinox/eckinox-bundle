<?php

namespace Eckinox\Library\General;

class StringEdit {
    public static function camelToSnakeCase($string) {
        return ltrim(strtolower(preg_replace('/[A-Z]([A-Z](?![a-z]))*/', '_$0', $string)), '_');
    }
}
