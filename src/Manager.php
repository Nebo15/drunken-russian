<?php

namespace Drunken;

class Manager
{
    private $tasks;
    private $workersDir = null;

    public function __construct(\MongoDB $db)
    {
        $this->tasks = $db->selectCollection('drunken_tasks');
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
        while ($doc = $this->getNext()) {
            if (!$this->workersDir) {
                throw new DrunkenException('Workers dir doesn\'t specified');
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
                    $worker->setDrunkenManager($this);
                    try {
                        $result = $worker->doThisJob($doc['data']);
                        # if result is true - the job was completed successfully
                        if ($result === true) {
                            $mongo_data['status'] = 'completed';
                        } else {
                            # some troubles in worker, set error message
                            $mongo_data['error'] = $result;
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
        }
    }

    private function getNext()
    {
        $query = [
            'status' => 'created',
            '$or' => [
                ['expires_at' => ['$exists' => false]],
                ['expires_at' => ['$gt' => new \MongoDate()]]
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
            'data' => $task->data
        ];
        if ($task->expiresAt) {
            $doc['expires_at'] = $task->expiresAt;
        }
        try {
            $result = $this->tasks->insert($doc);
            if (array_key_exists('_id', $doc)) {
                $result['id'] = $doc['_id'];
            }
            return $result;
        } catch (\MongoDuplicateKeyException $e) {
            throw new DrunkenDuplicateTaskException(sprintf('Task duplicate id:%s', $task_id));
        }
    }
}
