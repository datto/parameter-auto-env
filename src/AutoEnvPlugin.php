<?php

namespace Datto\Composer\ParameterAutoEnv;

use Composer\Composer;
use Composer\EventDispatcher\Event;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;

/**
 * Composer plugin to apply auto-env changes to composer extras before the Incenteev\ParameterHandler script runs
 */
class AutoEnvPlugin implements PluginInterface, EventSubscriberInterface, Capable
{
    /**
     * @var int Priority in the event chain. Must be higher than the priority of the Incenteev\ParameterHandler script
     */
    const EVENT_PRIORITY = 1;

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return array(
            'post-install-cmd' => array('processIncenteevParameters', self::EVENT_PRIORITY),
            'post-update-cmd' => array('processIncenteevParameters', self::EVENT_PRIORITY),
        );
    }

    /**
     * @inheritDoc
     */
    public function activate(Composer $composer, IOInterface $io)
    {
    }

    /**
     * @inheritDoc
     */
    public function getCapabilities()
    {
        return array(
            'Composer\Plugin\Capability\CommandProvider' => 'Datto\Composer\ParameterAutoEnv\AutoEnvCommandProvider',
        );
    }

    /**
     * Apply auto-env changes
     *
     * @param Event $event post-install or post-update composer event
     * @throws EnvironmentException If auto-environment values are likely to fail
     */
    public function processIncenteevParameters(Event $event)
    {
        $incenteev = new IncenteevParameters($event->getComposer());
        $io = $event->getIO();

        try {
            $files = $incenteev->getFiles();

            foreach ($files as $fileIndex => $file) {
                if ($file->isAutoEnvMap()) {
                    $map = new AutoEnvMap($file);

                    // Abort execution if parameter values are not going to be defined
                    if ($map->hasMissingParameters() && !$io->isInteractive()) {
                        $map->outputParameters($io);
                        throw new EnvironmentException('Missing environment variable for automatic map');
                    }

                    $incenteev->setConfigEnvMap($fileIndex, $map);
                }
            }

            // Apply configuration
            $package = $event->getComposer()->getPackage();
            $extras = $package->getExtra();
            $extras['incenteev-parameters'] = $incenteev->getParameters();
            $package->setExtra($extras);
        } catch (IncenteevArgumentException $e) {
            // Ignore any Incenteev config problems as Incenteev\ParameterHandler will alert on them
        }
    }
}
