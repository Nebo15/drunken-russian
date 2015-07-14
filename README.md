# DrunkenRussian

Tasks manager for PHP and MongoDB. 100% alcohol free.

## Before you start

Create some useful indexes

```shell
$ vendor/bin/drunken --db=db_name init
```

Add a clearing task to cron

```shell
$ vendor/bin/drunken --db=db_name clear
```

## Config

If you don't want use console options create config file *drunken.config.php* in the same directory, where script is called. Example of the config you can find in src directory.

Also you can run drunken with option **--config="path_to_config"**

## Example

Adding a task to the queue.

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$drunken = new \Drunken\Manager(
    (new MongoClient())->selectDB('db_name')
);

$drunken->add('file', [
    'file' => __DIR__ . 'drunken_lines_.txt',
    'message' => 'The test line'
]);
```

A worker. If the worker returns false or nothing, task will be marked as 'errored'.

```php
<?php

namespace Drunken;

class FileWorker extends AbstractWorker
{
    public function doThisJob(array $data)
    {
        $result = file_put_contents($data['file'], sprintf("%s\n", $data['message']), FILE_APPEND);
        
        if ($result !== false) {
            return true;
        }
        
        return false;
    }
}
```

Run workers.

```shell
$ vendor/bin/drunken --db=db_name --workers-dir=/path/to/drunken_workers/ do
```
