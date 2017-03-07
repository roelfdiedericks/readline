<?php

require '../vendor/autoload.php';
require 'ExampleCompleter.php';

$readline = new \Ridzhi\Readline\Readline();
$readline->setCompleter(new ExampleCompleter());

do {

    $line = $readline->read('readline > ');

} while ($line !== 'quit');

echo PHP_EOL . "Readline terminated" . PHP_EOL;