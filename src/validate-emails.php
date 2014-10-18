<?php

require dirname(__DIR__) . '/vendor/autoload.php';

$config = include dirname(__DIR__) . '/config/gearman.local.php';

$output = new SplFileObject('/home/dmskpd/valid_emails_3.dat', 'a');
$input  = new SplFileObject('/home/dmskpd/Downloads/google_5000000.txt', 'r');

$client = new GearmanClient();
$client->addServers(implode(',', $config['servers']));
$client->setTimeout(3000);

$total = 0;

$client->setCompleteCallback(function (GearmanTask $task) use ($output, &$total) {
    $result = json_decode($task->data(), true);

    if ($result['result']) {
        if ($output->flock(LOCK_EX)) {
            $output->fwrite($result['email'] . PHP_EOL);
            $output->flock(LOCK_UN);
        }
    }

    echo "\rTotal: " . (++$total);
});

$lastEmail = 'pandakiid@gmail.com';


$i = 0;
$needSkip = true;

while (!$input->eof()) {
    $line = trim($input->fgets());

    if ($needSkip && $line != $lastEmail) {
        continue;
    }

    $needSkip = false;

    $client->addTask('validate-email', $line);
    $i++;

    if ($i == 1000) {
        $client->runTasks();
        $i = 0;
    }
}