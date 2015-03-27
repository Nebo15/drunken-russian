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
            if (! $this->workersDir) {
                throw new DrunkenException('Workers dir doesn\'t specified');
            }
            $class_name = sprintf('%sWorker', ucfirst($doc['type']));
            include_once(sprintf('%s/%s.php', $this->workersDir, $class_name));
            $class_name_with_namespace = sprintf('\\Drunken\\%s', $class_name);
            $worker = new $class_name_with_namespace;
            $done = $worker->doThisJob($doc['data']);
        
            $status = $done ? 'completed' : 'errored';
            $query = [
                '_id' => $doc['_id'],
                'status' => 'processing'
            ];
            $this->tasks->update($query, [
                '$set' => [
                    'status' => $status,
                    'completed_at' => new \MongoDate()
                ]
            ]);
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
        $update = ['$set' => [
            'status' => 'processing',
            'started_at' => new \MongoDate()
        ]];
        $doc = $this->tasks->findAndModify($query, $update, null, [
            'sort' => ['priority' => -1, 'created_at' => 1],
            'new' => true
        ]);
        return $doc;
    }
    
    public function add($type, array $data, $priority = 0, $expiresAt = null)
    {
        $task = new \Drunken\Task($type, $data, $priority, $expiresAt);
        $this->addTask($task);
    }
    
    public function addTask($task)
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
            $this->tasks->insert($doc);
        } catch (\MongoDuplicateKeyException $e) {
            throw new DrunkenException(sprintf('Task duplicate id:%s', $task_id));
        }
    }
}
