<?php

require dirname(__DIR__) . '/vendor/autoload.php';

$config = include dirname(__DIR__) . '/config/gearman.local.php';

$client = new GearmanClient();
$client->addServers(implode(',', $config['servers']));

$pdo = new PDO('mysql:host=do.dev0.in;dbname=gearman', 'root', 'root123!', [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8']);

$players = $pdo->query(
//    "SELECT id FROM roswar_players WHERE level = 2 and alignment = 'resident' and money is null",
//    "select id from roswar_players where level < 7 and level > 4 and coolness < 100 and money is null order by level desc, coolness asc",
//    "select id from roswar_players where updated != '0000-00-00 00:00:00' and level > 4",
    "select id from roswar_players where level = 5 and money is not null order by money desc",
    PDO::FETCH_COLUMN,
    0
);

$total = $players->rowCount();

$proxies = [
// ams-3
//    '178.62.193.49:8888' => 0,
//    '178.62.193.48:8888' => 0,
//    '178.62.193.80:8888' => 0,
//    '178.62.193.50:8888' => 0,
//    '178.62.243.42:8888' => 0,
//    '178.62.207.48:8888' => 0,
//    '178.62.254.85:8888' => 0,
//    '178.62.243.99:8888' => 0,
//    '178.62.244.164:8888' => 0,
//    '178.62.242.249:8888' => 0,
//    '178.62.248.11:8888' => 0,
//    '178.62.242.229:8888' => 0,
//    '178.62.243.243:8888' => 0,
//    '178.62.242.152:8888' => 0,
//    '178.62.254.9:8888' => 0,
//    '178.62.250.71:8888' => 0,
//    '178.62.252.145:8888' => 0,
//    '178.62.242.224:8888' => 0,
//    '178.62.242.150:8888' => 0,

// ams-2
//    '188.226.164.61:8888' => 0,
//    '80.240.138.33:8888' => 0,
//    '188.226.176.172:8888' => 0,
//    '188.226.134.185:8888' => 0,
//    '188.226.171.94:8888' => 0,
//    '95.85.1.157:8888' => 0,
//    '188.226.169.150:8888' => 0,
//#    '178.62.180.71:8888' => 0,
//    '80.240.128.237:8888' => 0,
//    '80.240.128.244:8888' => 0,
//    '95.85.21.199:8888' => 0,
//    '188.226.250.213:8888' => 0,
//    '188.226.248.83:8888' => 0,
//    '188.226.178.213:8888' => 0,
//    '95.85.52.137:8888' => 0,
//    '188.226.241.41:8888' => 0,
//    '188.226.213.183:8888' => 0,

// ams-1
//    '198.211.126.168' => 0,
    
// lon-1
//    '178.62.121.140' => 0,


//    '188.226.164.61:8888' => 0,
//    '80.240.128.244:8888' => 0,
//    '188.226.165.187:8888' => 0,
//    '188.226.134.185:8888' => 0,
//    '80.240.138.33:8888' => 0,
//    '95.85.1.157:8888' => 0,
//    '188.226.176.172:8888' => 0,
//    '188.226.171.94:8888' => 0,
//    '95.85.21.199:8888' => 0,
//    '80.240.128.237:8888' => 0,
//    '188.226.169.150:8888' => 0,
//    '188.226.241.41:8888' => 0,
//    '188.226.178.213:8888' => 0,
//    '188.226.250.213:8888' => 0,
//    '95.85.52.137:8888' => 0,
//    '188.226.248.83:8888' => 0,
//    '188.226.213.183:8888' => 0,
];

foreach (file('/tmp/proxy.txt') as $proxy) {
    $proxies[trim($proxy)] = 0;
}

$client->setCompleteCallback(function (GearmanTask $task) use (&$total, &$proxies, &$client) {
    $result = json_decode($task->data(), true);
    echo "{$result['id']} - {$result['status']}\n";
    --$total;

    if ($result['status'] === 'proxy-busy') {
        echo "Proxy {$result['proxy']} busy.\n";
        $proxies[$result['proxy']] = time();
        $client->addTask('roswar.check-wallet', json_encode(['id' => $result['id'], 'proxy' => getProxy($proxies)]));
    } else if ($result['status'] === 'proxy-busy') {
        $client->addTask('roswar.check-wallet', json_encode(['id' => $result['id'], 'proxy' => getProxy($proxies)]));
    }
});

$i = 0;

echo "Queuing " . $players->rowCount() . " tasks...\n";
foreach ($players as $id) {
    $client->addTask('roswar.check-wallet', json_encode(['id' => $id, 'proxy' => getProxy($proxies)]));
    $i++;

    if ($i % count($proxies) == 0) {
        $client->runTasks();
    }
}
//echo ". Done.\n";

$client->runTasks();
echo "\nDone.\n";

function getProxy(array $proxies)
{
    while (true) {
        $shuffledProxies = [];
        $keys = array_keys($proxies);

        shuffle($keys);

        foreach($keys as $key) {
            $shuffledProxies[$key] = $proxies[$key];
        }

        foreach ($shuffledProxies as $name => $time) {
            if (time() - $time > 70) {
                return $name;
            }
        }

        echo "\rAll proxies are busy. Waiting...\n";

        sleep(1);
    }

    return false;
}