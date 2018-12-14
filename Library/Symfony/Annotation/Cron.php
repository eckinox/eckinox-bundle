<?php

namespace Eckinox\Library\Symfony\Annotation;

use App\Library\Symfony\AnnotationHelper;


/**
 * Annotation class for @Cron().
 *
 * @Annotation
 * @Target({"METHOD", "CLASS"})
 */
class Cron
{
    private $tab;

    /**
     * @param array $data An array of key/value parameters
     *
     * @throws \BadMethodCallException
     */
    public function __construct(array $data)
    {
        if (isset($data['value'])) {
            $data['tab'] = $data['value'];
            unset($data['value']);
        }

        foreach ($data as $key => $value) {
            $method = 'set'.str_replace('_', '', $key);
            if (!method_exists($this, $method)) {
                throw new \BadMethodCallException(sprintf('Unknown property "%s" on annotation "%s".', $key, get_class($this)));
            }
            $this->$method($value);
        }
    }

    /*
     * Getters and setters
     */

     public function setTab($tab)
    {
        $this->tab = $tab;
    }

    public function getTab()
    {
        return $this->tab;
    }
}
