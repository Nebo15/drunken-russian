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
    private $ignore_fields_for_unique_hash = [];

    public function __construct(
        $type,
        array $data,
        $priority = 0,
        $expiresAt = null,
        $from = null,
        $to = null,
        $ignoreFieldsForUniqueHash = null
    ) {
        $this->type = $type;
        $this->data = $data;
        $this->priority = $priority;
        $this->expiresAt = $expiresAt;
        if ($from and $to) {
            $this->setRunInterval($from, $to);
        }
        if ($ignoreFieldsForUniqueHash) {
            $this->setIgnoreFieldsForUniqueHash($ignoreFieldsForUniqueHash);
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

    /**
     * @param string|array $fields
     * @return $this
     */
    public function setIgnoreFieldsForUniqueHash($fields)
    {
        if (!is_array($fields)) {
            $fields = [$fields];
        }
        $this->ignore_fields_for_unique_hash = $fields;

        return $this;
    }

    public function getUniqueHash()
    {
        $data = $this->data;
        if ($this->ignore_fields_for_unique_hash) {
            foreach ($this->ignore_fields_for_unique_hash as $field) {
                if (array_key_exists($field, $data)) {
                    unset($data[$field]);
                }
            }
        }
        ksort($data);

        return sha1(sprintf('%s%s', $this->type, serialize($data)));
    }
}
