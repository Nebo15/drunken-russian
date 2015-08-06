<?php

namespace Drunken;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

class Manager
{
    private $tasks;
    private $workersDir = null;
    private $log = null;
    private $logPath = null;
    private $hipchatClient = null;

    public function __construct(\MongoDB $db, $log_path = null)
    {
        $this->tasks = $db->selectCollection('drunken_tasks');
        $this->logPath = $log_path;
    }

    public function setHipchatClient($hc)
    {
        $this->hipchatClient = $hc;
    }

    private function sendHipchatMessage($message)
    {
        if (is_null($this->hipchatClient)) {
            return false;
        }
        $this->hipchatClient->message_room(
            $this->hipchatClient->drunkenRoom,
            $this->hipchatClient->drunkenFrom,
            $message,
            true
        );
    }

    public function setWorkersDir($dir)
    {
        $this->workersDir = rtrim($dir, '/\\');
    }

    public function clear()
    {
        $this->tasks->remove([
            'status' => 'completed',
            'completed_at' => ['$lt' => new \MongoDate((new \DateTime('-1 month'))->getTimestamp())]
        ]);
    }

    public function ensureIndexes()
    {
        $this->tasks->ensureIndex(['priority' => -1, 'created_at' => 1]);
        $this->tasks->ensureIndex(['expires_at' => 1], ['expireAfterSeconds' => 0]);
    }

    public function doAll()
    {
        $this->log("Start run tasks");
        while ($doc = $this->getNext()) {
            if (!$this->workersDir) {
                $msg = 'Workers dir doesn\'t specified';
                $this->log($msg, 'ERROR');
                throw new DrunkenException($msg);
            }
            $class_name = sprintf('%sWorker', ucfirst($doc['type']));
            $class_path = sprintf('%s/%s.php', $this->workersDir, $class_name);

            $mongo_data = ['status' => 'failed'];

            if (!is_file($class_path)) {
                $mongo_data['error'] = "Worker doesn't exists in $class_path";
            } else {
                include_once($class_path);
                $class_name_with_namespace = sprintf('\\Drunken\\%s', $class_name);
                $worker = new $class_name_with_namespace;

                if ($worker instanceof AbstractWorker) {
                    $worker->setTaskId($doc['_id']);
                    $worker->setDrunkenManager($this);
                    try {
                        $this->log(sprintf("Run task %s, worker: %s", $worker->getTaskId(), $class_name));
                        $result = $worker->doThisJob($doc['data']);
                        # if result is true - the job was completed successfully
                        if ($result === true) {
                            $mongo_data['status'] = 'completed';
                        } elseif (is_string($result)) {
                            # some troubles in worker, set error message
                            $mongo_data['error'] = $result;
                        } else {
                            $mongo_data['error'] = 'Returned error from worker should be a string. Returned: '
                                . print_r($result, true);
                        }
                    } catch (\Exception $e) {
                        $mongo_data['error'] = $e->__toString();
                    }
                } else {
                    $mongo_data['error'] = 'Worker must be an instance of AbstractWorker';
                }
            }

            $query = [
                '_id' => $doc['_id'],
                'status' => 'processing'
            ];
            $mongo_data['completed_at'] = new \MongoDate;
            $this->tasks->update($query, ['$set' => $mongo_data]);
            if (isset($mongo_data['error'])) {
                $this->sendHipchatMessage($mongo_data['error']);
                $this->log(
                    sprintf("Error received for task %s, worker %s : %s",
                        $worker->getTaskId(),
                        $class_name,
                        $mongo_data['error']
                    ),
                    'ERROR'
                );
            } else {
                $this->log(
                    sprintf("Task %s of the worker %s successfully completed",
                        $worker->getTaskId(),
                        $class_name
                    )
                );
            }
        }
        $this->log('DONE');
    }

    private function getNext()
    {
        $time = new \MongoDate;
        $query = [
            'status' => 'created',
            '$and' => [
                ['$or' => [
                    ['expires_at' => ['$exists' => false]],
                    ['expires_at' => ['$gt' => $time]]
                ]],
                ['$or' => [
                    ['run_interval' => ['$exists' => false]],
                    ['$and' => [
                        ['run_interval.from' => ['$lt' => $time]],
                        ['run_interval.to' => ['$gte' => $time]],
                    ]]
                ]]
            ]
        ];
        $update = [
            '$set' => [
                'status' => 'processing',
                'started_at' => new \MongoDate()
            ]
        ];
        $doc = $this->tasks->findAndModify($query, $update, null, [
            'sort' => ['priority' => -1, 'created_at' => 1],
            'new' => true
        ]);

        return $doc;
    }

    public function add($type, array $data, $priority = 0, $expiresAt = null)
    {
        return $this->addTask(new Task($type, $data, $priority, $expiresAt));
    }

    public function addTask(Task $task)
    {
        $task_id = $task->getUniqueHash();
        $doc = [
            '_id' => $task_id,
            'type' => $task->type,
            'status' => 'created',
            'created_at' => new \MongoDate(),
            'priority' => $task->priority ? $task->priority : 0,
            'data' => $task->data,
        ];
        if ($task->expiresAt) {
            $doc['expires_at'] = $task->expiresAt;
        }
        if ($task->run_interval) {
            $doc['run_interval'] = $task->run_interval;
        }
        try {
            $return = $this->tasks->insert($doc);
            if (isset($doc['_id'])) {
                $return['id'] = $doc['_id'];
            }

            return $return;

        } catch (\MongoDuplicateKeyException $e) {
            throw new DrunkenDuplicateTaskException(sprintf('Task duplicate id:%s', $task_id));
        }
    }

    public function log($msg, $level = 'INFO')
    {
        if ($this->logPath) {
            if (!$this->log) {
                $logger = new Logger('drunken');
                $stream = new StreamHandler($this->logPath);
                $stream->setFormatter(new LineFormatter());
                $logger->pushHandler($stream);
                $this->log = $logger;
            }
            $this->log->log($level, $msg);
        }
    }
}
