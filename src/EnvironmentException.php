<?php

namespace Datto\Composer\ParameterAutoEnv;

use RuntimeException;

/**
 * Representation of invalid/missing environment variables for the task being run
 */
class EnvironmentException extends RuntimeException
{
}
