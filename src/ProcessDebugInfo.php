<?php

namespace ColinODell\VarPackagistSearch;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessDebugInfo
{
    /**
     * @var ResultCollection
     */
    private $resultCollection;

    /**
     * @var float
     */
    private $start;

    /**
     * @var int
     */
    private $maxPackageCount;

    /**
     * ProcessDebugInfo constructor.
     * @param ResultCollection $resultCollection
     * @param int              $maxPackageCount
     */
    public function __construct(ResultCollection $resultCollection, $maxPackageCount)
    {
        $this->resultCollection = $resultCollection;
        $this->maxPackageCount = $maxPackageCount;
        $this->start = microtime(true);
    }

    public function render(OutputInterface $output)
    {
        $data = [
            ['Progress', sprintf('%s / %s', number_format($this->resultCollection->getCount()), number_format($this->maxPackageCount))],
            ['Memory Usage', $this->getMemoryUsage() . ' MB'],
            ['Average Speed', $this->getAverageSpeedPerPackageInSeconds() . ' seconds per package'],
            ['Est Time Remaining', $this->getEstimatedTimeRemaining($this->maxPackageCount)],
        ];

        $table = new Table($output);
        $table->setHeaders(['Stat', 'Value'])
            ->setRows($data)
            ->render();
    }

    private function getMemoryUsage()
    {
        return number_format(memory_get_usage()/(1024*1024), 2);
    }

    private function getElapsedTime()
    {
        return (microtime(true) - $this->start);
    }

    /**
     * @return float
     */
    private function getAverageSpeedPerPackageInSeconds()
    {
        $packagesProcessed = $this->resultCollection->getCount();

        return round($this->getElapsedTime() / $packagesProcessed);
    }

    /**
     * @param int $maxPackageCount
     *
     * @return string
     */
    private function getEstimatedTimeRemaining($maxPackageCount)
    {
        $packagesRemaining = $maxPackageCount - $this->resultCollection->getCount();
        $timeRemaining = $this->getAverageSpeedPerPackageInSeconds() * $packagesRemaining;
        $hours = floor($timeRemaining / 3600);
        $minutes = floor(($timeRemaining - ($hours*3600))/60);

        return sprintf('%d hours and %d minutes', $hours, $minutes);
    }
}
