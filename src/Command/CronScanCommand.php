<?php
namespace Aequasi\Bundle\CronBundle\Command;

use aequasi\Bundle\CronBundle\Annotation\CronJob;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class CronScanCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName("cron:scan")
            ->setDescription("Scans for any new or deleted cron jobs");
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $jobs = $this->getCurrentJobs();

        // Enumerate all the jobs currently loaded
        $reader = $this->getContainer()->get("annotation_reader");

        foreach ($this->getApplication()->all() as $command) {
            // Check for an @CronJob annotation
            $class = new \ReflectionClass($command);
            foreach ($reader->getClassAnnotations($class) as $config) {
                if ($config instanceof CronJob) {
                    $job = $command->getName();
                    if (array_key_exists($job, $jobs)) {
                        $data = $jobs[$job];

                        if ($data['interval'] != $config->value) {
                            $newTime          = (new \DateTime())->add(new \DateInterval($config->value));
                            $data['interval'] = $config->value;
                            $data['nextRun']  = $newTime->getTimestamp();

                            $output->writeln("Updated interval for {$job} to {$config->value}");
                        }
                    } else {
                        $data       = [
                            'command'     => $job,
                            'description' => $command->getDescription(),
                            'interval'    => $config->value,
                            'nextRun'     => (new \DateTime())->getTimestamp()
                        ];
                        $jobs[$job] = $data;

                        $output->writeln("Added the job {$job} with interval {$config->value}");
                    }
                }
            }
        }

        file_put_contents($this->getCacheFile(), json_encode($data));

        $output->writeln("Finished scanning for cron jobs");
    }

    /**
     * @return array
     */
    private function getCurrentJobs()
    {
        if (!file_exists($this->getCacheFile())) {
            return [];
        }

        return json_decode(file_get_contents($this->getCacheFile()), true);
    }

    /**
     * @return string
     */
    private function getCacheFile()
    {
        return $this->getContainer()->getParameter('kernel.cache_dir').'cronjob.json';
    }
}
