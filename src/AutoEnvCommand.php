<?php

namespace Datto\Composer\ParameterAutoEnv;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Composer\Command\BaseCommand;

/**
 * auto-env-check composer command
 */
class AutoEnvCommand extends BaseCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('auto-env-check');
        $this->setDescription('Check all auto-env parameters have a corresponding environment variable set');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $incenteev = new IncenteevParameters($this->getComposer());
        $io = $this->getIO();

        $files = $incenteev->getFiles();

        $returnCode = 0;

        foreach ($files as $file) {
            if ($file->isAutoEnvMap()) {
                $map = new AutoEnvMap($file);

                if ($map->hasMissingParameters()) {
                    $returnCode = 1;
                }

                $map->outputParameters($io);
            } else {
                $io->write(
                    sprintf(
                        '<warning>Skipped file %s with no env-map: auto declaration</warning>',
                        $file->getFile()
                    )
                );
            }
        }

        return $returnCode;
    }
}
