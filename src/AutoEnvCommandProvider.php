<?php

namespace Datto\Composer\ParameterAutoEnv;

use Composer\Plugin\Capability\CommandProvider;

/**
 * Define available composer plugin commands
 */
class AutoEnvCommandProvider implements CommandProvider
{
    /**
     * @inheritDoc
     */
    public function getCommands()
    {
        return array(new AutoEnvCommand());
    }
}
