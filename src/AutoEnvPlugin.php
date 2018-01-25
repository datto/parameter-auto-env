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
     * @var string Callback for the standard Incenteev\ParameterHandler script
     */
    const INCENTEEV_SCRIPT = 'Incenteev\\ParameterHandler\\ScriptHandler::buildParameters';

    /**
     * @var string Callback for manual triggering of this auto-env script
     */
    const AUTOENV_SCRIPT = 'Datto\\Composer\\ParameterAutoEnv\\AutoEnvPlugin::buildMap';

    /**
     * @var array Events to subscribe to in Composer, populated by self::activate()
     */
    private static $subscribeEvents = array();

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return self::$subscribeEvents;
    }

    /**
     * Run auto-env mapping as a script
     *
     * @param Event $event Composer event
     * @throws EnvironmentException If auto-environment values are likely to fail
     */
    public static function buildMap(Event $event)
    {
        $plugin = new self();

        return $plugin->processIncenteevParameters($event);
    }

    /**
     * @inheritDoc
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $scripts = $composer->getPackage()->getScripts();

        self::$subscribeEvents = array();

        $possibleEvents = array('post-install-cmd', 'post-update-cmd');
        foreach ($possibleEvents as $event) {
            if ($this->needsAutoEnvEvent($event, $scripts)) {
                self::$subscribeEvents[$event] = array('processIncenteevParameters', self::EVENT_PRIORITY);
            }
        }
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
     * @param Event $event Composer event
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

    /**
     * Check if Incenteev script is called without auto-env during a given Composer event
     *
     * @param string $event
     * @param array $scripts
     */
    private function needsAutoEnvEvent($event, array $scripts)
    {
        if (!isset($scripts[$event])) {
            return false;
        }

        foreach ($scripts[$event] as $script) {
            if ($script === self::AUTOENV_SCRIPT) {
                // If auto-env script is found first we are good
                return false;
            } elseif ($script === self::INCENTEEV_SCRIPT) {
                // If Incenteev script is found first, even if auto-env follows, we need to inject before
                return true;
            } elseif (substr($script, 0, 1) === '@' && $this->needsAutoEnvEvent(substr($script, 1), $scripts)) {
                return true;
            }
        }

        return false;
    }
}
