<?php
/**
 * Author: Paul Bardack paul.bardack@gmail.com http://paulbardack.com
 * Date: 15.04.15
 * Time: 12:35
 */

namespace Drunken;

abstract class AbstractWorker
{
    /** @var Manager $manager */
    private $manager;

    /**
     * Do worker job.
     * Passed required data for worker in array
     * If worker has completed their job successfully it must return TRUE.
     * In other cases job will be marked as 'failed' and field 'error' will contain all returned data from worker
     *
     * @param array $data
     * @return true | mixed
     */
    abstract public function doThisJob(array $data);

    public function setDrunkenManager(Manager $manager)
    {
        $this->manager = $manager;
    }

    public function getDrunkenManager()
    {
        return $this->manager;
    }
}
