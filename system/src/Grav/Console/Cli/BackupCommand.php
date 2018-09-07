<?php
/**
 * @package    Grav.Console
 *
 * @copyright  Copyright (C) 2015 - 2018 Trilby Media, LLC. All rights reserved.
 * @license    MIT License; see LICENSE file for details.
 */

namespace Grav\Console\Cli;

use Grav\Common\Grav;
use Grav\Common\Backup\Backup;
use Grav\Console\ConsoleCommand;
use RocketTheme\Toolbox\File\JsonFile;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;

class BackupCommand extends ConsoleCommand
{
    /** @var string $source */
    protected $source;

    /** @var ProgressBar $progress */
    protected $progress;

    /**
     *
     */
    protected function configure()
    {
        $this
            ->setName("backup")
            ->addArgument(
                'destination',
                InputArgument::OPTIONAL,
                'Where to store the backup (/backup is default)'

            )
            ->setDescription("Creates a backup of the Grav instance")
            ->setHelp('The <info>backup</info> creates a zipped backup. Optionally can be saved in a different destination.');

        $this->source = getcwd();
    }

    /**
     * @return int|null|void
     */
    protected function serve()
    {
        $this->progress = new ProgressBar($this->output);
        $this->progress->setFormat('Archiving <cyan>%current%</cyan> files [<green>%bar%</green>] <white>%percent:3s%%</white> %elapsed:6s% -- <yellow>%message%</yellow>');

        Grav::instance()['config']->init();

        $destination = ($this->input->getArgument('destination')) ? $this->input->getArgument('destination') : null;
        $log = JsonFile::instance(Grav::instance()['locator']->findResource("log://backup.log", true, true));
        $backup = Backup::backup($destination, [$this, 'outputProgress']);

        $log->content([
            'time' => time(),
            'location' => $backup
        ]);
        $log->save();

        $this->output->writeln('');
        $this->output->writeln('');
        $this->output->writeln('<green>Backup Successfully Created:</green> ' . $backup);

    }

    /**
     * @param $args
     */
    public function outputProgress($args)
    {
        switch ($args['type']) {
            case 'count':
                $steps = $args['steps'];
                $freq = intval($steps > 100 ? round($steps / 100) : $steps);
                $this->progress->setMaxSteps($steps);
                $this->progress->setRedrawFrequency($freq);
                $this->progress->setMessage('Adding files...');
                break;
            case 'message':
                $this->progress->setMessage($args['message']);
                $this->progress->display();
                break;
            case 'progress':
                if (isset($args['complete']) && $args['complete']) {
                    $this->progress->finish();
                } else {
                    $this->progress->advance();
                }
                break;
        }
    }

}

