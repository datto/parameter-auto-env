<?php

namespace Datto\Composer\ParameterAutoEnv;

/**
 * Individual file configuration options inside extra.incenteev-parameters
 */
class IncenteevFile
{
    /**
     * @var string
     */
    const ENV_MAP_AUTO = 'auto';

    /**
     * @var array
     */
    private $config;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @return string
     * @throws IncenteevArgumentException
     */
    public function getFile()
    {
        if (empty($this->config['file'])) {
            throw new IncenteevArgumentException(
                'The extra.incenteev-parameters.file setting is required to use this script handler.'
            );
        }

        return $this->config['file'];
    }

    /**
     * @return string
     * @throws IncenteevArgumentException
     */
    public function getDistFile()
    {
        if (empty($config['dist-file'])) {
            return $this->getFile() . '.dist';
        }

        return $this->config['dist-file'];
    }

    /**
     * @return string
     */
    public function getParameterKey()
    {
        return empty($this->config['parameter-key']) ? 'parameters' : $this->config['parameter-key'];
    }

    /**
     * @return array|string
     */
    public function getEnvMap()
    {
        return empty($this->config['env-map']) ? array() : $this->config['env-map'];
    }

    /**
     * @return bool
     */
    public function isAutoEnvMap()
    {
        return $this->getEnvMap() === self::ENV_MAP_AUTO;
    }

    /**
     * @return string
     */
    public function getAutoEnvPrefix()
    {
        return empty($this->config['auto-env-prefix']) ? '' : $this->config['auto-env-prefix'];
    }

    /**
     * @return bool
     */
    public function getAutoEnvFullname()
    {
        return empty($this->config['auto-env-fullname']) ? true : (bool) $this->config['auto-env-fullname'];
    }
}
