<?php

require dirname(__DIR__) . '/vendor/autoload.php';

$pdo = new PDO('mysql:host=do.dev0.in;dbname=gearman', 'root', 'root123!', [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//$players = $pdo->query('SELECT id, nickname, money FROM roswar_players WHERE money is not null');
//echo "To fix: {$players->rowCount()}\n";
//
//foreach ($players as $n => $row) {
//    $pdo->exec("UPDATE roswar_players SET money = NULL WHERE id = {$row['id']}");
//    $pdo->exec(
//        "UPDATE roswar_players SET money = {$row['money']} WHERE id = (
//            SELECT MAX(id) FROM roswar_players WHERE nickname LIKE ' {$row['nickname']}' OR nickname LIKE '{$row['nickname']}'
//        )"
//    );
//    echo "Fixed $n\n";
//}
//
//exit;

//$players = $pdo->query('SELECT nickname FROM roswar_players WHERE money > 10000 AND level > 2 AND coolness < 200 and alignment = "resident" limit 10', PDO::FETCH_COLUMN, 0);
$players = $pdo->query(
//    'SELECT id, nickname FROM roswar_players
//    WHERE money > 1000 AND level = 5 AND alignment = "resident" AND id NOT IN (
//        SELECT duel_group.attacker FROM (
//            SELECT attacker, DATE(date) gdate, count(id) as daily_attacks, sum(money), max(date) FROM `roswar_duels`
//            WHERE victim = 1429142
//            GROUP BY gdate, attacker
//            HAVING daily_attacks = 5 AND gdate = DATE(NOW())
//        ) as duel_group
//    )
//    ORDER BY money DESC
//    LIMIT 0, 55',
    'SELECT id, nickname FROM roswar_players WHERE money is not null',
    PDO::FETCH_KEY_PAIR
);

$updateStmt = $pdo->prepare('UPDATE roswar_players SET money = ? WHERE id = ?');

foreach ($players as $value) {
    $id         = key($value);
    $nickname   = reset($value);
    $cookieFile = "/tmp/roswar_cookies/$id.cook";
    echo "$nickname\n";
    $ch = curl_init('http://www.roswar.ru/login/');
    curl_setopt_array($ch, [
        CURLOPT_AUTOREFERER    => true,
        CURLOPT_HTTPHEADER     => ["Proxy-Connection:"],
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_USERAGENT      => "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1",
        CURLOPT_COOKIESESSION  => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR      => $cookieFile,
        CURLOPT_COOKIEFILE     => $cookieFile,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_PROXY          => '188.226.213.183:8888',
//        CURLOPT_PROXY          => '120.203.214.182:81',
//        CURLOPT_PROXY          => '61.155.169.11:808',
//        CURLOPT_PROXY          => '78.107.234.60:3128',
//        CURLOPT_PROXY          => '37.139.16.175:8888',
    ]);

    if (!file_exists($cookieFile)) {
        echo "Dive in...\n";
        curl_setopt_array($ch, [
            CURLOPT_URL        => 'http://www.roswar.ru/login/',
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => http_build_query(['action' => 'login', 'email' => $nickname]),
        ]);
    } else {
        curl_setopt_array($ch, [
            CURLOPT_URL        => 'http://www.roswar.ru/player/',
            CURLOPT_POST       => false,
        ]);
    }

    $content = curl_exec($ch);

    if (preg_match('/stats-accordion/i', $content)) {

        preg_match('/Монет:\s(\d+)/i', $content, $money);

        preg_match('#"id":"(\d+)","nickname":"([^"]+)"#i', $content, $m);

        if ($m[1] != $id) {
            echo "Fixing IDs $id -> {$m[1]}.\n";
            $updateStmt->execute([$money[1], $m[1]]);
            $updateStmt->execute([null, $id]);
            @copy("/tmp/roswar_cookies/$id.cook", "/tmp/roswar_cookies/{$m[1]}.cook");
            continue;
        }

        continue;

        preg_match('#<span id="currenthp">(.+)</span>/<span id="maxhp">(.+)</span>#i', $content, $m);

        if ($m[1] / $m[2] < 0.50) {
            echo "HP {$m[1]}/{$m[2]} is too low to attack. Restoring..\n";

            curl_setopt_array($ch, [
                CURLOPT_URL        => 'http://www.roswar.ru/player/checkhp/',
                CURLOPT_POST       => true,
                CURLOPT_POSTFIELDS => http_build_query(['action' => 'restorehp']),
            ]);

            $content = curl_exec($ch);
        }

        echo "Attacking..\n";

        curl_setopt_array($ch, [
            CURLOPT_URL        => 'http://www.roswar.ru/alley/',
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => http_build_query(['action' => 'attack', 'player' => '1430380', 'use_items' => 0]),  // Killah
//            CURLOPT_POSTFIELDS => http_build_query(['action' => 'attack', 'player' => '1429142', 'use_items' => 0]),  // Skpd
//            CURLOPT_POSTFIELDS => http_build_query(['action' => 'attack', 'player' => '1426048', 'use_items' => 0]),  // Walond
        ]);

        $content = curl_exec($ch);

        if (preg_match('#<div class="red">(.*?)</div>#ims', $content, $m)) {
            echo trim(preg_replace('/\s+/ms', ' ', $m[1])) . PHP_EOL;
        }
    }

    file_put_contents('/tmp/result.html', $content);

    echo "Done\n";
    curl_close($ch);
}