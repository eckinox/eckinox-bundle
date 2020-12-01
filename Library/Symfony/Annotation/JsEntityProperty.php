<?php

namespace Eckinox\Library\Symfony\Annotation;

/**
 * Annotation class for @JsEntityProperty().
 *
 * @Annotation
 * @Target({"PROPERTY"})
 */
class JsEntityProperty
{
    /**
     * @var string
     */
    private $name;

    public function __construct(array $parameters)
    {
        $this->name = $parameters["name"] ?? null;
    }

    public function getName() {
        return $this->name;
    }
}
