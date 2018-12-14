<?php

namespace Eckinox\Library\Symfony\Annotation;

/**
 * @Annotation
 */
class Security
{
    /**
     * @var string
     */
    private $privilege;
    
    /**
     * @var string
     */
    private $redirect;
    
    /**
     * @var string
     */
    private $method;
    
    public function __construct(array $parameters)
    {
        if (isset($parameters['value'])) {
            $parameters['privilege'] = $parameters['value'];
            unset($parameters['value']);
        }
        
        foreach ($parameters as $key => $value) {
            if (!method_exists($this, $name = 'set'.$key)) {
                throw new \RuntimeException(sprintf('Unknown key "%s" for annotation "@%s".', $key, get_class($this)));
            }
            $this->$name($value);
        }
    }
    
    public function getPrivilege() {
        return $this->privilege;
    }
    
    public function setPrivilege($privilege) {
        $this->privilege = $privilege;
    }
    
    public function getMethod() {
        return $this->method;
    }
    
    public function setMethod($mtehod) {
        $this->method = $method;
    }
    
    public function getRedirect(){
		return $this->redirect;
	}

	public function setRedirect($redirect){
		$this->redirect = $redirect;
	}
}