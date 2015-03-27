#!/usr/bin/env php
<?php

$help = <<< END
usage: drunken --db=<name> --workers-dir=<path> <command>

The most commonly used commands are:
    clear   Delete all completed tasks with 1 month old
    do      Do the work
    help    Print this help
    init    Create mongo collection indexes

END;

date_default_timezone_set('UTC');

function out($message)
{
    printf("drunken: %s\n", $message);
    exit;
}

require_once(__DIR__ . '/../autoload.php');

$options = [];
$command = null;
foreach (array_slice($argv, 1) as $param) {
    if (strpos($param, '--') === 0) {
        list($name, $value) = explode('=', substr($param, 2));
        $options[$name] = $value;
    } else {
        $command = $param;
    }
}

if (! array_key_exists('db', $options)) {
    out('Use some database name option --db=<name>');
}
$drunken = new \Drunken\Drunken((new MongoClient())->selectDB($options['db']));

switch ($command) {
    case 'clear':
        $drunken->clear();
        out('cleared');
        break;
    case 'do':
        if (! array_key_exists('workers-dir', $options)) {
            out('Set some workers directory --workers-dir=<path>');
        }
        $drunken->setWorkersDir($options['workers-dir']);
        $drunken->doAll();
        out('done');
        break;
    case 'init':
        $drunken->ensureIndexes();
        out('initialized');
        break;
    default:
        echo $help;
}
