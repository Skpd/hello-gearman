<?php

use Jyggen\Curl\Dispatcher;

require dirname(__DIR__) . '/vendor/autoload.php';

$proxies = [];

//120.203.214.182:81 - ok
//196.201.217.49:4015 - ok
//61.155.169.11:808 - ok
##### b458969@trbvm.com

$dispatcher = new Dispatcher();
$i = 0;
$list = file('/tmp/proxy.txt') ;
foreach ($list as $proxy) {
    $request = new \Jyggen\Curl\Request('http://wtfismyip.com/text');
//    $request->setOption(CURLOPT_TIMEOUT, 3);
    $request->setOption(CURLOPT_POST, true);
    $request->setOption(CURLOPT_POSTFIELDS, "action=login");
    $request->setOption(CURLOPT_PROXY, trim($proxy));

    $dispatcher->add($request);

//    var_dump($i, count($list));
    if ($i % 100 == 0 || $i == count($list)) {
        echo date(DATE_ATOM) . ": Executing " . (($i - 1) % 100) . " requests\n";
        $dispatcher->execute(function (\Jyggen\Curl\Response $response) use (&$proxies, $proxy) {
            if (trim($response->getContent()) == 'No such fucking page!') {
                echo "$proxy - good\n";
                $proxies[] = $proxy;
            } else {
                echo "$proxy - bad\n";
            }
        });
        $dispatcher->clear();
        echo date(DATE_ATOM) . ": Done.\n";
    }

    $i++;
}

var_dump($proxies);
foreach ($proxies as $proxy => $time) {
    echo "'$proxy' => 0,\n";
}
