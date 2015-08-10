<?php

namespace Drunken;

class Task
{
    public $type;
    public $status;
    public $createdAt;
    public $expiresAt = null;
    public $priority = 0;
    public $run_interval = [];
    public $data;

    public function __construct($type, array $data, $priority = 0, $expiresAt = null, $from = null, $to = null)
    {
        $this->type = $type;
        $this->data = $data;
        $this->priority = $priority;
        $this->expiresAt = $expiresAt;
        if ($from and $to) {
            $this->setRunInterval($from, $to);
        }
    }

    public function setRunInterval($from, $to)
    {
        $from = is_string($from) ? strtotime($from) : $from;
        $to = is_string($to) ? strtotime($to) : $to;
        if ($from and $to) {
            $this->run_interval = [
                'from' => new \MongoDate($from),
                'to' => new \MongoDate($to),
            ];
        }

        return $this;
    }

    public function getUniqueHash()
    {
        ksort($this->data);
        $hash = sha1(sprintf('%s%s', $this->type, serialize($this->data)));

        return $hash;
    }
}
