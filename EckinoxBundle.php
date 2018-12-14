<?php

namespace Eckinox;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Eckinox\DependencyInjection\EckinoxExtension;

/**
 * Bundle.
 */
class EckinoxBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new EckinoxExtension();
    }
}
