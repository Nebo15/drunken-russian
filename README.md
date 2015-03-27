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

Run workers.

```shell
$ vendor/bin/drunken --db=db_name --workers-dir=/path/to/drunken_workers/ do
```
