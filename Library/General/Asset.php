<?php

namespace Eckinox\Library\General;

use Symfony\Component\Asset\VersionStrategy\VersionStrategyInterface;

class Asset implements VersionStrategyInterface
{   
    /**
     *@var string
     */
    private $version;
    
    private $format;
    
    public function __construct($debug, $format) {
        $this->version = $debug ? uniqid("debug") : Git::getCommit();
        $this->format = $format;
    }

    public function getVersion($path = false) {
        return $this->version;
    }

    public function applyVersion($path) {
        if ($path === false) {
            return sprintf($this->format, "", $this->getVersion());
        }
        
        $is_file = strpos(basename($path), '.') !== false;
        
        return  $is_file ? sprintf($this->format, $path, $this->getVersion()) : $path;
    }
}