<?php

namespace Datto\Composer\ParameterAutoEnv;

use Composer\Composer;

/**
 * Container for the extra.incenteev-parameters settings
 */
class IncenteevParameters
{
    /**
     * @var array
     */
    private $parameters;

    /**
     * @param Composer $composer
     * @throws IncenteevArgumentException
     */
    public function __construct(Composer $composer)
    {
        $extras = $composer->getPackage()->getExtra();

        if (!isset($extras['incenteev-parameters'])) {
            throw new IncenteevArgumentException(
                'The parameter handler needs to be configured through the extra.incenteev-parameters setting.'
            );
        }

        $parameters = $extras['incenteev-parameters'];

        if (!is_array($parameters)) {
            throw new IncenteevArgumentException(
                'The extra.incenteev-parameters setting must be an array or a configuration object.'
            );
        }

        // Normalize to a 0-indexed array of configurations
        if (array_keys($parameters) !== range(0, count($parameters) - 1)) {
            $parameters = array($parameters);
        }

        foreach ($parameters as $parameter) {
            if (!is_array($parameter)) {
                throw new IncenteevArgumentException(
                    'The extra.incenteev-parameters setting must be an array of configuration objects.'
                );
            }
        }

        $this->parameters = $parameters;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param int $parameterIndex
     * @param AutoEnvMap $map
     */
    public function setConfigEnvMap($parameterIndex, AutoEnvMap $map)
    {
        $this->parameters[$parameterIndex]['env-map'] = $map->getEnvMap();
    }

    /**
     * @return IncenteevFile[]
     */
    public function getFiles()
    {
        return array_map(
            function ($parameter) {
                return new IncenteevFile($parameter);
            },
            $this->parameters
        );
    }
}
