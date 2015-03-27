# DrunkenRussian
Tasks manager for PHP and MongoDB. 100% alcohol free.

## Example

Adding a task to the queue.

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$drunken = new \Drunken\Drunken(
    (new MongoClient())->selectDB('db_name')
);

$drunken->add('file', [
    'file' => __DIR__ . 'drunken_lines_.txt',
    'message' => 'The test line'
]);
```

Run workers.

```shell
$ vendor/bin/drunken --db=db_name --workers-dir=/path/to/drunken_workers/ do
```
