<?php

namespace Eckinox\Library\General;

use Doctrine\Common\Annotations\AnnotationReader;

use Eckinox\Library\Symfony\Annotation\Lang as LangAnnotation,
    Eckinox\Library\Symfony\Annotate;

trait lang {
    public function lang($path, $params = [], $domain = null, $prependDefaultKey = false) {
        return $this->translator->trans(
            ($prependDefaultKey ? $this->lang_get_default_key()."." : ""). $path,
            $params,
            $this->lang_get_domain($domain)
        );
    }

    public function _($path) {
        return $this->lang($path, [], null, true);
    }

    public function lang_array($path, $domain = null) {
        $retval = [];
        $len = strlen($path);

        foreach($this->translator->getCatalogue()->all($this->lang_get_domain($domain)) as $key => $value) {
            if ( substr( $key, 0, $len ) === $path ) {
                $retval[substr( $key, $len + 1 )] = $value;
            }
        }

        return $retval;
    }

    public function lang_get_default_key() {
        static $default_key = null;
        return $default_key ?: ( $default_key = ( Annotate::getClass($this, LangAnnotation::class) )->getDefaultKey() );
    }

    public function lang_get_domain($domain = null) {
        return $domain ?: ( Annotate::getClass(static::class, LangAnnotation::class)->getDomain() ?: 'messages' );
    }

}
