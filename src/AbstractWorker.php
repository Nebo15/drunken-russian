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

    private $task_id;

    /**
     * Do worker job.
     * Passed required data for worker in array
     * If worker has completed their job successfully it must return TRUE.
     * In other cases job should return string with error, that will be stored in field 'error'. Task will be marked as 'failed'
     *
     * @param array $data
     * @return true | string
     */
    abstract public function doThisJob(array $data);

    public function setTaskId($id)
    {
        $this->task_id = $id;
    }

    public function getTaskId()
    {
        return $this->task_id;
    }

    public function setDrunkenManager(Manager $manager)
    {
        $this->manager = $manager;
    }

    public function getDrunkenManager()
    {
        return $this->manager;
    }
}
