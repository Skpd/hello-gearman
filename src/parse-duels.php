<?php

require dirname(__DIR__) . '/vendor/autoload.php';

$pdo = new PDO('mysql:host=do.dev0.in;dbname=gearman', 'root', 'root123!', [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$updateCookieStmt = $pdo->prepare('UPDATE roswar_auth SET data = ? WHERE id = ?');
$insertDuelStmt = $pdo->prepare('INSERT IGNORE INTO roswar_duels (date, attacker, victim, winner, money, url) VALUES (?, ?, ?, ?, ?, ?)');

$id = 1429142;

$lastDuel = $pdo->query("SELECT date FROM roswar_duels WHERE attacker = $id OR victim = $id ORDER BY date DESC LIMIT 1")->fetch(PDO::FETCH_COLUMN);

$dateFrom = new \DateTime('', new DateTimeZone("Europe/Moscow"));
$dateTo = new \DateTime('-7 days', new DateTimeZone("Europe/Moscow"));
$baseUrl = 'http://www.roswar.ru/phone/duels/';

while ($dateFrom >= $dateTo) {
    $doc = new DOMDocument;
    $page = 1;

    while (true) {
        $url = $baseUrl . $dateFrom->format('Ymd') . '/' . $page . '/';
        echo "Loading page $url\n";
        $r = getPage($url, $id);

        @$doc->loadHTML($r);
        $xpath = new DOMXPath($doc);

        $duels = $xpath->query("descendant-or-self::*[contains(concat(' ', normalize-space(@class), ' '), ' messages-list ')]/descendant::tr");

        if (empty($duels) || $duels->length < 1) {
            $dateFrom->sub(new DateInterval('P1D'));
            continue 2;
        }

        for ($i = $duels->length - 1; $i >= 0; $i--) {
            $entry = $duels->item($i);

            $date = DateTime::createFromFormat("d.m.Y H:i:s", $entry->childNodes->item(0)->textContent, new DateTimeZone("Europe/Moscow"));

            if (preg_match('#Игрок.+/player/(\d+)/.+<br>(Вы потеряли|Вы получили).+class="tugriki">([\d,]+)<i>#iU', $doc->saveHTML($entry), $m)) {
                $victim = $id;
                $attacker = intval($m[1]);
                $winner = $m[2] == 'Вы потеряли' ? 1 : 0;
                $money = intval(str_replace(',', '', $m[3]));
            } else if (preg_match('#Вы напали.+/player/(\d+)/.+<br>(Вы потеряли|Вы получили).+class="tugriki">([\d,]+)<i>#iU', $doc->saveHTML($entry), $m)) {
                $victim = intval($m[1]);
                $attacker = $id;
                $winner = $m[2] == 'Вы потеряли' ? 0 : 1;
                $money = intval(str_replace(',', '', $m[3]));
            } else {
                continue;
            }

            preg_match('#"(/alley/fight/.+/.+/)"#iU', $doc->saveHTML($entry), $url);

            $insertDuelStmt->execute([
                $date->format("Y-m-d H:i:s"),
                $attacker,
                $victim,
                $winner,
                $money,
                $url[1]
            ]);
        }

        $page++;
    }
}

function getPage($url, $id, $isPost = false, $data = null)
{
    global $pdo, $updateCookieStmt;

    $player = $pdo->query('SELECT proxy, data FROM roswar_players JOIN roswar_auth USING (id) WHERE id = ' . $id)->fetch();

    $file = tempnam(sys_get_temp_dir(), 'roswar_cookie');
    file_put_contents($file, $player['data']);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_AUTOREFERER => true,
        CURLOPT_HTTPHEADER => ["Proxy-Connection:"],
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_USERAGENT => "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1",
        CURLOPT_COOKIESESSION => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => $file,
        CURLOPT_COOKIEFILE => $file,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_PROXY => $player['proxy'],
    ]);

    if ($isPost) {
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => is_array($data) ? http_build_query($data) : $data,
        ]);
    }

    $content = curl_exec($ch);
    $cookie = file_get_contents($file);

    $updateCookieStmt->execute([$cookie, $id]);

    return $content;
}