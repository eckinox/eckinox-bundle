<?php

namespace Eckinox\Library\Symfony\Annotation;

/**
 * Annotation class for @Lang().
 *
 * @Annotation
 * @Target({"CLASS"})
 */
class Lang
{
    private $domain = "messages";
    
    private $defaultKey = "";
    
    public function __construct(array $parameters)
    {
        $this->setDomain($parameters['domain'] ?? $this->domain);
        $this->setDefaultKey($parameters['default_key'] ?? $this->domain);
    }
    
    public function getDomain() {
        return $this->domain;
    }
    
    protected function setDomain($set = null) {
         $this->domain = $set;
    }

    public function getDefaultKey() {
        return $this->defaultKey;
    }
    
    protected function setDefaultKey($set = null) {
         $this->defaultKey = $set;
    }
}