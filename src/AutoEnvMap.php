<?php

namespace Datto\Composer\ParameterAutoEnv;

use Composer\IO\IOInterface;
use Symfony\Component\Yaml\Parser;

/**
 * Generate parameter to environment variable map for yml file
 */
class AutoEnvMap
{
    /**
     * @var IncenteevFile
     */
    private $file;

    /**
     * @var string[]
     */
    private $envMap = array();

    /**
     * @var string[]
     */
    private $missingParameters = array();

    /**
     * @param IncenteevFile $file
     * @throws IncenteevArgumentException
     */
    public function __construct(IncenteevFile $file)
    {
        $yamlParser = new Parser();

        $expectedValues = $yamlParser->parse(file_get_contents($file->getDistFile()));
        $parameterKey = $file->getParameterKey();
        if (!isset($expectedValues[$parameterKey])) {
            throw new IncenteevArgumentException(sprintf('The top-level key %s is missing.', $parameterKey));
        }
        $expectedParams = (array) $expectedValues[$parameterKey];

        foreach ($expectedParams as $param => $default) {
            $fullParam = ($file->getAutoEnvFullname() ? $parameterKey . '.' : '') . $param;

            $this->consumeParameter($fullParam, $default, $file->getAutoEnvPrefix());
        }
    }

    /**
     * @return string[]
     */
    public function getEnvMap()
    {
        return $this->envMap;
    }

    /**
     * @return string[]
     */
    public function getMissingParameters()
    {
        return $this->missingParameters;
    }

    /**
     * @return bool
     */
    public function hasMissingParameters()
    {
        return count($this->missingParameters) > 0;
    }

    /**
     * @param IOInterface $io
     */
    public function outputParameters(IOInterface $io)
    {
        foreach ($this->envMap as $param => $envName) {
            $io->write(sprintf('<info>Mapped env variable %s to %s</info>', $envName, $param));
        }

        foreach ($this->missingParameters as $param => $envName) {
            $io->write(sprintf('<error>Missing env variable %s for %s</error>', $envName, $param));
        }
    }

    /**
     * @param string $parameterName
     * @param string $envPrefix
     *
     * @return string
     */
    private function paramToEnvName($parameterName, $envPrefix = '')
    {
        return $envPrefix . strtoupper(str_replace('.', '__', $parameterName));
    }

    /**
     * @param string $param
     * @param mixed $default
     * @param string $envPrefix
     */
    private function consumeParameter($param, $default, $envPrefix = '')
    {
        $envName = $this->paramToEnvName($param, $envPrefix);

        if (getenv($envName) !== false) {
            $this->envMap[$param] = $envName;
        } elseif (is_array($default)) {
            foreach ($default as $subParam => $subDefault) {
                $this->consumeParameter($param . '.' . $subParam, $subDefault, $envPrefix);
            }
        } else {
            $this->missingParameters[$param] = $envName;
        }
    }
}
