<?php

namespace Drunken;

class FileWorker extends AbstractWorker
{
    public function doThisJob(array $data)
    {
        $result = file_put_contents($data['file'], sprintf("%s\n", $data['message']), FILE_APPEND);
        return $result !== false ? true : false;
    }
}
