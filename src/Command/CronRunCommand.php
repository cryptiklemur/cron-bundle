<?php

/**
 * This file is part of AequasiCronBundle
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */

namespace Aequasi\Bundle\CronBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class CronRunCommand extends ContainerAwareCommand
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName("cron:run")->setDescription("Runs any currently schedule cron jobs");
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $start    = microtime(true);
        $jobs     = $this->getJobs();
        $jobCount = count($jobs);
        $output->writeln("Running $jobCount jobs:");

        foreach ($this->getJobsToRun($jobs) as $name => $job) {
            $output->write("Running {$name}: ");
            try {
                $commandToRun = $this->getApplication()->get($job['command']);
                $commandToRun->execute(new ArgvInput(), new NullOutput());
            } catch (\InvalidArgumentException $e) {
                $output->writeln(" skipped (command no longer exists)");
                continue;
            } catch (\Exception $e) {
                $output->writeln(" failed with exception ".get_class($e));
                $output->writeln($e->getMessage());
            }

            $this->updateNextRun($name);
        }

        $end      = microtime(true);
        $duration = sprintf("%0.2f", $end - $start);
        $output->writeln("Cron run completed in {$duration} seconds");
    }

    /**
     * @param array $jobs
     *
     * @return array
     */
    private function getJobsToRun(array $jobs)
    {
        $toRun = [];
        foreach ($jobs as $name => $job) {
            if ($job['nextRun'] < time()) {
                $toRun[$name] = $job;
            }
        }

        return $toRun;
    }

    /**
     * @param bool $tryAgain
     *
     * @return string[]
     */
    private function getJobs($tryAgain = true)
    {
        if (!file_exists($this->getCacheFile())) {
            $commandToRun = $this->getApplication()->get('cron:scan');
            $commandToRun->execute(new ArgvInput(), new NullOutput());

            return $tryAgain ? $this->getJobs(false) : [];
        }

        return json_decode(file_get_contents($this->getCacheFile()), true);
    }

    /**
     * @param string $jobName
     */
    private function updateNextRun($jobName)
    {
        $jobs = $this->getJobs();
        foreach ($jobs as $name => $job) {
            if ($jobName === $name) {
                $jobs[$name]['nextRun'] = (new \DateTime())->add(new \DateInterval($job['interval']));
            }
        }

        file_put_contents($this->getCacheFile(), json_encode($jobs));
    }

    /**
     * @return string
     */
    private function getCacheFile()
    {
        return $this->getContainer()->getParameter('kernel.cache_dir').'cronjob.json';
    }
}
